<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\DocumentVersion;
use App\Models\FinalDocumentApproval;
use App\Models\Grade;
use App\Models\ReviewerAssignment;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\StudentDocumentPathService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinalSubmissionController extends Controller
{
    public function index(Request $request, Skripsi $skripsi, string $event): View|RedirectResponse
    {
        $this->authorizeOwner($request, $skripsi);

        $submission = $this->buildSubmissionState($skripsi, $event);
        if (! $submission['allowed']) {
            return redirect()
                ->route('mahasiswa.skripsi.show', $skripsi, false)
                ->with('warning', $submission['message']);
        }

        return view('mahasiswa.final.index', [
            'skripsi' => $skripsi,
            'submission' => $submission,
            'checklist' => $submission['checklist'],
            'cards' => $submission['cards'],
        ]);
    }

    public function store(Request $request, Skripsi $skripsi, string $event, NotificationService $notifications, StudentDocumentPathService $documentPathService): RedirectResponse
    {
        $this->authorizeOwner($request, $skripsi);

        $submission = $this->buildSubmissionState($skripsi, $event);
        if (! $submission['allowed']) {
            return redirect()
                ->route('mahasiswa.skripsi.show', $skripsi, false)
                ->with('warning', $submission['message']);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'journal_article_url' => ['nullable', 'url', 'max:500'],
        ]);

        $phase = $submission['document_phase'];
        $nextVersion = ((int) DocumentVersion::query()
            ->where('skripsi_id', $skripsi->id)
            ->where('phase', $phase)
            ->max('version_number')) + 1;

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $path = $file->storeAs('', $documentPathService->buildStoragePath($skripsi->loadMissing('student'), $phase, $nextVersion, $file), 'local');

        $document = DocumentVersion::query()->create([
            'skripsi_id' => $skripsi->id,
            'phase' => $phase,
            'version_number' => $nextVersion,
            'file_path' => $path,
            'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
            'size' => $file->getSize() ?: 0,
            'uploaded_by' => $request->user()->id,
        ]);

        if ($event === 'sidang_skripsi') {
            $skripsi->finalDocumentApprovals()
                ->where('document_version_id', '!=', $document->id)
                ->where('status', 'pending')
                ->update(['status' => 'superseded']);

            $skripsi->assignments()
                ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2', 'penguji_1', 'penguji_2'])
                ->get()
                ->each(function ($assignment) use ($skripsi, $document): void {
                    FinalDocumentApproval::query()->updateOrCreate(
                        [
                            'document_version_id' => $document->id,
                            'reviewer_id' => $assignment->lecturer_id,
                        ],
                        [
                            'skripsi_id' => $skripsi->id,
                            'role_type' => $assignment->role_type,
                            'status' => 'pending',
                            'note' => null,
                            'reviewed_at' => null,
                        ]
                    );
                });
        }

        $updates = [
            'current_phase' => $submission['next_phase'],
        ];

        if ($event === 'sidang_skripsi' && filled($validated['journal_article_url'] ?? null)) {
            $updates['journal_article_url'] = $validated['journal_article_url'];
        }

        $skripsi->update($updates);

        $recipients = User::query()->forRole('kaprodi')->get()
            ->concat($skripsi->assignments()->with('lecturer')->get()->pluck('lecturer')->filter())
            ->unique('id')
            ->values();

        $notifications->send($recipients, [
            'type' => $event === 'sidang_proposal' ? 'proposal_final_submitted' : 'skripsi_final_submitted',
            'title' => $event === 'sidang_proposal' ? 'Final proposal dikirim' : 'Dokumen final skripsi dikirim',
            'message' => $request->user()->name . ' mengirim ' . ($event === 'sidang_proposal' ? 'proposal final' : 'dokumen final skripsi') . ': ' . $skripsi->title,
            'url' => route('kaprodi.skripsi.show', ['skripsi' => $skripsi->id], false),
            'actor' => $request->user()->name,
            'meta' => [
                'skripsi_id' => $skripsi->id,
                'document_version_id' => $document->id,
                'phase' => $phase,
                'notes' => $validated['notes'] ?? null,
            ],
        ]);

        return redirect()
            ->route('mahasiswa.skripsi.show', $skripsi, false)
            ->with('success', $event === 'sidang_proposal'
                ? 'Proposal final berhasil dikirim.'
                : 'Dokumen final skripsi berhasil dikirim.');
    }

    private function authorizeOwner(Request $request, Skripsi $skripsi): void
    {
        if ($skripsi->student_id !== $request->user()->id) {
            abort(403);
        }
    }

    public static function buildSubmissionState(Skripsi $skripsi, string $event): array
    {
        if (! in_array($event, ['sidang_proposal', 'sidang_skripsi'], true)) {
            return [
                'allowed' => false,
                'message' => 'Jenis final submission tidak dikenali.',
            ];
        }

        $assignmentRoles = $event === 'sidang_proposal'
            ? ['pembimbing_1', 'pembimbing_2', 'penguji_1']
            : ['pembimbing_1', 'pembimbing_2', 'penguji_1', 'penguji_2'];

        $assignedReviewerCount = ReviewerAssignment::query()
            ->where('skripsi_id', $skripsi->id)
            ->whereIn('role_type', $assignmentRoles)
            ->count();

        $finalGrades = Grade::query()
            ->where('skripsi_id', $skripsi->id)
            ->where('grade_event', $event)
            ->where('status', 'published')
            ->with('reviewer')
            ->get();

        $documentPhase = $event === 'sidang_proposal' ? 'proposal_final' : 'skripsi_final';
        $latestDocument = DocumentVersion::query()
            ->where('skripsi_id', $skripsi->id)
            ->where('phase', $documentPhase)
            ->orderByDesc('version_number')
            ->first();

        $hasRejectedFinalDocument = $event === 'sidang_skripsi' && $latestDocument
            ? FinalDocumentApproval::query()
                ->where('document_version_id', $latestDocument->id)
                ->where('status', 'rejected')
                ->exists()
            : false;

        $alreadySubmitted = (bool) $latestDocument && ! $hasRejectedFinalDocument;

        $average = $finalGrades->whereNotNull('score')->avg('score');
        $requiredGradeCount = max($assignedReviewerCount, 1);
        $hasCompleteFinalGrades = $finalGrades->count() >= $requiredGradeCount;

        $allowedPhases = $event === 'sidang_proposal'
            ? ['sidang_proposal', 'bimbingan_skripsi', 'sidang_skripsi', 'revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai']
            : ['sidang_skripsi', 'revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'];

        $phaseAllowed = in_array($skripsi->current_phase, $allowedPhases, true);
        $allowed = $phaseAllowed && $hasCompleteFinalGrades && ! $alreadySubmitted;

        return [
            'event' => $event,
            'title' => $event === 'sidang_proposal' ? 'Final Submission Proposal' : 'Final Submission Skripsi',
            'allowed' => $allowed,
            'message' => $alreadySubmitted
                ? 'Final submission untuk tahap ini sudah pernah dikirim.'
                : (! $phaseAllowed ? 'Tahap final submission belum tersedia untuk fase skripsi saat ini.' : 'Final submission baru tersedia setelah semua nilai masuk.'),
            'document_phase' => $documentPhase,
            'next_phase' => $event === 'sidang_proposal' ? 'bimbingan_skripsi' : 'review_dokumen_final',
            'average' => $average,
            'final_grade_count' => $finalGrades->count(),
            'required_grade_count' => $requiredGradeCount,
            'already_submitted' => $alreadySubmitted,
            'has_rejected_final_document' => $hasRejectedFinalDocument,
            'show_journal_field' => $event === 'sidang_skripsi',
            'checklist' => [
                [
                    'title' => 'Nilai final tersedia',
                    'description' => 'Minimal ' . $requiredGradeCount . ' penilai untuk tahap ' . str($event)->replace('_', ' ')->title() . '.',
                    'status' => $hasCompleteFinalGrades ? 'SIAP' : 'MENUNGGU',
                ],
                [
                    'title' => 'Rata-rata nilai akhir',
                    'description' => $average !== null ? number_format((float) $average, 2) : 'Belum ada rerata nilai.',
                    'status' => $average !== null ? 'TERSEDIA' : 'BELUM ADA',
                ],
                [
                    'title' => 'Dokumen yang dikirim',
                    'description' => $event === 'sidang_proposal' ? 'Upload proposal final hasil perbaikan sidang.' : 'Upload naskah skripsi final hasil revisi sidang.',
                    'status' => $alreadySubmitted ? 'SUDAH DIKIRIM' : 'BELUM DIKIRIM',
                ],
                [
                    'title' => 'Fase berikutnya',
                    'description' => str($event === 'sidang_proposal' ? 'bimbingan_skripsi' : 'review_dokumen_final')->replace('_', ' ')->title(),
                    'status' => 'AUTO UPDATE',
                ],
            ],
            'cards' => [
                [
                    'eyebrow' => 'Fase Saat Ini',
                    'title' => str($skripsi->current_phase)->replace('_', ' ')->title()->toString(),
                    'description' => 'Submission final akan memindahkan alur ke fase berikutnya.',
                ],
                [
                    'eyebrow' => 'Penilai Final',
                    'title' => (string) $finalGrades->count() . ' / ' . (string) $requiredGradeCount,
                    'description' => 'Jumlah dosen yang sudah mempublikasikan nilai.',
                ],
            ],
        ];
    }
}
