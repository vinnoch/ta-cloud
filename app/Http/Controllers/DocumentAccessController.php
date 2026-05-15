<?php

namespace App\Http\Controllers;

use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentAccessController extends Controller
{
    public function preview(Request $request, DocumentVersion $document): BinaryFileResponse
    {
        $this->authorizeDocument($request, $document);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return response()->file(Storage::disk('local')->path($document->file_path), [
            'Content-Type' => $document->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . basename($document->file_path) . '"',
        ]);
    }

    public function download(Request $request, DocumentVersion $document): StreamedResponse
    {
        $this->authorizeDocument($request, $document);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, basename($document->file_path));
    }

    private function authorizeDocument(Request $request, DocumentVersion $document): void
    {
        $user = $request->user();
        $document->loadMissing(['skripsi.assignments']);
        $skripsi = $document->skripsi;

        if (! $skripsi) {
            abort(404);
        }

        if ($user->role === 'kaprodi') {
            return;
        }

        if ($user->role === 'mahasiswa' && (int) $skripsi->student_id === (int) $user->id) {
            return;
        }

        if ($user->role === 'dosen' && $skripsi->assignments->contains(fn ($assignment) => (int) $assignment->lecturer_id === (int) $user->id)) {
            return;
        }

        abort(403);
    }
}
