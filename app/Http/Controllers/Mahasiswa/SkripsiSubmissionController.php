<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\Periode;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SkripsiSubmissionController extends Controller
{
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

        $skripsi = Skripsi::query()->firstOrNew([
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
        $recipients = User::query()->forRole('kaprodi')->get()->concat(User::query()->forRole('dosen')->get());

        $notifications->send($recipients, [
            'type' => 'proposal_submitted',
            'title' => 'Proposal baru dikirim',
            'message' => "{$studentName} mengirim proposal skripsi: {$skripsi->title}",
            'url' => route('kaprodi.skripsi.show', ['id' => $skripsi->id], false),
            'actor' => $studentName,
            'meta' => [
                'skripsi_id' => $skripsi->id,
                'phase' => 'proposal',
            ],
        ]);

        return redirect()
            ->route('mahasiswa.skripsi.show', ['id' => $skripsi->id])
            ->with('success', 'Proposal berhasil dikirim.');
    }
}
