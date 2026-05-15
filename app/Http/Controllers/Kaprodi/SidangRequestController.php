<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\ReviewerAssignment;
use App\Models\SidangRequest;
use App\Models\Skripsi;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SidangRequestController extends Controller
{
    use BuildsKaprodiPage;

    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $approvalStatus = (string) $request->query('approval_status', 'all');
        $sidangType = (string) $request->query('sidang_type', 'all');
        $periodeId = (int) $request->query('periode_id', 0);
        $sort = (string) $request->query('sort', 'tanggal');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($approvalStatus, ['all', 'pending_approval', 'approved'], true)) {
            $approvalStatus = 'all';
        }

        if (! in_array($sidangType, ['all', 'proposal', 'skripsi'], true)) {
            $sidangType = 'all';
        }

        if (! in_array($sort, ['tanggal', 'mahasiswa', 'judul', 'fase'], true)) {
            $sort = 'tanggal';
        }

        $requests = SidangRequest::query()
            ->select('sidang_requests.*')
            ->leftJoin('skripsis', 'skripsis.id', '=', 'sidang_requests.skripsi_id')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 'skripsis.student_id')
            ->with(['skripsi.student', 'lecturer'])
            ->when($periodeId > 0, fn ($query) => $query->where('skripsis.periode_id', $periodeId))
            ->when($approvalStatus === 'pending_approval', function ($query) {
                $query->where('sidang_requests.status', 'submitted');
            })
            ->when($approvalStatus === 'approved', function ($query) {
                $query->where('sidang_requests.status', 'approved');
            })
            ->when($sidangType === 'proposal', function ($query) {
                $query->where('sidang_requests.role_type', 'mahasiswa');
            })
            ->when($sidangType === 'skripsi', function ($query) {
                $query->where('sidang_requests.role_type', '!=', 'mahasiswa');
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->whereHas('skripsi.student', function ($studentQuery) use ($search): void {
                        $studentQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('nim', 'like', "%{$search}%");
                    })->orWhereHas('skripsi', function ($skripsiQuery) use ($search): void {
                        $skripsiQuery->where('title', 'like', "%{$search}%");
                    })->orWhereHas('lecturer', function ($lecturerQuery) use ($search): void {
                        $lecturerQuery->where('name', 'like', "%{$search}%");
                    });
                });
            });

        $sortMap = [
            'tanggal' => 'sidang_requests.submitted_at',
            'mahasiswa' => 'students_sort.name',
            'judul' => 'skripsis.title',
            'fase' => 'sidang_requests.role_type',
        ];

        $requests = $requests
            ->orderBy($sortMap[$sort], $direction)
            ->orderBy('sidang_requests.id', 'desc')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.sidang-request.partials.table', [
                    'requests' => $requests,
                    'sort' => $sort,
                    'direction' => $direction,
                    'periodeId' => $periodeId,
                ])->render(),
                'pagination_html' => view('kaprodi.sidang-request.partials.pagination', [
                    'requests' => $requests,
                ])->render(),
                'count_text' => $requests->total() . ' permohonan sidang ditemukan.',
            ]);
        }

        return view('kaprodi.sidang-request.index', $this->page('Permohonan Sidang', 'KAPRODI • SIDANG', [
            'requests' => $requests,
            'search' => $search,
            'approvalStatus' => $approvalStatus,
            'sidangType' => $sidangType,
            'periodeId' => $periodeId,
            'sort' => $sort,
            'direction' => $direction,
        ]));
    }
public function approve(Skripsi $skripsi, SidangRequest $sidangRequest, NotificationService $notifications): RedirectResponse
    {
        abort_unless($sidangRequest->skripsi_id === $skripsi->id, 404);

        $sidangRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        if ($sidangRequest->role_type === 'mahasiswa') {
            $skripsi->update([
                'current_phase' => 'sidang_proposal',
                'proposal_review_status' => 'approved',
                'proposal_reviewed_at' => now(),
                'proposal_review_note' => null,
            ]);

            $notifications->send([$skripsi->student], [
                'type' => 'sidang_request_approved',
                'title' => 'Permohonan Sidang Disetujui',
                'message' => 'Permohonan sidang Anda telah disetujui Kaprodi.',
                'url' => route('mahasiswa.skripsi.show', $skripsi, false),
                'actor' => auth()->user()->name,
                'meta' => ['skripsi_id' => $skripsi->id],
            ]);
        } else {
            $this->syncSkripsiPhaseIfReady($skripsi->fresh());
        }

        return redirect()
            ->route('kaprodi.skripsi.show', $skripsi, false)
            ->with('success', 'Permohonan sidang berhasil disetujui.');
    }


    private function page(string $heading, string $crumbs, array $extra = []): array
    {
        return $this->kaprodiPage($heading, $crumbs, $extra);
    }

    private function syncSkripsiPhaseIfReady(Skripsi $skripsi): void
    {
        $advisorIds = ReviewerAssignment::query()
            ->where('skripsi_id', $skripsi->id)
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])
            ->pluck('lecturer_id')
            ->unique()
            ->values();

        if ($advisorIds->isEmpty()) {
            return;
        }

        $approvedCount = SidangRequest::query()
            ->where('skripsi_id', $skripsi->id)
            ->whereIn('lecturer_id', $advisorIds)
            ->where('status', 'approved')
            ->distinct('lecturer_id')
            ->count('lecturer_id');

        if ($approvedCount === $advisorIds->count()) {
            $skripsi->update([
                'current_phase' => 'sidang_skripsi',
            ]);
        }
    }
}
