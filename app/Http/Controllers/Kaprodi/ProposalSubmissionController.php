<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\TugasAkhir;
use App\Services\NotificationService;
use App\Services\TugasAkhirRouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $tugasAkhirType = (string) $request->query('tugas_akhir_type', 'all');
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

        if (! in_array($tugasAkhirType, ['all', 'skripsi', 'non_skripsi'], true)) {
            $tugasAkhirType = 'all';
        }

        $proposals = TugasAkhir::query()
            ->select('tugas_akhirs.*')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 'tugas_akhirs.student_id')
            ->leftJoin('periodes as periodes_sort', 'periodes_sort.id', '=', 'tugas_akhirs.periode_id')
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
            ->when($periodeId > 0, fn ($query) => $query->where('tugas_akhirs.periode_id', $periodeId))
            ->when($tugasAkhirType !== 'all', fn ($query) => $query->where('tugas_akhirs.type', $tugasAkhirType))
            ->with(['student', 'periode', 'documentVersions' => function ($q) {
                $q->where('phase', 'proposal')->oldest('created_at');
            }])
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('nim', 'like', "%{$search}%"))
                        ->orWhere('title', 'like', "%{$search}%");
                });
            });

        $sortMap = [
            'tanggal' => 'proposal_submitted_at',
            'nim' => 'students_sort.nim',
            'mahasiswa' => 'students_sort.name',
            'judul' => 'tugas_akhirs.title',
            'periode' => 'periodes_sort.kode_periode',
        ];

        $proposals = $proposals
            ->orderBy($sortMap[$sort], $direction)
            ->orderBy('tugas_akhirs.id', 'desc')
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
                    'tugasAkhirType' => $tugasAkhirType,
                ])->render(),
                'pagination_html' => view('kaprodi.proposal-submission.partials.pagination', [
                    'proposals' => $proposals,
                ])->render(),
                'count_text' => $proposals->total().' pengajuan proposal ditemukan.',
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
            'tugasAkhirType' => $tugasAkhirType,
        ]));
    }

    public function approve(Request $request, TugasAkhir $skripsi, NotificationService $notifications): RedirectResponse
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
                'url' => app(TugasAkhirRouteService::class)->route('mahasiswa', $skripsi, 'show', $skripsi, false),
                'actor' => auth()->user()->name,
                'meta' => ['tugas_akhir_id' => $skripsi->id],
            ]);

            return back()->with('success', 'Proposal berhasil disetujui. Fase skripsi berubah ke Sidang Proposal.');
        }

        return back()->with('error', 'Proposal ini sudah disetujui sebelumnya.');
    }

    public function reject(Request $request, TugasAkhir $skripsi, NotificationService $notifications): RedirectResponse
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
                'message' => 'Proposal Anda butuh revisi: '.$validated['note'],
                'url' => app(TugasAkhirRouteService::class)->route('mahasiswa', $skripsi, 'show', $skripsi, false),
                'actor' => auth()->user()->name,
                'meta' => [
                    'tugas_akhir_id' => $skripsi->id,
                    'note' => $validated['note'],
                ],
            ]);

            return back()->with('success', 'Proposal ditolak. Mahasiswa telah dinotifikasi untuk melakukan revisi.');
        }

        return back()->with('error', 'Proposal ini sudah disetujui sebelumnya.');
    }
}
