<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\FormatPenilaian;
use App\Models\Grade;
use App\Models\ReviewerAssignment;
use App\Models\Skripsi;
use App\Services\RoleNavigationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PenilaianController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $lecturerId = Auth::id();
        $search = (string) $request->query('q', '');
        $nilaiSidang = (string) $request->query('nilai_sidang', '');
        $sort = (string) $request->query('sort', 'tanggal');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, ['tanggal', 'mahasiswa', 'judul', 'periode', 'peran', 'status'], true)) {
            $sort = 'tanggal';
        }

        $assignments = ReviewerAssignment::query()
            ->select('reviewer_assignments.*')
            ->leftJoin('skripsis as s_sort', 's_sort.id', '=', 'reviewer_assignments.skripsi_id')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 's_sort.student_id')
            ->leftJoin('periodes as periodes_sort', 'periodes_sort.id', '=', 's_sort.periode_id')
            ->with([
                'skripsi.student',
                'skripsi.periode.tahunAkademik',
                'skripsi.grades' => fn ($query) => $query
                    ->where('reviewer_id', $lecturerId)
                    ->whereIn('grade_event', ['sidang_proposal', 'sidang_skripsi']),
            ])
            ->where('reviewer_assignments.lecturer_id', $lecturerId)
            ->whereIn('reviewer_assignments.role_type', ['pembimbing_1', 'pembimbing_2', 'penguji_1', 'penguji_2'])
            ->whereHas('skripsi', function ($query) use ($nilaiSidang) {
                $query->where(function ($phaseQuery) use ($nilaiSidang) {
                    if ($nilaiSidang === 'sidang_proposal') {
                        $phaseQuery->where('current_phase', 'sidang_proposal');
                        return;
                    }

                    if ($nilaiSidang === 'sidang_skripsi') {
                        $phaseQuery->whereIn('current_phase', ['sidang_skripsi', 'revisi_sidang_skripsi']);
                        return;
                    }

                    $phaseQuery->whereIn('current_phase', ['sidang_proposal', 'sidang_skripsi', 'revisi_sidang_skripsi']);
                });
            })
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($inner) use ($search): void {
                    $inner->where('students_sort.name', 'like', "%{$search}%")
                        ->orWhere('students_sort.nim', 'like', "%{$search}%")
                        ->orWhere('s_sort.title', 'like', "%{$search}%");
                });
            });

        $sortMap = [
            'tanggal' => 'reviewer_assignments.created_at',
            'mahasiswa' => 'students_sort.name',
            'judul' => 's_sort.title',
            'periode' => 'periodes_sort.name',
            'peran' => 'reviewer_assignments.role_type',
            'status' => 'reviewer_assignments.id',
        ];

        $assignments = $assignments
            ->orderBy($sortMap[$sort], $direction)
            ->orderBy('reviewer_assignments.id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $gradingQueue = $assignments->getCollection()
            ->map(function (ReviewerAssignment $assignment) {
                $skripsi = $assignment->skripsi;
                $gradeEvent = $skripsi && $skripsi->current_phase === 'sidang_proposal' ? 'sidang_proposal' : 'sidang_skripsi';
                $existingGrade = $skripsi?->grades->firstWhere('grade_event', $gradeEvent);

                if (! $skripsi) {
                    return null;
                }

                $format = $this->resolveSidangSkripsiFormat($skripsi);
                if (! $format) {
                    return null;
                }

                return [
                    'student' => $skripsi->student?->name ?? '-',
                    'title' => $skripsi->title ?? 'Tanpa Judul',
                    'date' => $assignment->created_at,
                    'fase' => in_array($skripsi->current_phase, ['sidang_proposal']) ? 'Sidang Proposal' : 'Sidang Skripsi',
                    'role' => str($assignment->role_type)->replace('_', ' ')->title()->toString(),
                    'status' => $existingGrade ? 'PUBLISHED' : 'BELUM DINILAI',
                    'href' => route('dosen.penilaian.show', $skripsi),
                    'skripsi_href' => route('dosen.skripsi.show', $skripsi),
                ];
            })
            ->filter()
            ->values();

        $assignments->setCollection($gradingQueue);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('dosen.penilaian.partials.table', ['gradingQueue' => $assignments, 'sort' => $sort, 'direction' => $direction])->render(),
                'pagination_html' => view('dosen.penilaian.partials.pagination', ['gradingQueue' => $assignments])->render(),
                'count_text' => $assignments->total() . ' antrian penilaian ditemukan.',
            ]);
        }

        return view('dosen.penilaian.index', $this->page('Antrian Penilaian', 'DOSEN • GRADING QUEUE', [
            'gradingQueue' => $assignments,
            'search' => $search,
            'nilaiSidang' => $nilaiSidang,
            'sort' => $sort,
            'direction' => $direction,
        ]));
    }

    public function show(Skripsi $skripsi): View
    {
        $assignment = $this->findAssignmentOrFail($skripsi);
        $format = $this->resolveSidangSkripsiFormatOrFail($skripsi);

        $format->load(['items' => fn ($query) => $query->orderBy('sort_order')]);
        $skripsi->load(['student', 'periode.tahunAkademik']);

        $grade = Grade::query()
            ->with('items')
            ->where('skripsi_id', $skripsi->id)
            ->where('format_penilaian_id', $format->id)
            ->where('reviewer_id', Auth::id())
            ->where('grade_event', 'sidang_skripsi')
            ->first();

        $itemScores = $grade
            ? $grade->items->pluck('score', 'item_penilaian_id')->map(fn ($score) => (float) $score)->all()
            : [];

        return view('dosen.penilaian.show', $this->page('Form Penilaian Sidang', 'DOSEN • GRADING FORM', [
            'skripsi' => $skripsi,
            'format' => $format,
            'assignment' => $assignment,
            'grade' => $grade,
            'itemScores' => $itemScores,
        ]));
    }

    public function store(Request $request, Skripsi $skripsi): RedirectResponse
    {
        $assignment = $this->findAssignmentOrFail($skripsi);
        $format = $this->resolveSidangSkripsiFormatOrFail($skripsi);
        $format->load(['items' => fn ($query) => $query->orderBy('sort_order')]);

        $rules = [];

        foreach ($format->items as $item) {
            $rules['scores.' . $item->id] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        $validated = $request->validate($rules, [
            'scores.*.required' => 'Semua item penilaian wajib diisi.',
            'scores.*.numeric' => 'Nilai item penilaian harus berupa angka.',
            'scores.*.min' => 'Nilai item penilaian minimal 0.',
            'scores.*.max' => 'Nilai item penilaian maksimal 100.',
        ]);

        $weightedScore = collect($format->items)->sum(function ($item) use ($validated) {
            $score = (float) data_get($validated, 'scores.' . $item->id, 0);
            return $score * ((float) $item->bobot / 100);
        });

        DB::transaction(function () use ($skripsi, $format, $assignment, $validated, $weightedScore): void {
            $grade = Grade::query()->updateOrCreate(
                [
                    'skripsi_id' => $skripsi->id,
                    'format_penilaian_id' => $format->id,
                    'reviewer_id' => Auth::id(),
                    'grade_event' => in_array($skripsi->current_phase, ['sidang_proposal']) ? 'sidang_proposal' : 'sidang_skripsi',
                ],
                [
                    'role_type' => $assignment->role_type,
                    'status' => 'published',
                    'score' => round($weightedScore, 2),
                ]
            );

            foreach ($format->items as $item) {
                $grade->items()->updateOrCreate(
                    ['item_penilaian_id' => $item->id],
                    ['score' => (float) data_get($validated, 'scores.' . $item->id)]
                );
            }
        });

        return redirect()
            ->route('dosen.penilaian.show', $skripsi)
            ->with('success', 'Nilai sidang skripsi berhasil dipublikasikan.');
    }

    private function findAssignmentOrFail(Skripsi $skripsi): ReviewerAssignment
    {
        $assignment = ReviewerAssignment::query()
            ->where('skripsi_id', $skripsi->id)
            ->where('lecturer_id', Auth::id())
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2', 'penguji_1', 'penguji_2'])
            ->first();

        if (! $assignment) {
            throw ValidationException::withMessages([
                'access' => 'Anda tidak ditugaskan sebagai pembimbing atau penguji untuk skripsi ini.',
            ]);
        }

        if (! in_array($skripsi->current_phase, ['sidang_proposal', 'sidang_skripsi', 'revisi_sidang_skripsi'], true)) {
            throw ValidationException::withMessages([
                'phase' => 'Penilaian hanya dapat dilakukan saat fase sidang skripsi.',
            ]);
        }

        return $assignment;
    }

    private function resolveSidangSkripsiFormatOrFail(Skripsi $skripsi): FormatPenilaian
    {
        $format = $this->resolveSidangSkripsiFormat($skripsi);

        if (! $format) {
            throw ValidationException::withMessages([
                'format' => 'Format penilaian sidang skripsi untuk periode ini belum tersedia.',
            ]);
        }

        return $format;
    }

    private function resolveSidangSkripsiFormat(Skripsi $skripsi): ?FormatPenilaian
    {
        $type = in_array($skripsi->current_phase, ['sidang_proposal']) ? 'sidang_proposal' : 'sidang_skripsi';
        return $skripsi->periode?->formats()
            ->where('template_type', $type)
            ->where('is_published', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();
    }

    private function page(string $heading, string $crumbs, array $extra = []): array
    {
        $navigation = app(RoleNavigationService::class);

        return array_merge([
            'title' => $heading,
            'heading' => $heading,
            'crumbs' => $crumbs,
            'navItems' => $navigation->dosenNavItems(),
            'primaryCta' => null,
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'dosen',
        ], $extra);
    }
}
