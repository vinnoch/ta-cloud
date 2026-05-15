<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Bimbingan;
use App\Models\DocumentVersion;
use App\Models\Skripsi;
use App\Services\NotificationService;
use App\Services\StudentDocumentPathService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BimbinganController extends Controller
{
    public function index(Request $request, Skripsi $skripsi): View
    {
        if ($skripsi->student_id !== $request->user()->id) {
            abort(403);
        }

        $reviewerId = (int) $request->query('reviewer_id', 0);

        $reviewerOptions = $skripsi->assignments()
            ->with('lecturer:id,name')
            ->get()
            ->pluck('lecturer')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $bimbingans = Bimbingan::query()
            ->where('skripsi_id', $skripsi->id)
            ->when($reviewerId > 0, fn ($query) => $query->where('reviewer_id', $reviewerId))
            ->with(['reviewer', 'reviewedVersion'])
            ->orderByDesc('meeting_date')
            ->get();

        $meetings = $bimbingans->map(function (Bimbingan $b) use ($skripsi): array {
            $revisionName = $b->reviewedVersion
                ? basename((string) $b->reviewedVersion->file_path)
                : null;

            return [
                'record' => $b,
                'topic' => str($b->phase)->replace('_', ' ')->title()->toString(),
                'phase' => $b->phase,
                'summary' => $b->student_notes ?: ($b->lecturer_notes ?: '-'),
                'date' => $b->meeting_date?->format('d M Y') ?? '-',
                'reviewer' => $b->reviewer?->name ?? '-',
                'has_revision' => ! empty($b->reviewedVersion),
                'revision_url' => $b->revision_file_url,
                'revision_name' => $revisionName,
                'upload_url' => route('mahasiswa.skripsi.bimbingan.update', [$skripsi, $b]),
                'remove_url' => route('mahasiswa.skripsi.bimbingan.revision.destroy', [$skripsi, $b]),
            ];
        })->all();

        $cards = [];

        return view('mahasiswa.bimbingan.index', [
            'title' => 'Histori Bimbingan',
            'heading' => 'Histori Bimbingan',
            'crumbs' => 'MAHASISWA • HISTORI BIMBINGAN',
            'skripsi' => $skripsi,
            'meetings' => $meetings,
            'cards' => $cards,
            'reviewerOptions' => $reviewerOptions,
            'selectedReviewerId' => $reviewerId,
        ]);
    }

    public function exportCsv(Request $request, Skripsi $skripsi): StreamedResponse
    {
        if ($skripsi->student_id !== $request->user()->id) {
            abort(403);
        }

        $reviewerId = (int) $request->query('reviewer_id', 0);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="logbook_mahasiswa_' . $skripsi->id . ($reviewerId > 0 ? '_reviewer_' . $reviewerId : '') . '.csv"',
        ];

        $callback = function () use ($skripsi, $reviewerId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tanggal', 'Dosen', 'Catatan', 'Dokumen']);

            $rows = Bimbingan::query()
                ->where('skripsi_id', $skripsi->id)
                ->when($reviewerId > 0, fn ($query) => $query->where('reviewer_id', $reviewerId))
                ->with(['reviewer', 'reviewedVersion'])
                ->orderByDesc('meeting_date')
                ->get();

            foreach ($rows as $row) {
                fputcsv($handle, [
                    optional($row->meeting_date)->format('d/m/Y'),
                    $row->reviewer?->name ?? '-',
                    $row->student_notes ?: ($row->lecturer_notes ?: '-'),
                    $row->has_revision_file ? ($row->reviewedVersion?->file_path ? basename($row->reviewedVersion->file_path) : 'Dokumen') : '-',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request, Skripsi $skripsi): View
    {
        if ($skripsi->student_id !== $request->user()->id) {
            abort(403);
        }

        $reviewerId = (int) $request->query('reviewer_id', 0);
        $reviewer = $reviewerId > 0 ? $skripsi->assignments()->with('lecturer:id,name')->get()->pluck('lecturer')->firstWhere('id', $reviewerId) : null;
        $rows = Bimbingan::query()
            ->where('skripsi_id', $skripsi->id)
            ->when($reviewerId > 0, fn ($query) => $query->where('reviewer_id', $reviewerId))
            ->with(['reviewer', 'reviewedVersion'])
            ->orderByDesc('meeting_date')
            ->get();

        return view('mahasiswa.bimbingan.export-pdf', [
            'skripsi' => $skripsi,
            'rows' => $rows,
            'selectedReviewer' => $reviewer,
        ]);
    }

    public function update(Request $request, Skripsi $skripsi, Bimbingan $bimbingan, StudentDocumentPathService $documentPathService, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        if ($skripsi->student_id !== $request->user()->id || $bimbingan->skripsi_id !== $skripsi->id) {
            abort(403);
        }

        $validated = $request->validate([
            'student_notes' => ['nullable', 'string'],
            'revision_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:2048'],
        ]);

        $bimbingan->student_notes = $validated['student_notes'] ?? null;
        $uploadedRevision = false;

        if ($request->hasFile('revision_file')) {
            $file = $request->file('revision_file');
            $nextVersion = DocumentVersion::query()
                ->where('skripsi_id', $skripsi->id)
                ->where('phase', $bimbingan->phase)
                ->max('version_number');

            $nextVersion = ((int) $nextVersion) + 1;
            $path = $file->storeAs('', $documentPathService->buildStoragePath($skripsi->loadMissing('student'), $bimbingan->phase . '_revision', $nextVersion, $file), 'local');

            $document = DocumentVersion::query()->create([
                'skripsi_id' => $skripsi->id,
                'phase' => $bimbingan->phase,
                'version_number' => $nextVersion,
                'file_path' => $path,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize() ?: 0,
                'uploaded_by' => $request->user()->id,
            ]);

            $bimbingan->reviewed_version_id = $document->id;
            $bimbingan->revision_file_url = null;
            $uploadedRevision = true;
        }

        $bimbingan->save();

        if ($uploadedRevision && $bimbingan->reviewer) {
            $notifications->send([$bimbingan->reviewer], [
                'type' => 'bimbingan_revision_uploaded',
                'title' => 'Revisi bimbingan baru',
                'message' => ($skripsi->student?->name ?? 'Mahasiswa') . ' mengunggah revisi bimbingan untuk ' . str($bimbingan->phase)->replace('_', ' ')->title() . '.',
                'url' => route('dosen.skripsi.show', $skripsi, false) . '#bimbingan-' . $bimbingan->id,
                'actor' => $request->user()->name,
                'meta' => [
                    'skripsi_id' => $skripsi->id,
                    'bimbingan_id' => $bimbingan->id,
                    'phase' => $bimbingan->phase,
                    'student_name' => $skripsi->student?->name,
                ],
            ]);
        }

        if ($request->expectsJson() || $request->ajax()) {
            $document = $bimbingan->reviewedVersion;

            return response()->json([
                'message' => 'Catatan dan revisi bimbingan berhasil diperbarui.',
                'filename' => $document ? basename((string) $document->file_path) : null,
                'url' => $bimbingan->revision_file_url,
                'remove_url' => route('mahasiswa.skripsi.bimbingan.revision.destroy', [$skripsi, $bimbingan]),
                'upload_url' => route('mahasiswa.skripsi.bimbingan.update', [$skripsi, $bimbingan]),
            ]);
        }

        return redirect()
            ->to(route('mahasiswa.skripsi.bimbingan.index', $skripsi, false) . '#bimbingan-' . $bimbingan->id)
            ->with('success', 'Catatan dan revisi bimbingan berhasil diperbarui.');
    }

    public function destroyRevision(Request $request, Skripsi $skripsi, Bimbingan $bimbingan): RedirectResponse|JsonResponse
    {
        if ($skripsi->student_id !== $request->user()->id || $bimbingan->skripsi_id !== $skripsi->id) {
            abort(403);
        }

        if ($bimbingan->reviewedVersion?->file_path) {
            Storage::disk('local')->delete($bimbingan->reviewedVersion->file_path);
            $bimbingan->reviewedVersion->delete();
        }

        $bimbingan->reviewed_version_id = null;
        $bimbingan->revision_file_url = null;
        $bimbingan->save();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Referensi revisi berhasil dilepas.',
            ]);
        }

        return back()->with('success', 'Referensi revisi berhasil dilepas.');
    }
}
