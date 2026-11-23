<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Periode;
use App\Models\TugasAkhir;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TugasAkhirRouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TugasAkhirSubmissionController extends Controller
{
    public function __construct(private readonly TugasAkhirRouteService $tugasAkhirRoutes) {}

    public function store(Request $request, NotificationService $notifications): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:skripsi,non_skripsi'],
            'kode_periode' => ['required', 'string', 'exists:periodes,kode_periode'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'journal_article_url' => ['nullable', 'url', 'max:500'],
        ]);

        $period = Periode::query()->where('kode_periode', $validated['kode_periode'])->firstOrFail();

        $skripsi = TugasAkhir::query()->firstOrNew([
            'student_id' => (int) $request->user()->id,
        ]);

        $skripsi->fill([
            'periode_id' => $period->id,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'current_phase' => 'proposal',
            'journal_article_url' => $validated['journal_article_url'] ?? null,
        ]);
        $skripsi->save();

        $studentName = $request->user()->name;
        $payload = [
            'type' => 'proposal_submitted',
            'title' => 'Proposal baru dikirim',
            'message' => "{$studentName} mengirim proposal skripsi: {$skripsi->title}",
            'actor' => $studentName,
            'meta' => [
                'tugas_akhir_id' => $skripsi->id,
                'phase' => 'proposal',
            ],
        ];
        $recipients = fn (string $role) => User::query()
            ->forRole($role)
            ->where('account_status', 'active')
            ->where('study_program_id', $request->user()->study_program_id)
            ->get();

        $notifications->send($recipients('kaprodi'), $payload + [
            'url' => $this->tugasAkhirRoutes->route('kaprodi', $skripsi, 'show', $skripsi, false),
        ]);
        $notifications->send($recipients('dosen'), $payload + [
            'url' => route('dosen.proposal.index', absolute: false),
        ]);

        return redirect()
            ->to($this->tugasAkhirRoutes->route('mahasiswa', $skripsi, 'index'))
            ->with('success', 'Proposal berhasil dikirim.');
    }
}
