<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\DocumentVersion;
use App\Models\SidangRequest;
use App\Models\TugasAkhir;
use App\Services\StudentDocumentPathService;
use App\Services\TugasAkhirRouteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentVersionController extends Controller
{
    public function __construct(private readonly TugasAkhirRouteService $tugasAkhirRoutes) {}

    public function createProposalUpload(Request $request, TugasAkhir $skripsi): RedirectResponse
    {
        if ($skripsi->student_id !== $request->user()->id) {
            abort(403);
        }

        return redirect()->to($this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'show', ['skripsi' => $skripsi, 'openProposalUpload' => '1']));
    }

    public function store(Request $request, TugasAkhir $skripsi, StudentDocumentPathService $documentPathService): JsonResponse|RedirectResponse
    {
        if ($skripsi->student_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'phase' => ['required', 'in:proposal'],
        ]);

        $nextVersion = DocumentVersion::query()
            ->where('tugas_akhir_id', $skripsi->id)
            ->where('phase', $validated['phase'])
            ->max('version_number');

        $nextVersion = ((int) $nextVersion) + 1;

        $file = $validated['file'];
        $path = $file->storeAs('', $documentPathService->buildStoragePath($skripsi->loadMissing('student'), $validated['phase'], $nextVersion, $file), 'local');

        DocumentVersion::query()->create([
            'tugas_akhir_id' => $skripsi->id,
            'phase' => $validated['phase'],
            'version_number' => $nextVersion,
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/pdf',
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        if ($validated['phase'] === 'proposal') {
            $skripsi->update([
                'proposal_review_status' => 'pending',
                'proposal_rejected_at' => null,
            ]);

            SidangRequest::query()->updateOrCreate(
                [
                    'tugas_akhir_id' => $skripsi->id,
                    'role_type' => 'mahasiswa',
                ],
                [
                    'lecturer_id' => $request->user()->id,
                    'status' => 'submitted',
                    'note' => null,
                    'submitted_at' => now(),
                    'approved_at' => null,
                    'approved_by' => null,
                ]
            );
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Dokumen berhasil diupload.',
                'redirect' => $this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'show', $skripsi),
            ]);
        }

        return redirect()->to($this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'show', $skripsi))->with('success', 'Dokumen berhasil diupload.');
    }

    public function showProposalFile(Request $request, TugasAkhir $skripsi, DocumentVersion $document): StreamedResponse
    {
        if ($skripsi->student_id !== $request->user()->id || $document->tugas_akhir_id !== $skripsi->id || $document->phase !== 'proposal') {
            abort(403);
        }

        if (! $document->file_path || ! Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->response(
            $document->file_path,
            basename($document->file_path),
            ['Content-Type' => $document->mime_type ?: 'application/pdf']
        );
    }

    public function destroy(Request $request, TugasAkhir $skripsi, DocumentVersion $document): RedirectResponse
    {
        if ($skripsi->student_id !== $request->user()->id || $document->tugas_akhir_id !== $skripsi->id) {
            abort(403);
        }

        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->to($this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'show', $skripsi))->with('success', 'Dokumen berhasil dihapus.');
    }
}
