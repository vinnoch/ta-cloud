<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\DocumentVersion;
use App\Models\Skripsi;
use App\Services\NotificationService;
use App\Services\StudentDocumentPathService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinalReviewController extends Controller
{
    use BuildsKaprodiPage;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $approvalStatus = (string) $request->query('approval_status', 'all');
        $periodeId = (int) $request->query('periode_id', 0);
        $sort = (string) $request->query('sort', 'tanggal');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (! in_array($approvalStatus, ['all', 'pending_approval', 'approved'], true)) {
            $approvalStatus = 'all';
        }

        if (! in_array($sort, ['tanggal', 'nim', 'mahasiswa', 'judul', 'periode'], true)) {
            $sort = 'tanggal';
        }

        $skripsis = Skripsi::query()
            ->select('skripsis.*')
            ->leftJoin('users as students_sort', 'students_sort.id', '=', 'skripsis.student_id')
            ->leftJoin('periodes as periodes_sort', 'periodes_sort.id', '=', 'skripsis.periode_id')
            ->withMin(['documentVersions as final_submitted_at' => function ($q) {
                $q->where('phase', 'skripsi_final');
            }], 'created_at')
            ->whereIn('current_phase', ['review_dokumen_final', 'skripsi_selesai'])
            ->when($periodeId > 0, fn ($query) => $query->where('skripsis.periode_id', $periodeId))
            ->whereHas('assignments')
            ->whereDoesntHave('assignments', function ($assignmentQuery) {
                $assignmentQuery->whereNotExists(function ($gradeQuery) {
                    $gradeQuery->selectRaw('1')
                        ->from('grades')
                        ->whereColumn('grades.skripsi_id', 'skripsis.id')
                        ->whereColumn('grades.reviewer_id', 'reviewer_assignments.lecturer_id')
                        ->whereColumn('grades.role_type', 'reviewer_assignments.role_type')
                        ->where('grades.grade_event', 'sidang_skripsi')
                        ->where('grades.status', 'published');
                });
            })
            ->with([
                'student',
                'periode',
                'documentVersions' => function ($query) {
                    $query->where('phase', 'skripsi_final')->oldest('created_at');
                },
            ])
            ->when($approvalStatus === 'pending_approval', function ($query) {
                $query->where('current_phase', 'review_dokumen_final');
            })
            ->when($approvalStatus === 'approved', function ($query) {
                $query->where('current_phase', 'skripsi_selesai');
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('nim', 'like', "%{$search}%"))
                        ->orWhere('title', 'like', "%{$search}%");
                });
            });

        $sortMap = [
            'tanggal' => 'final_submitted_at',
            'nim' => 'students_sort.nim',
            'mahasiswa' => 'students_sort.name',
            'judul' => 'skripsis.title',
            'periode' => 'periodes_sort.kode_periode',
        ];

        $skripsis = $skripsis
            ->orderBy($sortMap[$sort], $direction)
            ->orderBy('skripsis.id', 'desc')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'table_html' => view('kaprodi.final-review.partials.table', [
                    'skripsis' => $skripsis,
                    'sort' => $sort,
                    'direction' => $direction,
                    'periodeId' => $periodeId,
                ])->render(),
                'pagination_html' => view('kaprodi.final-review.partials.pagination', [
                    'skripsis' => $skripsis,
                ])->render(),
                'count_text' => $skripsis->total().' dokumen final ditemukan.',
            ]);
        }

        return view('kaprodi.final-review.index', $this->kaprodiPage('Pengajuan Dokumen Final', 'KAPRODI • REVIEW DOKUMEN FINAL', [
            'skripsis' => $skripsis,
            'search' => $search,
            'approvalStatus' => $approvalStatus,
            'periodeId' => $periodeId,
            'sort' => $sort,
            'direction' => $direction,
        ]));
    }

    public function approve(Request $request, Skripsi $skripsi, NotificationService $notifications): RedirectResponse
    {
        if ($skripsi->current_phase === 'review_dokumen_final' && $this->eligibleForCompletion($skripsi)) {
            $skripsi->update(['current_phase' => 'skripsi_selesai']);

            $notifications->send([$skripsi->student], [
                'type' => 'skripsi_finished',
                'title' => 'Skripsi Selesai',
                'message' => 'Selamat! Skripsi Anda telah divalidasi Kaprodi dan dinyatakan selesai.',
                'url' => route('mahasiswa.skripsi.show', $skripsi, false),
                'actor' => auth()->user()->name,
                'meta' => ['skripsi_id' => $skripsi->id],
            ]);

            return redirect()->route('kaprodi.skripsi.show', $skripsi)->with('success', 'Skripsi berhasil divalidasi dan dinyatakan selesai.');
        }

        return back()->with('error', 'Dokumen final atau nilai reviewer belum lengkap.');
    }

    public function completeLegacy(Request $request, Skripsi $skripsi, StudentDocumentPathService $documentPathService): RedirectResponse
    {
        $skripsi->loadMissing(['periode', 'student']);

        if (! $skripsi->periode || $skripsi->periode->is_aktif || $skripsi->periode->status === 'active') {
            return back()->with('error', 'Penyelesaian historis hanya tersedia untuk periode lama yang tidak aktif.');
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
        ]);

        $nextVersion = ((int) $skripsi->documentVersions()->where('phase', 'skripsi_final')->max('version_number')) + 1;
        $file = $validated['file'];
        $path = $file->storeAs('', $documentPathService->buildStoragePath($skripsi, 'skripsi_final', $nextVersion, $file), 'local');

        DocumentVersion::query()->create([
            'skripsi_id' => $skripsi->id,
            'phase' => 'skripsi_final',
            'version_number' => $nextVersion,
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        $skripsi->update(['current_phase' => 'skripsi_selesai']);

        return redirect()->route('kaprodi.skripsi.show', $skripsi)
            ->with('success', 'Dokumen final historis tersimpan dan skripsi dinyatakan selesai.');
    }

    private function eligibleForCompletion(Skripsi $skripsi): bool
    {
        $document = $skripsi->documentVersions()->where('phase', 'skripsi_final')->latest('version_number')->first();
        $assignments = $skripsi->assignments()->get();

        return $document !== null && $assignments->isNotEmpty() && $assignments->every(fn ($assignment) => $skripsi->grades()->where('reviewer_id', $assignment->lecturer_id)->where('role_type', $assignment->role_type)->where('grade_event', 'sidang_skripsi')->where('status', 'published')->exists());
    }
}
