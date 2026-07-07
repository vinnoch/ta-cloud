<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\Bimbingan;
use App\Models\DocumentVersion;
use App\Models\Grade;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SkripsiController extends Controller
{
    


    


    use BuildsKaprodiPage;

    public function exportPage(Request $request): View
    {
        $search = $request->string('q')->toString();
        $status = $request->string('status')->toString();
        $periodeId = (int) $request->integer('periode_id');
        $periodes = \App\Models\Periode::query()
            ->with('tahunAkademik')
            ->orderByDesc('is_aktif')
            ->orderByDesc('kode_periode')
            ->get(['id', 'kode_periode', 'tahun_akademik_id', 'semester', 'is_aktif']);

        return view('kaprodi.skripsi.export', $this->kaprodiPage('Export Skripsi', 'KAPRODI • EXPORT SKRIPSI', [
            'search' => $search,
            'status' => $status,
            'periodeId' => $periodeId,
            'periodes' => $periodes,
        ]));
    }

    public function index(Request $request)
    {
        $scope = trim((string) $request->query('scope', ''));
        if (($request->ajax() || $request->expectsJson()) && $scope === 'reviewer') {
            $query = trim((string) $request->query('q', ''));
            if (mb_strlen($query) < 2) {
                return response()->json([]);
            }

            $lecturers = User::query()
                ->forRole('dosen')
                ->where(function ($builder) use ($query) {
                    $builder->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'email']);

            return response()->json($lecturers->map(fn (User $lecturer) => [
                'id' => $lecturer->id,
                'name' => $lecturer->name,
                'email' => $lecturer->email,
            ])->values());
        }

        $status = $request->string('status')->toString();
        $search = $request->string('q')->toString();
        $sort = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';

        $phaseFilterMap = $this->skripsiPhaseFilterMap();

        $summaryBaseQuery = Skripsi::query()
            ->where('type', 'skripsi')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('student', function ($studentQuery) use ($search) {
                            $studentQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('nim', 'like', "%{$search}%");
                        });
                });
            });

        $baseQuery = Skripsi::query()
            ->where('type', 'skripsi')
            ->with(['student.level', 'periode', 'assignments.lecturer'])
            ->when($status !== '', function ($query) use ($status, $phaseFilterMap) {
                if (isset($phaseFilterMap[$status])) {
                    $query->whereIn(DB::raw('LOWER(current_phase)'), $phaseFilterMap[$status]);
                    return;
                }

                $normalizedStatus = strtolower(str_replace(['-', '_'], ' ', $status));
                $query->whereRaw('LOWER(REPLACE(current_phase, "_", " ")) = ?', [$normalizedStatus]);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('student', function ($studentQuery) use ($search) {
                            $studentQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('nim', 'like', "%{$search}%");
                        });
                });
            });

        $skripsis = (clone $baseQuery)
            ->when(in_array($sort, ['mahasiswa','nim'], true), function ($query) use ($direction, $sort) {
                $query->join('users', 'users.id', '=', 'skripsis.student_id')
                    ->orderBy($sort === 'nim' ? 'users.nim' : 'users.name', $direction)
                    ->select('skripsis.*');
            })
            ->when($sort === 'judul', fn ($query) => $query->orderBy('title', $direction))
            ->when($sort === 'fase', fn ($query) => $query->orderBy('current_phase', $direction))
            ->when(! in_array($sort, ['mahasiswa','nim','judul','fase'], true), fn ($query) => $query->orderByDesc('created_at'))
            ->paginate(10)
            ->withQueryString();

        $summarySource = (clone $summaryBaseQuery)->get(['current_phase'])
            ->map(function ($item) {
                $item->current_phase = strtolower(str_replace('_', ' ', (string) $item->current_phase));
                return $item;
            });

        $chartData = [
            ['label' => 'Proposal', 'value' => $summarySource->whereIn('current_phase', $phaseFilterMap['Proposal'])->count()],
            ['label' => 'Bimbingan Skripsi', 'value' => $summarySource->whereIn('current_phase', $phaseFilterMap['Bimbingan Skripsi'])->count()],
            ['label' => 'Sidang Skripsi', 'value' => $summarySource->whereIn('current_phase', $phaseFilterMap['Sidang Skripsi'])->count()],
            ['label' => 'Skripsi Selesai', 'value' => $summarySource->whereIn('current_phase', $phaseFilterMap['Skripsi Selesai'])->count()],
        ];

        if ($request->ajax() || $request->expectsJson()) {
            $suggestions = (clone $baseQuery)
                ->limit(6)
                ->get()
                ->map(fn (Skripsi $skripsi) => [
                    'id' => $skripsi->id,
                    'student_name' => $skripsi->student?->name,
                    'nim' => $skripsi->student?->nim,
                    'title' => $skripsi->title,
                    'url' => route('kaprodi.skripsi.show', $skripsi, false),
                ])
                ->values();

            return response()->json([
                'table_html' => view('kaprodi.skripsi.partials.table', ['skripsis' => $skripsis, 'sort' => $sort, 'direction' => $direction])->render(),
                'pagination_html' => view('kaprodi.skripsi.partials.pagination', ['skripsis' => $skripsis])->render(),
                'stats_html' => view('kaprodi.skripsi.partials.stats', ['chartData' => $chartData])->render(),
                'count_text' => $skripsis->total() . ' skripsi ditemukan.',
                'suggestions' => $suggestions,
            ]);
        }

        return view('kaprodi.skripsi.index', $this->page('Monitoring Skripsi', 'KAPRODI • SKRIPSI', [
            'skripsis' => $skripsis,
            'chartData' => $chartData,
            'status' => $status,
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]));
    }

    public function show(Skripsi $skripsi): View
    {
        $relations = ['student.level', 'periode', 'documentVersions.uploader', 'finalDocumentApprovals.reviewer'];

        if (Schema::hasTable('reviewer_assignments')) {
            $relations[] = 'assignments.lecturer';
        }

        if (Schema::hasTable('sidang_requests')) {
            $relations[] = 'sidangRequests';

            if (Schema::hasColumns('sidang_requests', ['lecturer_id', 'approved_by'])) {
                $relations[] = 'sidangRequests.lecturer';
                $relations[] = 'sidangRequests.approver';
            }
        }

        $skripsi->load($relations);

        $latestBimbingans = Bimbingan::query()
            ->where('skripsi_id', $skripsi->id)
            ->with(['reviewer'])
            ->orderByDesc('meeting_date')
            ->limit(5)
            ->get();

        $sidangAssignments = $skripsi->assignments
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2', 'penguji_1', 'penguji_2'])
            ->values();

        $finalSidangGrades = Grade::query()
            ->where('skripsi_id', $skripsi->id)
            ->where('grade_event', 'sidang_skripsi')
            ->where('status', 'published')
            ->get()
            ->keyBy(fn (Grade $grade) => $grade->reviewer_id . ':' . $grade->role_type);

        $gradingProgress = [
            'expected_count' => $sidangAssignments->count(),
            'submitted_count' => 0,
            'pending_count' => 0,
            'submitted_reviewers' => [],
            'pending_reviewers' => [],
        ];

        foreach ($sidangAssignments as $assignment) {
            $gradeKey = $assignment->lecturer_id . ':' . $assignment->role_type;
            $reviewerData = [
                'name' => $assignment->lecturer?->name ?? '-',
                'role' => str($assignment->role_type)->replace('_', ' ')->title()->toString(),
            ];

            if ($finalSidangGrades->has($gradeKey)) {
                $gradingProgress['submitted_count']++;
                $gradingProgress['submitted_reviewers'][] = $reviewerData;
            } else {
                $gradingProgress['pending_count']++;
                $gradingProgress['pending_reviewers'][] = $reviewerData;
            }
        }

        $finalReviewDocuments = $skripsi->documentVersions
            ->filter(fn ($document) => in_array($document->phase, ['skripsi_final', 'review_dokumen_final', 'proposal_final'], true))
            ->sortByDesc('created_at')
            ->values();

        $proposalVersions = $skripsi->documentVersions
            ->filter(fn ($document) => $document->phase === 'proposal')
            ->sortByDesc('version_number')
            ->values();

        return view('kaprodi.skripsi.show', $this->page('Detail Skripsi', 'KAPRODI • DETAIL SKRIPSI', [
            'skripsi' => $skripsi,
            'latestBimbingans' => $latestBimbingans,
            'finalReviewDocuments' => $finalReviewDocuments,
            'proposalVersions' => $proposalVersions,

            'reviewerTableHtml' => $this->renderReviewerTable($skripsi),
            'reviewerSearchUrl' => route('kaprodi.skripsi.reviewers.search', $skripsi),
            'reviewerStoreUrl' => route('kaprodi.skripsi.reviewers.store', $skripsi),
            'sidangRequests' => $skripsi->sidangRequests
                ->filter(fn ($request) => in_array($request->role_type, ['pembimbing_1', 'pembimbing_2'], true))
                ->sortBy('role_type')
                ->values(),
            'gradingProgress' => $gradingProgress,
            'journalArticleUrl' => $skripsi->journal_article_url,
            'sidangProposalSchedule' => $skripsi->sidang_proposal_datetime,
            'sidangSkripsiSchedule' => $skripsi->sidang_skripsi_datetime,
        ]));
    }

    public function updateStatus(Request $request, Skripsi $skripsi)
    {
        $validated = $request->validate([
            'current_phase' => ['required', Rule::in(['proposal', 'sidang_proposal', 'bimbingan_skripsi', 'sidang_skripsi', 'revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'])],
        ]);

        $skripsi->update([
            'current_phase' => $validated['current_phase'],
        ]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => 'Fase skripsi berhasil diperbarui.',
                'current_phase' => str($skripsi->current_phase)->replace(['_', '-'], ' ')->title()->toString(),
            ]);
        }

        return back()->with('success', 'Fase skripsi berhasil diperbarui.');
    }

    public function updateSidangProposalSchedule(Request $request, Skripsi $skripsi, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        return $this->storeSidangSchedule($request, $skripsi, $notifications, 'proposal');
    }

    public function updateSidangSchedule(Request $request, Skripsi $skripsi, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        return $this->storeSidangSchedule($request, $skripsi, $notifications, 'skripsi');
    }

    public function showProposal(Skripsi $skripsi): View
    {
        $skripsi->load([
            'student.level',
            'periode',
            'assignments.lecturer',
            'documentVersions' => fn ($query) => $query->where('phase', 'proposal')->orderByDesc('version_number'),
        ]);

        return view('kaprodi.skripsi.proposal', $this->page('Proposal Skripsi', 'KAPRODI • PROPOSAL', [
            'skripsi' => $skripsi,
            'reviewerTableHtml' => $this->renderReviewerTable($skripsi),
            'reviewerSearchUrl' => route('kaprodi.skripsi.reviewers.search', $skripsi),
            'reviewerStoreUrl' => route('kaprodi.skripsi.reviewers.store', $skripsi),
        ]));
    }

    public function showBimbingan(Skripsi $skripsi): View
    {
        $skripsi->load('student.level');
        $bimbingans = Bimbingan::query()
            ->where('skripsi_id', $skripsi->id)
            ->orderByDesc('meeting_date')
            ->with(['reviewer'])
            ->get();

        return view('kaprodi.skripsi.bimbingan', $this->page('Histori Bimbingan', 'KAPRODI • BIMBINGAN', [
            'skripsi' => $skripsi,
            'bimbingans' => $bimbingans,
        ]));
    }

    public function showBimbinganItem(Skripsi $skripsi, Bimbingan $bimbingan): View
    {
        abort_unless($bimbingan->skripsi_id === $skripsi->id, 404);
        $bimbingan->load(['reviewer']);
        $skripsi->load('student.level');

        return view('kaprodi.skripsi.bimbingan-show', $this->page('Detail Histori Bimbingan', 'KAPRODI • DETAIL BIMBINGAN', [
            'skripsi' => $skripsi,
            'bimbingan' => $bimbingan,
        ]));
    }

    public function searchReviewers(Request $request, Skripsi $skripsi)
    {
        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $lecturers = User::query()
            ->forRole('dosen')
            ->where(function ($builder) use ($query) {
                $builder->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'email']);

        return response()->json($lecturers->map(fn (User $lecturer) => [
            'id' => $lecturer->id,
            'name' => $lecturer->name,
            'email' => $lecturer->email,
        ])->values());
    }

    public function storeReviewer(Request $request, Skripsi $skripsi, NotificationService $notifications)
    {
        $data = $request->validate([
            'lecturer_id' => ['required', 'exists:users,id'],
            'role_type' => ['required', 'in:pembimbing_1,pembimbing_2,penguji,penguji_1,penguji_2'],
        ]);

        $normalizedRole = $data['role_type'] === 'penguji' ? 'penguji_1' : $data['role_type'];
        $user = User::findOrFail($data['lecturer_id']);

        if ($user->role !== 'dosen') {
            throw ValidationException::withMessages([
                'lecturer_id' => 'Hanya dosen yang dapat menjadi reviewer.',
            ]);
        }

        $existingAssignment = $skripsi->assignments()
            ->where('lecturer_id', $data['lecturer_id'])
            ->where('role_type', '!=', $normalizedRole)
            ->first();

        if ($existingAssignment) {
            throw ValidationException::withMessages([
                'lecturer_id' => 'Dosen ini sudah ditetapkan sebagai ' . str_replace('_', ' ', $existingAssignment->role_type) . '.',
            ]);
        }

        $assignment = $skripsi->assignments()->updateOrCreate(
            ['role_type' => $normalizedRole],
            ['lecturer_id' => $data['lecturer_id']]
        );

        $assignment->load(['lecturer', 'skripsi.student']);
        $notifications->send([$assignment->lecturer], [
            'type' => 'reviewer_assigned',
            'title' => 'Penugasan dosen baru',
            'message' => 'Anda ditugaskan sebagai ' . str_replace('_', ' ', $assignment->role_type) . ' untuk ' . $assignment->skripsi->student->name . ': ' . $assignment->skripsi->title,
            'url' => route('dosen.skripsi.show', ['skripsi' => $assignment->skripsi->id], false),
            'actor' => $request->user()->name,
            'meta' => [
                'skripsi_id' => $assignment->skripsi->id,
                'role_type' => $assignment->role_type,
                'student_name' => $assignment->skripsi->student->name,
            ],
        ]);
        $notifications->send([$assignment->skripsi->student], [
            'type' => 'reviewer_assigned',
            'title' => 'Reviewer ditetapkan',
            'message' => $assignment->lecturer->name . ' ditetapkan sebagai ' . str_replace('_', ' ', $assignment->role_type) . ' untuk tugas akhir Anda.',
            'url' => route('mahasiswa.skripsi.show', ['skripsi' => $assignment->skripsi->id], false),
            'actor' => $request->user()->name,
            'meta' => [
                'skripsi_id' => $assignment->skripsi->id,
                'role_type' => $assignment->role_type,
                'lecturer_name' => $assignment->lecturer->name,
            ],
        ]);

        $skripsi->load('assignments.lecturer');

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => 'Reviewer berhasil ditetapkan.',
                'reviewers_html' => $this->renderReviewerTable($skripsi),
            ]);
        }

        return back()->with('success', 'Reviewer berhasil ditetapkan.');
    }

    public function assignPembimbing(Request $request, Skripsi $skripsi): RedirectResponse
    {
        $request->merge(['role_type' => $request->input('role_type', 'pembimbing_2')]);

        $this->storeReviewer($request, $skripsi);

        return back()->with('success', 'Pembimbing berhasil ditetapkan.');
    }


    public function assignPenguji(Request $request, Skripsi $skripsi): RedirectResponse
    {
        $request->merge(['role_type' => $request->input('role_type', 'penguji_1')]);

        $this->storeReviewer($request, $skripsi);

        return back()->with('success', 'Penguji berhasil ditetapkan.');
    }

    public function unassignReviewer(Request $request, Skripsi $skripsi, $assignment)
    {
        $assignment = $skripsi->assignments()->findOrFail($assignment);
        $assignment->delete();
        $skripsi->load('assignments.lecturer');

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => 'Reviewer berhasil di-unassign.',
                'reviewers_html' => $this->renderReviewerTable($skripsi),
            ]);
        }

        return back()->with('success', 'Reviewer berhasil di-unassign.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $search = $request->string('q')->toString();
        $status = $request->string('status')->toString();
        $periodeId = (int) $request->integer('periode_id');

        $phaseFilterMap = $this->skripsiPhaseFilterMap();

        $skripsis = Skripsi::query()
            ->where('type', 'skripsi')
            ->with([
                'student:id,name,nim',
                'periode:id,tahun_akademik_id,kode_periode,semester',
                'periode.tahunAkademik:id,tahun_awal,tahun_akhir',
            ])
            ->when($periodeId > 0, fn ($query) => $query->where('periode_id', $periodeId))
            ->when($status !== '', function ($query) use ($status, $phaseFilterMap) {
                if (isset($phaseFilterMap[$status])) {
                    $query->whereIn(DB::raw('LOWER(current_phase)'), $phaseFilterMap[$status]);
                    return;
                }

                $normalizedStatus = strtolower(str_replace(['-', '_'], ' ', $status));
                $query->whereRaw('LOWER(REPLACE(current_phase, "_", " ")) = ?', [$normalizedStatus]);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('title', 'like', "%{$search}%")
                        ->orWhereHas('student', function ($studentQuery) use ($search) {
                            $studentQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('nim', 'like', "%{$search}%");
                        });
                });
            })
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="rekap_skripsi_' . now()->format('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($skripsis) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['NIM', 'Nama Mahasiswa', 'Judul Skripsi', 'Tipe', 'Fase Saat Ini', 'Periode']);

            foreach ($skripsis as $skripsi) {
                fputcsv($handle, [
                    $skripsi->student?->nim,
                    $skripsi->student?->name,
                    $skripsi->title,
                    $skripsi->type,
                    str($skripsi->current_phase)->replace('_', ' ')->title(),
                    $skripsi->periode?->name,
                ]);
            }

            fclose($handle);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    private function storeSidangSchedule(Request $request, Skripsi $skripsi, NotificationService $notifications, string $mode): RedirectResponse|JsonResponse
    {
        $isProposal = $mode === 'proposal';
        $field = $isProposal ? 'sidang_proposal_datetime' : 'sidang_skripsi_datetime';
        $notifiedField = $isProposal ? 'sidang_proposal_grade_notified_at' : 'sidang_skripsi_grade_notified_at';
        $label = $isProposal ? 'Sidang Proposal' : 'Sidang Skripsi';
        $notificationType = $isProposal ? 'sidang_proposal_scheduled' : 'sidang_skripsi_scheduled';
        $allowedRoles = $isProposal ? ['pembimbing_1', 'pembimbing_2'] : ['pembimbing_1', 'pembimbing_2', 'penguji_1', 'penguji_2'];

        $validated = $request->validate([
            $field => ['required', 'date', 'after_or_equal:now'],
        ]);

        $scheduledAt = Carbon::parse($validated[$field]);
        $skripsi->loadMissing(['student', 'assignments.lecturer']);

        $lecturerIds = $skripsi->assignments
            ->whereIn('role_type', $allowedRoles)
            ->pluck('lecturer_id')
            ->filter()
            ->unique()
            ->values();

        $conflictingSchedule = Skripsi::query()
            ->whereKeyNot($skripsi->id)
            ->whereNotNull($field)
            ->whereBetween($field, [
                $scheduledAt->copy()->subHours(2),
                $scheduledAt->copy()->addHours(2),
            ])
            ->whereHas('assignments', fn ($query) => $query->whereIn('lecturer_id', $lecturerIds))
            ->with(['student:id,name,nim'])
            ->first();

        if ($conflictingSchedule) {
            $message = 'Jadwal sidang bentrok dengan ' . ($conflictingSchedule->student?->name ?? 'mahasiswa lain') . ' pada ' . optional($conflictingSchedule->{$field})->format('d/m/Y H:i') . '.';

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors([$field => $message])->withInput();
        }

        $skripsi->update([
            $field => $scheduledAt,
            $notifiedField => null,
        ]);

        $formattedDate = $scheduledAt->translatedFormat('d M Y');
        $formattedTime = $scheduledAt->format('H:i');
        $student = $skripsi->student;
        $lecturers = $skripsi->assignments
            ->whereIn('role_type', $allowedRoles)
            ->pluck('lecturer')
            ->filter()
            ->values();

        try {
            if ($student) {
                $notifications->send([$student], [
                    'type' => $notificationType,
                    'title' => 'Jadwal ' . $label . ' Ditetapkan',
                    'message' => $label . ' untuk "' . $skripsi->title . '" dijadwalkan pada ' . $formattedDate . ' pukul ' . $formattedTime . '.',
                    'url' => route('mahasiswa.skripsi.show', $skripsi, false),
                    'meta' => [
                        'skripsi_id' => $skripsi->id,
                        'scheduled_at' => $scheduledAt->toIso8601String(),
                    ],
                ]);
            }

            if ($lecturers->isNotEmpty()) {
                $notifications->send($lecturers, [
                    'type' => $notificationType,
                    'title' => 'Jadwal ' . $label . ' Ditetapkan',
                    'message' => $label . ' ' . ($student?->name ?? 'mahasiswa') . ' dijadwalkan pada ' . $formattedDate . ' pukul ' . $formattedTime . '.',
                    'url' => route('dosen.skripsi.show', $skripsi, false),
                    'meta' => [
                        'skripsi_id' => $skripsi->id,
                        'scheduled_at' => $scheduledAt->toIso8601String(),
                    ],
                ]);
            }
        } catch (\Throwable $exception) {
            report($exception);
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'message' => 'Jadwal ' . strtolower($label) . ' berhasil disimpan.',
                'scheduled_at' => $scheduledAt->toIso8601String(),
                'formatted' => $scheduledAt->translatedFormat('d M Y H:i'),
            ]);
        }

        return back()->with('success', 'Jadwal ' . strtolower($label) . ' berhasil disimpan.');
    }

    private function renderReviewerTable(Skripsi $skripsi): string
    {
        $skripsi->loadMissing('assignments.lecturer');

        return view('kaprodi.skripsi.partials.reviewer-table', [
            'skripsi' => $skripsi,
        ])->render();
    }

    private function skripsiPhaseFilterMap(): array
    {
        return [
            'Proposal' => ['proposal', 'sidang proposal', 'sidang_proposal'],
            'Sidang Proposal' => ['sidang proposal', 'sidang_proposal'],
            'Bimbingan Skripsi' => ['bimbingan skripsi', 'bimbingan_skripsi'],
            'Sidang Skripsi' => ['sidang skripsi', 'sidang_skripsi', 'revisi sidang skripsi', 'revisi_sidang_skripsi', 'review dokumen final', 'review_dokumen_final'],
            'Revisi Sidang Skripsi' => ['revisi sidang skripsi', 'revisi_sidang_skripsi'],
            'Review Dokumen Final' => ['review dokumen final', 'review_dokumen_final'],
            'Skripsi Selesai' => ['skripsi selesai', 'skripsi_selesai'],
        ];
    }

    public function downloadLogbook(Skripsi $skripsi): StreamedResponse
    {
        $skripsi->loadMissing('student');
        $studentLabel = trim((string) ($skripsi->student?->nim ?: $skripsi->student?->name ?: 'mahasiswa'));

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="logbook_' . Str::slug($studentLabel, '_') . '.csv"',
        ];

        $callback = function () use ($skripsi) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tanggal', 'Phase', 'Lecturer', 'Catatan Dosen', 'Versi Dokumen']);

            $bimbingans = Bimbingan::query()
                ->where('skripsi_id', $skripsi->id)
                ->with(['reviewer'])
                ->orderBy('meeting_date')
                ->get();

            foreach ($bimbingans as $bimbingan) {
                fputcsv($handle, [
                    $bimbingan->meeting_date,
                    $bimbingan->phase,
                    $bimbingan->reviewer?->name,
                    $bimbingan->lecturer_notes,
                    $bimbingan->revision_file_url ?? '-',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadDocument(Skripsi $skripsi, DocumentVersion $document): StreamedResponse
    {
        abort_unless($document->skripsi_id === $skripsi->id, 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, basename($document->file_path));
    }

    private function page(string $heading, string $crumbs, array $extra = []): array
    {
        return $this->kaprodiPage($heading, $crumbs, $extra);
    }
}
