<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\ReviewerAssignment;
use App\Models\Skripsi;
use App\Services\RoleNavigationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkripsiViewController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $search = (string) $request->query('q', '');
        $fase = (string) $request->query('fase', 'all');
        $sort = (string) $request->query('sort', 'nim');
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, ['tanggal', 'nim', 'mahasiswa', 'judul', 'fase'], true)) {
            $sort = 'nim';
        }

        $summaryBaseQuery = Skripsi::query()
            ->select('skripsis.*')
            ->join('reviewer_assignments as ra_summary', 'ra_summary.skripsi_id', '=', 'skripsis.id')
            ->leftJoin('users as students_summary', 'students_summary.id', '=', 'skripsis.student_id')
            ->where('ra_summary.lecturer_id', $request->user()->id)
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($inner) use ($search): void {
                    $inner->where('students_summary.name', 'like', "%{$search}%")
                        ->orWhere('students_summary.nim', 'like', "%{$search}%")
                        ->orWhere('skripsis.title', 'like', "%{$search}%");
                });
            })
            ->distinct('skripsis.id');

        $query = Skripsi::query()
            ->select('skripsis.*', 'students_sort.nim as student_sort_nim', 'students_sort.name as student_sort_name')
            ->join('reviewer_assignments as ra_sort', 'ra_sort.skripsi_id', '=', 'skripsis.id')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 'skripsis.student_id')
            ->where('ra_sort.lecturer_id', $request->user()->id)
            ->with(['student', 'periode'])
            ->when($fase !== 'all', function ($q) use ($fase): void {
                $q->where('skripsis.current_phase', $fase);
            })
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($inner) use ($search): void {
                    $inner->where('students_sort.name', 'like', "%{$search}%")
                        ->orWhere('students_sort.nim', 'like', "%{$search}%")
                        ->orWhere('skripsis.title', 'like', "%{$search}%");
                });
            })
            ->distinct('skripsis.id');

        $sortMap = [
            'tanggal' => 'skripsis.created_at',
            'nim' => 'students_sort.nim',
            'mahasiswa' => 'students_sort.name',
            'judul' => 'skripsis.title',
            'fase' => 'skripsis.current_phase',
        ];

        $skripsis = $query
            ->orderBy($sortMap[$sort], $direction)
            ->orderBy('skripsis.id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $summarySource = (clone $summaryBaseQuery)->get(['skripsis.current_phase'])
            ->map(function ($item) {
                $item->current_phase = strtolower(str_replace('_', ' ', (string) $item->current_phase));

                return $item;
            });

        $chartData = [
            ['label' => 'Proposal', 'value' => $summarySource->whereIn('current_phase', ['proposal', 'sidang proposal'])->count()],
            ['label' => 'Bimbingan Skripsi', 'value' => $summarySource->where('current_phase', 'bimbingan skripsi')->count()],
            ['label' => 'Sidang Skripsi', 'value' => $summarySource->whereIn('current_phase', ['sidang skripsi', 'revisi sidang skripsi'])->count()],
            ['label' => 'Review Dokumen Final', 'value' => $summarySource->where('current_phase', 'review dokumen final')->count()],
            ['label' => 'Skripsi Selesai', 'value' => $summarySource->where('current_phase', 'skripsi selesai')->count()],
        ];

        if ($request->ajax() || $request->expectsJson()) {
            $suggestions = (clone $query)
                ->orderBy('skripsis.id', 'desc')
                ->limit(6)
                ->get()
                ->map(fn (Skripsi $skripsi) => [
                    'id' => $skripsi->id,
                    'student_name' => $skripsi->student?->name,
                    'nim' => $skripsi->student?->nim,
                    'title' => $skripsi->title,
                    'url' => route('dosen.skripsi.show', $skripsi, false),
                ])
                ->values();

            return response()->json([
                'table_html' => view('dosen.skripsi.partials.table', ['skripsis' => $skripsis, 'sort' => $sort, 'direction' => $direction])->render(),
                'pagination_html' => view('dosen.skripsi.partials.pagination', ['skripsis' => $skripsis])->render(),
                'stats_html' => view('dosen.skripsi.partials.stats', ['chartData' => $chartData])->render(),
                'count_text' => $skripsis->total().' skripsi ditemukan.',
                'suggestions' => $suggestions,
            ]);
        }

        return view('dosen.skripsi.index', $this->page('Skripsi Mahasiswa Bimbingan', 'DOSEN • SKRIPSI', [
            'skripsis' => $skripsis,
            'search' => $search,
            'fase' => $fase,
            'sort' => $sort,
            'direction' => $direction,
            'chartData' => $chartData,
        ]));
    }

    public function proposals(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $sort = (string) $request->query('sort', 'tanggal');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($status, ['all', 'pending', 'approved', 'rejected'], true)) {
            $status = 'all';
        }

        if (! in_array($sort, ['tanggal', 'mahasiswa', 'judul', 'periode'], true)) {
            $sort = 'tanggal';
        }

        $proposals = Skripsi::query()
            ->select('skripsis.*')
            ->join('reviewer_assignments as proposal_assignments', 'proposal_assignments.skripsi_id', '=', 'skripsis.id')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 'skripsis.student_id')
            ->leftJoin('periodes as periodes_sort', 'periodes_sort.id', '=', 'skripsis.periode_id')
            ->where('proposal_assignments.lecturer_id', $request->user()->id)
            ->whereIn('skripsis.current_phase', ['proposal', 'sidang_proposal'])
            ->when($status === 'approved', fn ($query) => $query->where('proposal_review_status', 'approved'))
            ->when($status === 'rejected', fn ($query) => $query->whereIn('proposal_review_status', ['rejected', 'revision_required']))
            ->when($status === 'pending', function ($query): void {
                $query->where(function ($statusQuery): void {
                    $statusQuery->whereNull('proposal_review_status')
                        ->orWhereNotIn('proposal_review_status', ['approved', 'rejected', 'revision_required']);
                });
            })
            ->withMin(['documentVersions as proposal_submitted_at' => fn ($query) => $query->where('phase', 'proposal')], 'created_at')
            ->with([
                'student',
                'periode',
                'documentVersions' => fn ($query) => $query->where('phase', 'proposal')->oldest('created_at'),
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('students_sort.name', 'like', "%{$search}%")
                        ->orWhere('students_sort.nim', 'like', "%{$search}%")
                        ->orWhere('skripsis.title', 'like', "%{$search}%");
                });
            })
            ->distinct('skripsis.id');

        $sortMap = [
            'tanggal' => 'proposal_submitted_at',
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
                'table_html' => view('dosen.proposal.partials.table', compact('proposals', 'sort', 'direction'))->render(),
                'pagination_html' => view('dosen.proposal.partials.pagination', compact('proposals'))->render(),
                'count_text' => $proposals->total().' proposal ditemukan.',
            ]);
        }

        return view('dosen.proposal.index', $this->page('Proposal', 'DOSEN • PROPOSAL', compact(
            'proposals',
            'search',
            'status',
            'sort',
            'direction',
        )));
    }

    public function search(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));

        if (mb_strlen($search) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = Skripsi::query()
            ->select('skripsis.*')
            ->join('reviewer_assignments as ra_sort', 'ra_sort.skripsi_id', '=', 'skripsis.id')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 'skripsis.student_id')
            ->where('ra_sort.lecturer_id', $request->user()->id)
            ->where(function ($q) use ($search): void {
                $q->where('students_sort.name', 'like', "%{$search}%")
                    ->orWhere('students_sort.nim', 'like', "%{$search}%")
                    ->orWhere('skripsis.title', 'like', "%{$search}%");
            })
            ->with(['student'])
            ->distinct('skripsis.id')
            ->orderBy('skripsis.id', 'desc')
            ->limit(6)
            ->get()
            ->map(fn (Skripsi $skripsi) => [
                'id' => $skripsi->id,
                'student_name' => $skripsi->student?->name,
                'nim' => $skripsi->student?->nim,
                'title' => $skripsi->title,
                'url' => route('dosen.skripsi.show', $skripsi, false),
            ])
            ->values();

        return response()->json(['suggestions' => $suggestions]);
    }

    public function show(Request $request, Skripsi $skripsi): View
    {
        $assigned = ReviewerAssignment::query()
            ->where('skripsi_id', $skripsi->id)
            ->where('lecturer_id', $request->user()->id)
            ->exists();

        if (! $assigned) {
            abort(403);
        }

        $assignment = ReviewerAssignment::query()
            ->where('skripsi_id', $skripsi->id)
            ->where('lecturer_id', $request->user()->id)
            ->first();

        $skripsi->load(['student', 'periode', 'assignments.lecturer', 'bimbingans.reviewer', 'bimbingans.reviewedVersion', 'documentVersions.uploader', 'grades.reviewer', 'sidangRequests']);

        $showGradeReminder = false;

        if ($assignment && $skripsi->current_phase === 'sidang_skripsi' && $skripsi->sidang_skripsi_datetime?->lte(now())) {
            $showGradeReminder = ! Grade::query()
                ->where('skripsi_id', $skripsi->id)
                ->where('reviewer_id', $request->user()->id)
                ->where('role_type', $assignment->role_type)
                ->where('grade_event', 'sidang_skripsi')
                ->where('status', 'published')
                ->exists();
        }

        return view('dosen.skripsi.show', $this->page('Detail Skripsi', 'DOSEN • DETAIL SKRIPSI', [
            'skripsi' => $skripsi,
            'myRoleType' => $assignment?->role_type,
            'showGradeReminder' => $showGradeReminder,
            'sidangProposalSchedule' => $skripsi->sidang_proposal_datetime,
            'sidangSkripsiSchedule' => $skripsi->sidang_skripsi_datetime,
        ]));
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
}
