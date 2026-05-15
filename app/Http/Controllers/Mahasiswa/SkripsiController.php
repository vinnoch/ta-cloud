<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mahasiswa\FinalSubmissionController;
use App\Models\DocumentVersion;
use App\Models\Skripsi;
use App\Services\MahasiswaSkripsiDataService;
use App\Services\StudentDocumentPathService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SkripsiController extends Controller
{
    public function index(Request $request, MahasiswaSkripsiDataService $skripsiData): View|JsonResponse
    {
        $activeSkripsi = $skripsiData->activeForUser($request->user());

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
                $suggestions = Skripsi::query()
                    ->with('student')
                    ->where('student_id', $request->user()->id)
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
                    ->map(fn (Skripsi $skripsi) => [
                        'id' => $skripsi->id,
                        'student_name' => $skripsi->student?->name,
                        'nim' => $skripsi->student?->nim,
                        'title' => $skripsi->title,
                        'url' => route('mahasiswa.skripsi.show', $skripsi, false),
                    ]);
            }

            return response()->json([
                'suggestions' => $suggestions->values(),
            ]);
        }

        return view('mahasiswa.skripsi.index', [
            'title' => 'Tugas Akhir Saya',
            'heading' => 'Tugas Akhir Saya',
            'crumbs' => 'MAHASISWA • TUGAS AKHIR',
            'activeSkripsi' => $activeSkripsi,
            'latestBimbingans' => $latestBimbingans,
            'reviewers' => $reviewers,
            'documents' => $documents,
            'canManageNonSkripsi' => $activeSkripsi?->type === 'non_skripsi',
            'hasNonSkripsiRecord' => $activeSkripsi?->type === 'non_skripsi' ? $activeSkripsi->nonSkripsiRecord()->exists() : false,
        ]);
    }


    public function search(Request $request, MahasiswaSkripsiDataService $skripsiData): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $suggestions = Skripsi::query()
            ->with('student')
            ->where('student_id', $request->user()->id)
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
            ->map(fn (Skripsi $skripsi) => [
                'id' => $skripsi->id,
                'student_name' => $skripsi->student?->name,
                'nim' => $skripsi->student?->nim,
                'title' => $skripsi->title,
                'url' => route('mahasiswa.skripsi.show', $skripsi, false),
            ]);

        return response()->json([
            'suggestions' => $suggestions->values(),
        ]);
    }

    public function create(Request $request): View
    {
        $periodes = \App\Models\Periode::query()->orderByDesc('id')->get();
        $selectedType = $request->query('type') === 'non_skripsi' ? 'non_skripsi' : 'skripsi';
        $heading = $selectedType === 'non_skripsi' ? 'Buat Non-Skripsi Baru' : 'Buat Skripsi Baru';

        return view('mahasiswa.skripsi.create', [
            'title' => $heading,
            'heading' => $heading,
            'crumbs' => 'MAHASISWA • CREATE',
            'guides' => [
                ['eyebrow' => 'Aturan', 'title' => '1 mahasiswa = 1 tugas akhir aktif', 'description' => 'Sistem tahan pengajuan baru jika masih ada tugas akhir aktif.'],
            ],
            'periodes' => $periodes,
            'selectedType' => $selectedType,
        ]);
    }

    public function store(Request $request, StudentDocumentPathService $documentPathService): RedirectResponse
    {
        $validated = $request->validate([
            'periode_id' => ['required', 'exists:periodes,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:skripsi,non_skripsi'],
            'journal_article_url' => ['nullable', 'url', 'max:500'],
            'proposal_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $skripsi = Skripsi::query()->create([
            'student_id' => $request->user()->id,
            'periode_id' => $validated['periode_id'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'journal_article_url' => $validated['journal_article_url'] ?? null,
            'current_phase' => 'proposal',
        ]);

        if ($request->hasFile('proposal_file')) {
            $file = $request->file('proposal_file');
            $path = $file->storeAs('', $documentPathService->buildStoragePath($skripsi->loadMissing('student'), 'proposal', 1, $file), 'local');

            DocumentVersion::query()->create([
                'skripsi_id' => $skripsi->id,
                'phase' => 'proposal',
                'version_number' => 1,
                'file_path' => $path,
                'mime_type' => 'application/pdf',
                'size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        return redirect()->route('mahasiswa.skripsi.show', $skripsi, false)->with('success', 'Skripsi berhasil dibuat.');
    }

    public function show(Request $request, Skripsi $skripsi, MahasiswaSkripsiDataService $skripsiData): View
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

        return view('mahasiswa.skripsi.show', [
            'skripsi' => $skripsi,
            'proposalVersions' => $proposalVersions,
            'canProposalUpload' => $canProposalUpload,
            'needsProposalUpload' => $needsProposalUpload,
            'proposalUploadUrl' => route('mahasiswa.skripsi.documents.store', $skripsi),
            'openProposalUpload' => $request->boolean('openProposalUpload'),
            'latestBimbingans' => $skripsiData->latestBimbingans($skripsi),
            'reviewers' => $skripsiData->reviewers($skripsi),
            'documents' => $skripsiData->documents($skripsi),
            'proposalFinalSubmission' => FinalSubmissionController::buildSubmissionState($skripsi, 'sidang_proposal'),
            'skripsiFinalSubmission' => FinalSubmissionController::buildSubmissionState($skripsi, 'sidang_skripsi'),
        ]);
    }

    public function edit(Request $request, Skripsi $skripsi): View
    {
        $this->authorizeOwner($request, $skripsi);

        return view('mahasiswa.skripsi.edit', [
            'skripsi' => $skripsi,
            'guides' => [],
        ]);
    }

    public function update(Request $request, Skripsi $skripsi): RedirectResponse
    {
        $this->authorizeOwner($request, $skripsi);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:skripsi,non_skripsi'],
            'journal_article_url' => ['nullable', 'url', 'max:500'],
        ]);

        $skripsi->update($validated);

        return redirect()->route('mahasiswa.skripsi.show', $skripsi, false)->with('success', 'Skripsi berhasil diperbarui.');
    }

    public function destroy(Request $request, Skripsi $skripsi): RedirectResponse
    {
        $this->authorizeOwner($request, $skripsi);
        $skripsi->delete();

        return redirect()->route('mahasiswa.skripsi.index')->with('success', 'Skripsi berhasil dihapus.');
    }

    private function authorizeOwner(Request $request, Skripsi $skripsi): void
    {
        if ($skripsi->student_id !== $request->user()->id) {
            abort(403);
        }
    }
}
