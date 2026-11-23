<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\DocumentSubmission;
use App\Models\DocumentVersion;
use App\Models\Periode;
use App\Models\TugasAkhir;
use App\Models\User;
use App\Services\MahasiswaSkripsiDataService;
use App\Services\NotificationService;
use App\Services\StudentDocumentPathService;
use App\Services\TugasAkhirRouteService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TugasAkhirController extends Controller
{
    public function __construct(private readonly TugasAkhirRouteService $tugasAkhirRoutes) {}

    public function index(Request $request, MahasiswaSkripsiDataService $skripsiData): View|JsonResponse
    {
        $type = $this->tugasAkhirRoutes->typeFromRequest($request);
        $label = $this->tugasAkhirRoutes->label($type);
        $activeSkripsi = $skripsiData->activeForUser($request->user(), $type);

        $latestBimbingans = collect();
        $reviewers = collect();
        $documents = collect();

        if ($activeSkripsi) {
            $latestBimbingans = $skripsiData->latestBimbingans($activeSkripsi);
            $reviewers = $skripsiData->reviewers($activeSkripsi);
            $documents = $skripsiData->documents($activeSkripsi);
        }

        if ($request->ajax() || $request->expectsJson()) {
            $query = trim((string) $request->query('q', ''));

            $suggestions = collect();
            if (mb_strlen($query) >= 2) {
                $suggestions = TugasAkhir::query()
                    ->with('student')
                    ->where('student_id', $request->user()->id)
                    ->where('type', $type)
                    ->where(function ($builder) use ($query): void {
                        $builder->where('title', 'like', "%{$query}%")
                            ->orWhereHas('student', function ($studentQuery) use ($query): void {
                                $studentQuery->where('name', 'like', "%{$query}%")
                                    ->orWhere('nim', 'like', "%{$query}%");
                            });
                    })
                    ->orderByDesc('id')
                    ->limit(6)
                    ->get()
                    ->map(fn (TugasAkhir $skripsi) => [
                        'id' => $skripsi->id,
                        'student_name' => $skripsi->student?->name,
                        'nim' => $skripsi->student?->nim,
                        'title' => $skripsi->title,
                        'url' => $this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'show', $skripsi, false),
                    ]);
            }

            return response()->json([
                'suggestions' => $suggestions->values(),
            ]);
        }

        return view('mahasiswa.skripsi.index', [
            'title' => $label.' Saya',
            'heading' => $label.' Saya',
            'crumbs' => 'MAHASISWA • '.mb_strtoupper($label),
            'activeSkripsi' => $activeSkripsi,
            'latestBimbingans' => $latestBimbingans,
            'reviewers' => $reviewers,
            'documents' => $documents,
            'completedSkripsi' => ! $activeSkripsi
                ? TugasAkhir::query()->where('student_id', $request->user()->id)->where('type', $type)->where('current_phase', 'skripsi_selesai')->latest()->first()
                : null,
            'tugasAkhirType' => $type,
            'tugasAkhirLabel' => $label,
        ]);
    }

    public function search(Request $request, MahasiswaSkripsiDataService $skripsiData): JsonResponse
    {
        $type = $this->tugasAkhirRoutes->typeFromRequest($request);
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = TugasAkhir::query()
            ->with('student')
            ->where('student_id', $request->user()->id)
            ->where('type', $type)
            ->where(function ($builder) use ($query): void {
                $builder->where('title', 'like', "%{$query}%")
                    ->orWhereHas('student', function ($studentQuery) use ($query): void {
                        $studentQuery->where('name', 'like', "%{$query}%")
                            ->orWhere('nim', 'like', "%{$query}%");
                    });
            })
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn (TugasAkhir $skripsi) => [
                'id' => $skripsi->id,
                'student_name' => $skripsi->student?->name,
                'nim' => $skripsi->student?->nim,
                'title' => $skripsi->title,
                'url' => $this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'show', $skripsi, false),
            ]);

        return response()->json([
            'suggestions' => $suggestions->values(),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($request->routeIs('mahasiswa.pendaftaran.create')) {
            $existing = TugasAkhir::query()
                ->where('student_id', $request->user()->id)
                ->latest('id')
                ->first();

            if ($existing) {
                return redirect()->to($this->tugasAkhirRoutes->route('mahasiswa', $existing, 'index'));
            }
        }

        $periodes = Periode::query()->orderByDesc('id')->get();
        $isUnifiedRegistration = $request->routeIs('mahasiswa.pendaftaran.create');
        $selectedType = $isUnifiedRegistration
            ? (string) old('type', $request->query('type', 'skripsi'))
            : $this->tugasAkhirRoutes->typeFromRequest($request);
        $selectedType = in_array($selectedType, [TugasAkhirRouteService::SKRIPSI, TugasAkhirRouteService::NON_SKRIPSI], true)
            ? $selectedType
            : TugasAkhirRouteService::SKRIPSI;
        $heading = $isUnifiedRegistration
            ? 'Pendaftaran Tugas Akhir'
            : ($selectedType === TugasAkhirRouteService::NON_SKRIPSI ? 'Buat Non-Skripsi Baru' : 'Buat Skripsi Baru');

        return view('mahasiswa.skripsi.create', [
            'title' => $heading,
            'heading' => $heading,
            'crumbs' => 'MAHASISWA • CREATE',
            'guides' => [
                ['eyebrow' => 'Skripsi', 'title' => 'Penelitian ilmiah', 'description' => 'Menjelaskan, menguji, mengevaluasi, membandingkan, memprediksi, atau mengembangkan pengetahuan.'],
                ['eyebrow' => 'Non-Skripsi', 'title' => 'Karya atau proyek', 'description' => 'Menjawab kebutuhan pengguna atau proses bisnis melalui karya yang tetap dievaluasi.'],
            ],
            'periodes' => $periodes,
            'selectedType' => $selectedType,
            'isUnifiedRegistration' => $isUnifiedRegistration,
        ]);
    }

    public function store(Request $request, StudentDocumentPathService $documentPathService, NotificationService $notifications): RedirectResponse
    {
        $isDraft = $request->input('save_mode', 'draft') !== 'published';
        $rules = [
            'periode_id' => ['required', 'exists:periodes,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => [$request->routeIs('mahasiswa.pendaftaran.store') ? 'required' : 'nullable', 'in:skripsi,non_skripsi'],
            'journal_article_url' => ['nullable', 'url', 'max:500'],
            'proposal_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'save_mode' => ['nullable', 'in:draft,published'],
        ];
        if (! $isDraft) {
            $rules['understands_tugas_akhir_types'] = ['required', 'accepted'];
        }
        $validated = $request->validate($rules);
        $type = $request->routeIs('mahasiswa.pendaftaran.store')
            ? $validated['type']
            : $this->tugasAkhirRoutes->typeFromRequest($request);
        $validated['type'] = $type;

        $existingSkripsi = TugasAkhir::query()
            ->where('student_id', $request->user()->id)
            ->latest('id')
            ->first();

        if ($existingSkripsi !== null) {
            return redirect()
                ->to($this->tugasAkhirRoutes->route('mahasiswa', $existingSkripsi, 'show', $existingSkripsi))
                ->with('error', 'Anda sudah memiliki tugas akhir. Gunakan data yang sudah ada.');
        }

        try {
            $skripsi = TugasAkhir::query()->create([
                'student_id' => $request->user()->id,
                'periode_id' => $validated['periode_id'],
                'title' => $validated['title'],
                'type' => $validated['type'],
                'journal_article_url' => $validated['journal_article_url'] ?? null,
                'current_phase' => 'proposal',
                'proposal_review_status' => $isDraft ? 'draft' : 'published',
            ]);
        } catch (QueryException $exception) {
            if ((int) $exception->getCode() === 23000) {
                $existingSkripsi = TugasAkhir::query()
                    ->where('student_id', $request->user()->id)
                    ->latest('id')
                    ->first();

                if ($existingSkripsi !== null) {
                    return redirect()
                        ->to($this->tugasAkhirRoutes->route('mahasiswa', $existingSkripsi, 'show', $existingSkripsi))
                        ->with('error', 'Anda sudah memiliki tugas akhir. Gunakan data yang sudah ada.');
                }
            }

            throw $exception;
        }

        $hasProposalFile = $request->hasFile('proposal_file');

        if ($hasProposalFile) {
            $file = $request->file('proposal_file');
            $path = $file->storeAs('', $documentPathService->buildStoragePath($skripsi->loadMissing('student'), 'proposal', 1, $file), 'local');

            DocumentVersion::query()->create([
                'tugas_akhir_id' => $skripsi->id,
                'phase' => 'proposal',
                'version_number' => 1,
                'file_path' => $path,
                'mime_type' => 'application/pdf',
                'size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        $kaprodiUsers = User::query()->forRole('kaprodi')->get();

        if ($hasProposalFile && ! $isDraft && $kaprodiUsers->isNotEmpty()) {
            $notifications->send($kaprodiUsers, [
                'type' => 'proposal_submitted',
                'title' => 'Pengajuan proposal baru',
                'message' => $request->user()->name.' mengajukan proposal '.$this->tugasAkhirRoutes->label($skripsi->type).': '.$skripsi->title,
                'url' => $this->tugasAkhirRoutes->route('kaprodi', $skripsi, 'show', $skripsi, false),
                'actor' => $request->user()->name,
                'meta' => [
                    'tugas_akhir_id' => $skripsi->id,
                    'phase' => 'proposal',
                ],
            ]);
        }

        return redirect()->to($this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'index'))->with('success', $this->tugasAkhirRoutes->label($type).' berhasil dibuat.');
    }

    public function publish(Request $request, TugasAkhir $skripsi, NotificationService $notifications): RedirectResponse
    {
        $this->authorizeOwner($request, $skripsi);
        $request->validate([
            'understands_tugas_akhir_types' => ['required', 'accepted'],
        ]);

        if ($skripsi->isDraft()) {
            $skripsi->update([
                'proposal_review_status' => 'published',
            ]);

            $kaprodiUsers = User::query()->forRole('kaprodi')->get();

            if ($kaprodiUsers->isNotEmpty()) {
                $notifications->send($kaprodiUsers, [
                    'type' => 'proposal_submitted',
                    'title' => 'Pengajuan proposal baru',
                    'message' => $skripsi->student->name.' mengajukan proposal '.$this->tugasAkhirRoutes->label($skripsi->type).': '.$skripsi->title,
                    'url' => $this->tugasAkhirRoutes->route('kaprodi', $skripsi, 'show', $skripsi, false),
                    'actor' => $skripsi->student->name,
                    'meta' => [
                        'tugas_akhir_id' => $skripsi->id,
                        'phase' => 'proposal',
                    ],
                ]);
            }

            return redirect()->to($this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'index'))->with('success', 'Pengajuan proposal berhasil dikirim.');
        }

        return back()->with('error', 'Skripsi ini tidak dalam status draft proposal.');
    }

    public function show(Request $request, TugasAkhir $skripsi, MahasiswaSkripsiDataService $skripsiData): View
    {
        $this->authorizeOwner($request, $skripsi);

        $skripsi->load(['student', 'periode', 'assignments.lecturer', 'bimbingans.reviewer', 'documentVersions.uploader']);

        $proposalVersions = $skripsi->documentVersions()
            ->where('phase', 'proposal')
            ->orderByDesc('version_number')
            ->get();

        $canProposalUpload = $skripsi->current_phase === 'proposal'
            && $skripsi->proposal_review_status !== 'approved';

        $needsProposalUpload = $canProposalUpload && $proposalVersions->isEmpty();
        $dokumenFinalVisible = in_array($skripsi->current_phase, ['revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'], true);
        $dokumenFinalStatus = null;

        if ($dokumenFinalVisible) {
            $hasDocumentSubmissions = DocumentSubmission::query()
                ->where('tugas_akhir_id', $skripsi->id)
                ->exists();

            $dokumenFinalStatus = [
                'label' => $skripsi->current_phase === 'skripsi_selesai' ? 'Approved' : 'Sedang Dicek',
                'description' => $hasDocumentSubmissions
                    ? ($skripsi->current_phase === 'skripsi_selesai'
                        ? 'Dokumen final sudah divalidasi Kaprodi.'
                        : 'Dokumen final sedang divalidasi Kaprodi.')
                    : 'Belum ada dokumen final yang dikirim.',
                'href' => $this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'final.skripsi.index', $skripsi),
            ];
        }

        return view('mahasiswa.skripsi.show', [
            'skripsi' => $skripsi,
            'proposalVersions' => $proposalVersions,
            'canProposalUpload' => $canProposalUpload,
            'needsProposalUpload' => $needsProposalUpload,
            'proposalUploadUrl' => $this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'documents.store', $skripsi),
            'openProposalUpload' => $request->boolean('openProposalUpload'),
            'latestBimbingans' => $skripsiData->latestBimbingans($skripsi),
            'reviewers' => $skripsiData->reviewers($skripsi),
            'documents' => $skripsiData->documents($skripsi),
            'proposalFinalSubmission' => FinalSubmissionController::buildSubmissionState($skripsi, 'sidang_proposal'),
            'skripsiFinalSubmission' => FinalSubmissionController::buildSubmissionState($skripsi, 'sidang_skripsi'),
            'dokumenFinalVisible' => $dokumenFinalVisible,
            'dokumenFinalStatus' => $dokumenFinalStatus,
            'sidangProposalSchedule' => $skripsi->sidang_proposal_datetime,
            'sidangSkripsiSchedule' => $skripsi->sidang_tugas_akhir_datetime,
            'tugasAkhirType' => $skripsi->type,
            'tugasAkhirLabel' => $this->tugasAkhirRoutes->label($skripsi->type),
        ]);
    }

    public function edit(Request $request, TugasAkhir $skripsi): View
    {
        $this->authorizeOwner($request, $skripsi);
        abort_unless($skripsi->isDraft(), 403, 'Proposal yang sudah diajukan tidak dapat diedit.');

        return view('mahasiswa.skripsi.edit', [
            'skripsi' => $skripsi,
            'guides' => [],
        ]);
    }

    public function update(Request $request, TugasAkhir $skripsi, StudentDocumentPathService $documentPathService, NotificationService $notifications): RedirectResponse
    {
        $this->authorizeOwner($request, $skripsi);
        abort_unless($skripsi->isDraft(), 403, 'Proposal yang sudah diajukan tidak dapat diedit.');

        $publishAfterSave = $request->input('save_mode', 'draft') === 'published';
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'in:skripsi,non_skripsi'],
            'journal_article_url' => ['nullable', 'url', 'max:500'],
            'proposal_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'save_mode' => ['nullable', 'in:draft,published'],
        ];
        if ($publishAfterSave) {
            $rules['understands_tugas_akhir_types'] = ['required', 'accepted'];
        }
        $validated = $request->validate($rules);
        $validated['type'] = $this->tugasAkhirRoutes->typeFromRequest($request);

        $skripsi->update($validated);

        if ($skripsi->proposal_review_status === 'draft' && $request->hasFile('proposal_file')) {
            $file = $request->file('proposal_file');
            $existingVersion = $skripsi->documentVersions()
                ->where('phase', 'proposal')
                ->orderBy('version_number')
                ->first();

            $path = $file->storeAs('', $documentPathService->buildStoragePath($skripsi->loadMissing('student'), 'proposal', 1, $file), 'local');

            if ($existingVersion) {
                $existingVersion->update([
                    'file_path' => $path,
                    'mime_type' => 'application/pdf',
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            } else {
                DocumentVersion::query()->create([
                    'tugas_akhir_id' => $skripsi->id,
                    'phase' => 'proposal',
                    'version_number' => 1,
                    'file_path' => $path,
                    'mime_type' => 'application/pdf',
                    'size' => $file->getSize(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }
        }

        if ($publishAfterSave && $skripsi->isDraft()) {
            $skripsi->update([
                'proposal_review_status' => 'published',
            ]);

            $kaprodiUsers = User::query()->forRole('kaprodi')->get();

            if ($kaprodiUsers->isNotEmpty()) {
                $notifications->send($kaprodiUsers, [
                    'type' => 'proposal_submitted',
                    'title' => 'Pengajuan proposal baru',
                    'message' => $skripsi->student->name.' mengajukan proposal '.$this->tugasAkhirRoutes->label($skripsi->type).': '.$skripsi->title,
                    'url' => $this->tugasAkhirRoutes->route('kaprodi', $skripsi, 'show', $skripsi, false),
                    'actor' => $skripsi->student->name,
                    'meta' => [
                        'tugas_akhir_id' => $skripsi->id,
                        'phase' => 'proposal',
                    ],
                ]);
            }

            return redirect()->to($this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'index'))->with('success', 'Pengajuan proposal berhasil dikirim.');
        }

        return redirect()->to($this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'index'))->with('success', 'Proposal draft berhasil diperbarui.');
    }

    public function destroy(Request $request, TugasAkhir $skripsi): RedirectResponse
    {
        $this->authorizeOwner($request, $skripsi);
        $indexUrl = $this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'index');
        $skripsi->delete();

        return redirect()->to($indexUrl)->with('success', 'Tugas akhir berhasil dihapus.');
    }

    private function authorizeOwner(Request $request, TugasAkhir $skripsi): void
    {
        if ($skripsi->student_id !== $request->user()->id) {
            abort(403);
        }
    }
}
