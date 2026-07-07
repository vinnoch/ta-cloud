<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\Skripsi;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ProposalSubmissionController extends Controller
{
    use BuildsKaprodiPage;

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $periodeId = (int) $request->query('periode_id', 0);
        $reviewerStatus = (string) $request->query('reviewer_status', 'all');
        $statusFilter = (string) $request->query('status', 'all');
        $sort = (string) $request->query('sort', 'tanggal');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (! in_array($sort, ['tanggal', 'nim', 'mahasiswa', 'judul', 'periode'], true)) {
            $sort = 'tanggal';
        }

        if (! in_array($reviewerStatus, ['all', 'pending_approval', 'unassigned'], true)) {
            $reviewerStatus = 'all';
        }

        if (! in_array($statusFilter, ['all', 'pending', 'approved', 'rejected'], true)) {
            $statusFilter = 'all';
        }

        $proposals = Skripsi::query()
            ->select('skripsis.*')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 'skripsis.student_id')
            ->leftJoin('periodes as periodes_sort', 'periodes_sort.id', '=', 'skripsis.periode_id')
            ->withMin(['documentVersions as proposal_submitted_at' => function ($q) {
                $q->where('phase', 'proposal');
            }], 'created_at')
            ->when($reviewerStatus === 'unassigned', function ($query) {
                $query->where('current_phase', 'sidang_proposal')
                    ->whereHas('documentVersions', fn ($q) => $q->where('phase', 'proposal'))
                    ->whereDoesntHave('assignments', function ($assignmentQuery) {
                        $assignmentQuery->whereIn('role_type', ['pembimbing_1', 'pembimbing_2']);
                    });
            }, function ($query) use ($statusFilter) {
                $query->whereHas('documentVersions', fn ($q) => $q->where('phase', 'proposal'))
                    ->when($statusFilter === 'approved', function ($approvedQuery) {
                        $approvedQuery->where('proposal_review_status', 'approved');
                    })
                    ->when($statusFilter === 'rejected', function ($rejectedQuery) {
                        $rejectedQuery->whereIn('proposal_review_status', ['rejected', 'revision_required']);
                    })
                    ->when($statusFilter === 'pending', function ($pendingQuery) {
                        $pendingQuery->where('current_phase', 'proposal')
                            ->where(function ($statusQuery) {
                                $statusQuery->whereNull('proposal_review_status')
                                    ->orWhereNotIn('proposal_review_status', ['approved', 'rejected', 'revision_required']);
                            });
                    })
                    ->when($statusFilter === 'all', function ($allQuery) {
                        $allQuery->whereIn('current_phase', ['proposal', 'sidang_proposal']);
                    });
            })
            ->when($periodeId > 0, fn ($query) => $query->where('skripsis.periode_id', $periodeId))
            ->with(['student', 'periode', 'documentVersions' => function ($q) {
                $q->where('phase', 'proposal')->oldest('created_at');
            }])
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->whereHas('student', fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('nim', 'like', "%{$search}%"))
                        ->orWhere('title', 'like', "%{$search}%");
                });
            });

        $sortMap = [
            'tanggal' => 'proposal_submitted_at',
            'nim' => 'students_sort.nim',
            'mahasiswa' => 'students_sort.name',
            'judul' => 'skripsis.title',
            'periode' => 'periodes_sort.kode_periode',
        ];

        $proposals = $proposals
            ->orderBy($sortMap[$sort], $direction)
            ->orderBy('skripsis.id', 'desc')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.proposal-submission.partials.table', [
                    'proposals' => $proposals,
                    'sort' => $sort,
                    'direction' => $direction,
                    'periodeId' => $periodeId,
                    'reviewerStatus' => $reviewerStatus,
            'statusFilter' => $statusFilter,
                    'statusFilter' => $statusFilter,
                ])->render(),
                'pagination_html' => view('kaprodi.proposal-submission.partials.pagination', [
                    'proposals' => $proposals,
                ])->render(),
                'count_text' => $proposals->total() . ' pengajuan proposal ditemukan.',
            ]);
        }

        return view('kaprodi.proposal-submission.index', $this->kaprodiPage('Pengajuan Proposal', 'KAPRODI • PROPOSAL', [
            'proposals' => $proposals,
            'search' => $search,
            'periodeId' => $periodeId,
            'sort' => $sort,
            'direction' => $direction,
            'reviewerStatus' => $reviewerStatus,
            'statusFilter' => $statusFilter,
        ]));
    }

    public function approve(Request $request, Skripsi $skripsi, NotificationService $notifications): RedirectResponse
    {
        if ($skripsi->proposal_review_status !== 'approved') {
            $skripsi->update([
                'current_phase' => 'sidang_proposal',
                'proposal_review_status' => 'approved',
                'proposal_reviewed_at' => now(),
                'proposal_review_note' => null,
            ]);

            $notifications->send([$skripsi->student], [
                'type' => 'proposal_approved',
                'title' => 'Proposal Disetujui',
                'message' => 'Proposal Anda telah disetujui Kaprodi dan siap untuk dijadwalkan sidang.',
                'url' => route('mahasiswa.skripsi.show', $skripsi, false),
                'actor' => auth()->user()->name,
                'meta' => ['skripsi_id' => $skripsi->id],
            ]);

            return back()->with('success', 'Proposal berhasil disetujui. Fase skripsi berubah ke Sidang Proposal.');
        }

        return back()->with('error', 'Proposal ini sudah disetujui sebelumnya.');
    }

    public function reject(Request $request, Skripsi $skripsi, NotificationService $notifications): RedirectResponse
    {
        if ($skripsi->proposal_review_status !== 'approved') {
            $validated = $request->validate([
                'note' => ['required', 'string', 'max:1000'],
            ]);

            $skripsi->forceFill([
                'current_phase' => 'proposal',
                'proposal_review_status' => 'rejected',
                'proposal_rejected_at' => now(),
                'proposal_reviewed_at' => null,
                'proposal_review_note' => $validated['note'],
            ])->save();

            $notifications->send([$skripsi->student], [
                'type' => 'proposal_rejected',
                'title' => 'Proposal Butuh Revisi',
                'message' => 'Proposal Anda butuh revisi: ' . $validated['note'],
                'url' => route('mahasiswa.skripsi.show', $skripsi, false),
                'actor' => auth()->user()->name,
                'meta' => [
                    'skripsi_id' => $skripsi->id,
                    'note' => $validated['note'],
                ],
            ]);

            return back()->with('success', 'Proposal ditolak. Mahasiswa telah dinotifikasi untuk melakukan revisi.');
        }

        return back()->with('error', 'Proposal ini sudah disetujui sebelumnya.');
    }
}
