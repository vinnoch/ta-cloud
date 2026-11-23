<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\FormatPenilaian;
use App\Models\Grade;
use App\Models\ReviewerAssignment;
use App\Models\TugasAkhir;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RoleNavigationService;
use App\Services\TugasAkhirRouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
            ->leftJoin('tugas_akhirs as s_sort', 's_sort.id', '=', 'reviewer_assignments.tugas_akhir_id')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 's_sort.student_id')
            ->leftJoin('periodes as periodes_sort', 'periodes_sort.id', '=', 's_sort.periode_id')
            ->with([
                'tugasAkhir.student',
                'tugasAkhir.periode.tahunAkademik',
                'tugasAkhir.grades' => fn ($query) => $query
                    ->with('items')
                    ->where('reviewer_id', $lecturerId)
                    ->whereIn('grade_event', ['sidang_proposal', 'sidang_skripsi']),
            ])
            ->where('reviewer_assignments.lecturer_id', $lecturerId)
            ->whereIn('reviewer_assignments.role_type', ['pembimbing_1', 'pembimbing_2', 'penguji_1', 'penguji_2'])
            ->whereHas('tugasAkhir', function ($query) use ($nilaiSidang) {
                $query->where(function ($phaseQuery) use ($nilaiSidang) {
                    if ($nilaiSidang === 'sidang_proposal') {
                        $phaseQuery->where('current_phase', 'sidang_proposal');

                        return;
                    }

                    if (in_array($nilaiSidang, ['sidang_skripsi', 'sidang_non_skripsi'], true)) {
                        $phaseQuery->whereIn('current_phase', ['sidang_skripsi', 'revisi_sidang_skripsi'])
                            ->where('type', $nilaiSidang === 'sidang_non_skripsi' ? 'non_skripsi' : 'skripsi');

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
            'periode' => 'periodes_sort.kode_periode',
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
                $skripsi = $assignment->tugasAkhir;
                $gradeEvent = $skripsi ? $this->resolveGradeEvent($skripsi) : 'sidang_skripsi';
                $existingGrade = $skripsi?->grades->firstWhere('grade_event', $gradeEvent);

                if (! $skripsi) {
                    return null;
                }

                $format = $this->resolveSidangSkripsiFormat($skripsi);
                if (! $format) {
                    return null;
                }

                $format->loadMissing(['items' => fn ($query) => $query->orderBy('sort_order')]);
                $itemScores = $existingGrade
                    ? $existingGrade->items->pluck('score', 'item_penilaian_id')->map(fn ($score) => (float) $score)->all()
                    : [];

                return [
                    'skripsi' => $skripsi,
                    'assignment' => $assignment,
                    'format' => $format,
                    'grade' => $existingGrade,
                    'itemScores' => $itemScores,
                    'student' => $skripsi->student?->name ?? '-',
                    'nim' => $skripsi->student?->nim ?? '-',
                    'title' => $skripsi->title ?? 'Tanpa Judul',
                    'date' => $assignment->created_at,
                    'type_label' => $skripsi->type === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi',
                    'fase' => in_array($skripsi->current_phase, ['sidang_proposal']) ? 'Sidang Proposal' : ($skripsi->type === 'non_skripsi' ? 'Sidang Non-Skripsi' : 'Sidang Skripsi'),
                    'role' => str($assignment->role_type)->replace('_', ' ')->title()->toString(),
                    'status' => $existingGrade?->status ?? 'draft',
                    'has_grade' => (bool) $existingGrade,
                    'is_locked' => (bool) $existingGrade?->locked_at,
                    'unlock_requested' => (bool) $existingGrade?->unlock_requested_at,
                    'modal_id' => 'dosen-grade-modal-'.$assignment->id,
                    'href' => route('dosen.penilaian.show', $skripsi),
                    'skripsi_href' => app(TugasAkhirRouteService::class)->route('dosen', $skripsi, 'show', $skripsi),
                    'store_url' => route('dosen.penilaian.store', $skripsi),
                    'unlock_url' => route('dosen.penilaian.request-unlock', $skripsi),
                ];
            })
            ->filter()
            ->values();

        $assignments->setCollection($gradingQueue);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('dosen.penilaian.partials.table', ['gradingQueue' => $assignments, 'sort' => $sort, 'direction' => $direction])->render(),
                'pagination_html' => view('dosen.penilaian.partials.pagination', ['gradingQueue' => $assignments])->render(),
                'count_text' => $assignments->total().' antrian penilaian ditemukan.',
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

    public function show(TugasAkhir $skripsi): View
    {
        $assignment = $this->findAssignmentOrFail($skripsi);
        $format = $this->resolveSidangSkripsiFormatOrFail($skripsi);

        $format->load(['items' => fn ($query) => $query->orderBy('sort_order')]);
        $skripsi->load(['student', 'periode.tahunAkademik']);

        $gradeEvent = $this->resolveGradeEvent($skripsi);

        $grade = Grade::query()
            ->with('items')
            ->where('tugas_akhir_id', $skripsi->id)
            ->where('format_penilaian_id', $format->id)
            ->where('reviewer_id', Auth::id())
            ->where('grade_event', $gradeEvent)
            ->first();

        $itemScores = $grade
            ? $grade->items->pluck('score', 'item_penilaian_id')->map(fn ($score) => (float) $score)->all()
            : [];

        return view('dosen.penilaian.show', $this->page('Form Penilaian dan Revisi Sidang', 'DOSEN • GRADING FORM', [
            'skripsi' => $skripsi,
            'format' => $format,
            'assignment' => $assignment,
            'grade' => $grade,
            'itemScores' => $itemScores,
            'isLocked' => (bool) $grade?->locked_at,
            'unlockRequested' => (bool) $grade?->unlock_requested_at,
        ]));
    }

    public function store(Request $request, TugasAkhir $skripsi): RedirectResponse
    {
        $assignment = $this->findAssignmentOrFail($skripsi);
        $format = $this->resolveSidangSkripsiFormatOrFail($skripsi);
        $format->load(['items' => fn ($query) => $query->orderBy('sort_order')]);

        $rules = [];

        foreach ($format->items as $item) {
            $rules['scores.'.$item->id] = ['required', 'numeric', 'min:0', 'max:100'];
        }
        $rules['notes'] = ['nullable', 'string', 'max:2000'];
        $rules['save_mode'] = ['required', 'in:draft,publish_lock'];

        $validated = $request->validate($rules, [
            'scores.*.required' => 'Semua item penilaian wajib diisi.',
            'scores.*.numeric' => 'Nilai item penilaian harus berupa angka.',
            'scores.*.min' => 'Nilai item penilaian minimal 0.',
            'scores.*.max' => 'Nilai item penilaian maksimal 100.',
        ]);

        $gradeEvent = $this->resolveGradeEvent($skripsi);
        $existingGrade = Grade::query()
            ->where('tugas_akhir_id', $skripsi->id)
            ->where('format_penilaian_id', $format->id)
            ->where('reviewer_id', Auth::id())
            ->where('grade_event', $gradeEvent)
            ->first();

        if ($existingGrade?->locked_at) {
            throw ValidationException::withMessages([
                'locked' => 'Nilai sudah dipublikasikan dan dikunci. Ajukan buka kunci ke Kaprodi untuk melakukan perubahan.',
            ]);
        }

        $weightedScore = collect($format->items)->sum(function ($item) use ($validated) {
            $score = (float) data_get($validated, 'scores.'.$item->id, 0);

            return $score * ((float) $item->bobot / 100);
        });

        $saveMode = (string) $validated['save_mode'];
        $isPublishing = $saveMode === 'publish_lock';

        DB::transaction(function () use ($skripsi, $format, $assignment, $validated, $weightedScore, $gradeEvent): void {
            $grade = Grade::query()->updateOrCreate(
                [
                    'tugas_akhir_id' => $skripsi->id,
                    'format_penilaian_id' => $format->id,
                    'reviewer_id' => Auth::id(),
                    'grade_event' => $gradeEvent,
                ],
                [
                    'role_type' => $assignment->role_type,
                    'status' => $validated['save_mode'] === 'publish_lock' ? 'published' : 'draft',
                    'locked_at' => $validated['save_mode'] === 'publish_lock' ? now() : null,
                    'unlock_requested_at' => null,
                    'score' => round($weightedScore, 2),
                    'notes' => $validated['notes'] ?? null,
                ]
            );

            foreach ($format->items as $item) {
                $grade->items()->updateOrCreate(
                    ['item_penilaian_id' => $item->id],
                    ['score' => (float) data_get($validated, 'scores.'.$item->id)]
                );
            }
        });

        return redirect()->to($request->input('redirect_to', route('dosen.penilaian.show', $skripsi)))
            ->with('success', $isPublishing
                ? 'Nilai sidang berhasil dipublikasikan dan dikunci.'
                : 'Draft nilai berhasil disimpan.');
    }

    public function requestUnlock(TugasAkhir $skripsi, NotificationService $notifications): RedirectResponse
    {
        $assignment = $this->findAssignmentOrFail($skripsi);
        $format = $this->resolveSidangSkripsiFormatOrFail($skripsi);
        $gradeEvent = $this->resolveGradeEvent($skripsi);

        $grade = Grade::query()
            ->where('tugas_akhir_id', $skripsi->id)
            ->where('format_penilaian_id', $format->id)
            ->where('reviewer_id', Auth::id())
            ->where('grade_event', $gradeEvent)
            ->first();

        if (! $grade || ! $grade->locked_at) {
            throw ValidationException::withMessages([
                'locked' => 'Nilai ini belum dikunci.',
            ]);
        }

        if (! $grade->unlock_requested_at) {
            $grade->forceFill([
                'unlock_requested_at' => now(),
            ])->save();

            $kaprodiUsers = User::query()
                ->whereHas('level', fn ($query) => $query->where('users_level', 'kaprodi'))
                ->get();

            if ($kaprodiUsers->isNotEmpty()) {
                $notifications->send($kaprodiUsers, [
                    'title' => 'Permintaan Buka Kunci Nilai',
                    'message' => sprintf(
                        '%s meminta buka kunci nilai %s untuk %s.',
                        Auth::user()?->name ?? 'Dosen',
                        in_array($gradeEvent, ['sidang_proposal'], true) ? 'Sidang Proposal' : 'Sidang Skripsi',
                        $skripsi->student?->name ?? 'mahasiswa'
                    ),
                    'url' => route('kaprodi.formats.grades.show', [$format, $skripsi], false),
                ]);
            }
        }

        return back()->with('success', 'Permintaan buka kunci nilai telah dikirim ke Kaprodi.');
    }

    private function findAssignmentOrFail(TugasAkhir $skripsi): ReviewerAssignment
    {
        $assignment = ReviewerAssignment::query()
            ->where('tugas_akhir_id', $skripsi->id)
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

    private function resolveSidangSkripsiFormatOrFail(TugasAkhir $skripsi): FormatPenilaian
    {
        $format = $this->resolveSidangSkripsiFormat($skripsi);

        if (! $format) {
            throw ValidationException::withMessages([
                'format' => 'Format penilaian sidang skripsi untuk periode ini belum tersedia.',
            ]);
        }

        return $format;
    }

    private function resolveSidangSkripsiFormat(TugasAkhir $skripsi): ?FormatPenilaian
    {
        $type = $this->resolveFormatType($skripsi);

        return $skripsi->periode?->formats()
            ->where('template_type', $type)
            ->where('is_published', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->first();
    }

    private function resolveGradeEvent(TugasAkhir $skripsi): string
    {
        return in_array($skripsi->current_phase, ['sidang_proposal'], true) ? 'sidang_proposal' : 'sidang_skripsi';
    }

    private function resolveFormatType(TugasAkhir $skripsi): string
    {
        if ($this->resolveGradeEvent($skripsi) === 'sidang_proposal') {
            return 'sidang_proposal';
        }

        return $skripsi->type === 'non_skripsi' ? 'sidang_non_skripsi' : 'sidang_skripsi';
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
