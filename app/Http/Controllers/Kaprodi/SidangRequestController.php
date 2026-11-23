<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\ReviewerAssignment;
use App\Models\SidangRequest;
use App\Models\TugasAkhir;
use App\Services\NotificationService;
use App\Services\TugasAkhirRouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SidangRequestController extends Controller
{
    use BuildsKaprodiPage;

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $approvalStatus = (string) $request->query('approval_status', 'all');
        $sidangType = (string) $request->query('sidang_type', 'all');
        $periodeId = (int) $request->query('periode_id', 0);
        $sort = (string) $request->query('sort', 'tanggal');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($approvalStatus, ['all', 'pending_approval', 'approved', 'rejected'], true)) {
            $approvalStatus = 'all';
        }

        if (! in_array($sidangType, ['all', 'proposal', 'skripsi', 'non_skripsi'], true)) {
            $sidangType = 'all';
        }

        if (! in_array($sort, ['tanggal', 'mahasiswa', 'judul', 'fase'], true)) {
            $sort = 'tanggal';
        }

        $requests = SidangRequest::query()
            ->select('sidang_requests.*')
            ->leftJoin('tugas_akhirs', 'tugas_akhirs.id', '=', 'sidang_requests.tugas_akhir_id')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 'tugas_akhirs.student_id')
            ->with(['tugasAkhir.student', 'tugasAkhir.assignments', 'tugasAkhir.sidangRequests', 'lecturer'])
            ->when($periodeId > 0, fn ($query) => $query->where('tugas_akhirs.periode_id', $periodeId))
            ->when($approvalStatus === 'pending_approval', function ($query) {
                $query->where('sidang_requests.status', 'submitted');
            })
            ->when($approvalStatus === 'approved', function ($query) {
                $query->where('sidang_requests.status', 'approved');
            })
            ->when($approvalStatus === 'rejected', function ($query) {
                $query->where('sidang_requests.status', 'rejected');
            })
            ->when($sidangType === 'proposal', function ($query) {
                $query->where('sidang_requests.role_type', 'mahasiswa');
            })
            ->when($sidangType === 'skripsi', function ($query) {
                $query->where('sidang_requests.role_type', '!=', 'mahasiswa')
                    ->where('tugas_akhirs.type', 'skripsi');
            })
            ->when($sidangType === 'non_skripsi', function ($query) {
                $query->where('sidang_requests.role_type', '!=', 'mahasiswa')
                    ->where('tugas_akhirs.type', 'non_skripsi');
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->whereHas('tugasAkhir.student', function ($studentQuery) use ($search): void {
                        $studentQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('nim', 'like', "%{$search}%");
                    })->orWhereHas('tugasAkhir', function ($skripsiQuery) use ($search): void {
                        $skripsiQuery->where('title', 'like', "%{$search}%");
                    })->orWhereHas('lecturer', function ($lecturerQuery) use ($search): void {
                        $lecturerQuery->where('name', 'like', "%{$search}%");
                    });
                });
            });

        $sortMap = [
            'tanggal' => 'sidang_requests.submitted_at',
            'mahasiswa' => 'students_sort.name',
            'judul' => 'tugas_akhirs.title',
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
                'count_text' => $requests->total().' permohonan sidang ditemukan.',
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

    public function approve(TugasAkhir $skripsi, SidangRequest $sidangRequest, NotificationService $notifications): RedirectResponse
    {
        abort_unless($sidangRequest->tugas_akhir_id === $skripsi->id, 404);

        if ($sidangRequest->role_type !== 'mahasiswa' && ! $this->allAdvisorRequestsSubmitted($skripsi)) {
            return back()->with('error', 'Sidang skripsi baru dapat disetujui setelah semua dosen pembimbing mengajukan permohonan.');
        }

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

            try {
                $notifications->send([$skripsi->student], [
                    'type' => 'sidang_request_approved',
                    'title' => 'Permohonan Sidang Disetujui',
                    'message' => 'Permohonan sidang Anda telah disetujui Kaprodi.',
                    'url' => app(TugasAkhirRouteService::class)->route('mahasiswa', $skripsi, 'show', $skripsi, false),
                    'actor' => auth()->user()->name,
                    'meta' => ['tugas_akhir_id' => $skripsi->id],
                ]);
            } catch (Throwable $exception) {
                report($exception);
            }
        } else {
            $this->syncSkripsiPhaseIfReady($skripsi->fresh());

            try {
                $skripsi->loadMissing('student');
                $sidangRequest->loadMissing('lecturer');

                $recipients = collect([$skripsi->student, $sidangRequest->lecturer])->filter()->unique('id')->values();

                $notifications->send($recipients, [
                    'type' => 'sidang_request_approved',
                    'title' => 'Permohonan Sidang Disetujui',
                    'message' => 'Kaprodi telah menyetujui permohonan sidang untuk skripsi: '.$skripsi->title,
                    'url' => app(TugasAkhirRouteService::class)->route('dosen', $skripsi, 'show', $skripsi, false),
                    'actor' => auth()->user()->name,
                    'meta' => ['tugas_akhir_id' => $skripsi->id],
                ]);

                if ($skripsi->student) {
                    $notifications->send([$skripsi->student], [
                        'type' => 'sidang_request_approved',
                        'title' => 'Permohonan Sidang Disetujui',
                        'message' => 'Kaprodi telah menyetujui permohonan sidang untuk skripsi Anda: '.$skripsi->title,
                        'url' => app(TugasAkhirRouteService::class)->route('mahasiswa', $skripsi, 'show', $skripsi, false),
                        'actor' => auth()->user()->name,
                        'meta' => ['tugas_akhir_id' => $skripsi->id],
                    ]);
                }
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        try {
            return redirect()
                ->to(app(TugasAkhirRouteService::class)->route('kaprodi', $skripsi, 'show', $skripsi))
                ->with('success', 'Permohonan sidang berhasil disetujui.');
        } catch (Throwable $e) {
            \Log::error('Approve Sidang Redirect Failed: '.$e->getMessage());
            throw $e;
        }
    }

    public function reject(Request $request, TugasAkhir $skripsi, SidangRequest $sidangRequest, NotificationService $notifications): RedirectResponse
    {
        abort_unless($sidangRequest->tugas_akhir_id === $skripsi->id, 404);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $sidangRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'approved_by' => auth()->id(),
            'note' => $validated['note'] ?? $sidangRequest->note,
        ]);

        try {
            $notifications->send([$skripsi->student], [
                'type' => 'sidang_request_rejected',
                'title' => 'Permohonan Sidang Ditolak',
                'message' => 'Permohonan sidang Anda ditolak Kaprodi.'.(! empty($validated['note']) ? ' Catatan: '.$validated['note'] : ''),
                'url' => app(TugasAkhirRouteService::class)->route('mahasiswa', $skripsi, 'show', $skripsi, false),
                'actor' => auth()->user()->name,
                'meta' => ['tugas_akhir_id' => $skripsi->id, 'note' => $validated['note'] ?? null],
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->to(app(TugasAkhirRouteService::class)->route('kaprodi', $skripsi, 'show', $skripsi))
            ->with('success', 'Permohonan sidang berhasil ditolak.');
    }

    private function page(string $heading, string $crumbs, array $extra = []): array
    {
        return $this->kaprodiPage($heading, $crumbs, $extra);
    }

    private function syncSkripsiPhaseIfReady(TugasAkhir $skripsi): void
    {
        $advisorIds = ReviewerAssignment::query()
            ->where('tugas_akhir_id', $skripsi->id)
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])
            ->pluck('lecturer_id')
            ->unique()
            ->values();

        if ($advisorIds->isEmpty()) {
            return;
        }

        $approvedCount = SidangRequest::query()
            ->where('tugas_akhir_id', $skripsi->id)
            ->whereIn('lecturer_id', $advisorIds)
            ->where('status', 'approved')
            ->distinct('lecturer_id')
            ->count('lecturer_id');

        if ($approvedCount === $advisorIds->count()) {
            $skripsi->update([
                'current_phase' => 'sidang_skripsi',
            ]);
            \Log::debug('Skripsi phase updated to sidang_skripsi for ID '.$skripsi->id);
        } else {
            \Log::debug('Skripsi phase not yet updated. Approved: '.$approvedCount.' / Expected: '.$advisorIds->count().' for Skripsi ID '.$skripsi->id);
        }
    }

    private function allAdvisorRequestsSubmitted(TugasAkhir $skripsi): bool
    {
        $advisorIds = $skripsi->assignments()
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])
            ->pluck('lecturer_id')
            ->unique();

        return $advisorIds->isNotEmpty()
            && $skripsi->sidangRequests()
                ->whereIn('lecturer_id', $advisorIds)
                ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])
                ->whereIn('status', ['submitted', 'approved'])
                ->distinct('lecturer_id')
                ->count('lecturer_id') === $advisorIds->count();
    }
}
