<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\ReviewerAssignment;
use App\Models\SidangRequest;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\RoleNavigationService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SidangRequestController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $sort = (string) $request->query('sort', 'tanggal');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, ['tanggal', 'mahasiswa', 'judul', 'status'], true)) {
            $sort = 'tanggal';
        }

        $requests = SidangRequest::query()
            ->select('sidang_requests.*')
            ->leftJoin('skripsis', 'skripsis.id', '=', 'sidang_requests.skripsi_id')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 'skripsis.student_id')
            ->where('sidang_requests.lecturer_id', (int) $request->user()->id)
            ->with(['skripsi.student', 'lecturer'])
            ->when($status !== '', function ($query) use ($status): void {
                if ($status === 'submitted') {
                    $query->where('sidang_requests.status', 'submitted');
                    return;
                }
                if ($status === 'approved') {
                    $query->where('sidang_requests.status', 'approved');
                }
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('students_sort.name', 'like', "%{$search}%")
                        ->orWhere('students_sort.nim', 'like', "%{$search}%")
                        ->orWhere('skripsis.title', 'like', "%{$search}%");
                });
            });

        $sortMap = [
            'tanggal' => 'sidang_requests.submitted_at',
            'mahasiswa' => 'students_sort.name',
            'judul' => 'skripsis.title',
            'status' => 'sidang_requests.status',
        ];

        $requests = $requests
            ->orderBy($sortMap[$sort], $direction)
            ->orderBy('sidang_requests.id', 'desc')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('dosen.sidang-request.partials.table', ['requests' => $requests, 'sort' => $sort, 'direction' => $direction])->render(),
                'pagination_html' => view('dosen.sidang-request.partials.pagination', ['requests' => $requests])->render(),
                'count_text' => $requests->total() . ' pengajuan sidang skripsi ditemukan.',
            ]);
        }

        return view('dosen.sidang-request.index', $this->page('Pengajuan Sidang Skripsi', 'DOSEN • PENGAJUAN SIDANG', [
            'requests' => $requests,
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
        ]));
    }


    public function store(Request $request, Skripsi $skripsi, NotificationService $notifications): RedirectResponse
    {
        $assignment = $this->advisorAssignment($skripsi, (int) $request->user()->id);

        if ($assignment === null || $skripsi->current_phase !== 'bimbingan_skripsi') {
            return redirect()
                ->route('dosen.skripsi.show', $skripsi, false)
                ->with('error', 'Permohonan sidang hanya bisa diajukan oleh dosen pembimbing saat fase bimbingan skripsi.');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $sidangRequest = SidangRequest::query()->updateOrCreate(
            [
                'skripsi_id' => $skripsi->id,
                'lecturer_id' => $request->user()->id,
            ],
            [
                'role_type' => $assignment->role_type,
                'status' => 'submitted',
                'note' => $validated['note'] ?? null,
                'submitted_at' => now(),
                'approved_at' => null,
                'approved_by' => null,
            ]
        );

        $kaprodiUsers = User::query()->forRole('kaprodi')->get();
        $notifications->send($kaprodiUsers, [
            'type' => 'sidang_request_submitted',
            'title' => 'Permohonan sidang baru',
            'message' => $request->user()->name . ' mengajukan permohonan sidang untuk ' . $skripsi->title,
            'url' => route('kaprodi.skripsi.show', $skripsi, false),
            'actor' => $request->user()->name,
            'meta' => [
                'skripsi_id' => $skripsi->id,
                'sidang_request_id' => $sidangRequest->id,
                'phase' => 'sidang_skripsi',
            ],
        ]);

        return redirect()
            ->route('dosen.skripsi.show', $skripsi, false)
            ->with('success', 'Permohonan sidang berhasil dikirim ke Kaprodi.');
    }

    private function page(string $heading, string $crumbs, array $extra = []): array
    {
        $navigation = app(RoleNavigationService::class);

        return array_merge([
            'title' => $heading,
            'heading' => $heading,
            'crumbs' => $crumbs,
            'navItems' => $navigation->dosenNavItems(),
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'dosen',
            'primaryCta' => null,
        ], $extra);
    }

    private function advisorAssignment(Skripsi $skripsi, int $lecturerId): ?ReviewerAssignment
    {
        return ReviewerAssignment::query()
            ->where('skripsi_id', $skripsi->id)
            ->where('lecturer_id', $lecturerId)
            ->where('role_type', 'pembimbing_1')
            ->first();
    }
}
