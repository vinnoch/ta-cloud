<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\DocumentVersion;
use App\Models\Skripsi;
use App\Models\SidangRequest;
use App\Services\StudentDocumentPathService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentVersionController extends Controller
{
    public function createProposalUpload(Request $request, Skripsi $skripsi): RedirectResponse
    {
        if ($skripsi->student_id !== $request->user()->id) {
            abort(403);
        }

        return redirect()->route('mahasiswa.skripsi.show', ['skripsi' => $skripsi, 'openProposalUpload' => '1']);
    }

    public function store(Request $request, Skripsi $skripsi, StudentDocumentPathService $documentPathService): JsonResponse|RedirectResponse
    {
        if ($skripsi->student_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'phase' => ['required', 'string', 'max:50'],
        ]);

        $nextVersion = DocumentVersion::query()
            ->where('skripsi_id', $skripsi->id)
            ->where('phase', $validated['phase'])
            ->max('version_number');

        $nextVersion = ((int) $nextVersion) + 1;

        $file = $validated['file'];
        $path = $file->storeAs('', $documentPathService->buildStoragePath($skripsi->loadMissing('student'), $validated['phase'], $nextVersion, $file), 'local');

        DocumentVersion::query()->create([
            'skripsi_id' => $skripsi->id,
            'phase' => $validated['phase'],
            'version_number' => $nextVersion,
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/pdf',
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        if ($validated['phase'] === 'proposal') {
            SidangRequest::query()->updateOrCreate(
                [
                    'skripsi_id' => $skripsi->id,
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
                'redirect' => route('mahasiswa.skripsi.show', $skripsi),
            ]);
        }

        return redirect()->route('mahasiswa.skripsi.show', $skripsi)->with('success', 'Dokumen berhasil diupload.');
    }


    public function showProposalFile(Request $request, Skripsi $skripsi, DocumentVersion $document): StreamedResponse
    {
        if ($skripsi->student_id !== $request->user()->id || $document->skripsi_id !== $skripsi->id || $document->phase !== 'proposal') {
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

    public function destroy(Request $request, Skripsi $skripsi, DocumentVersion $document): RedirectResponse
    {
        if ($skripsi->student_id !== $request->user()->id || $document->skripsi_id !== $skripsi->id) {
            abort(403);
        }

        if ($document->file_path) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('mahasiswa.skripsi.show', $skripsi)->with('success', 'Dokumen berhasil dihapus.');
    }
}
