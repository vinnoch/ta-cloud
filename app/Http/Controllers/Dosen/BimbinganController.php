<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Bimbingan;
use App\Models\ReviewerAssignment;
use App\Models\Skripsi;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BimbinganController extends Controller
{
    public function store(Request $request, Skripsi $skripsi, NotificationService $notifications): RedirectResponse
    {
        $this->ensureAssigned($request, $skripsi);

        $validated = $request->validate([
            'meeting_date' => ['required', 'date'],
            'lecturer_notes' => ['nullable', 'string'],
        ]);

        Bimbingan::query()->create([
            'skripsi_id' => $skripsi->id,
            'reviewer_id' => $request->user()->id,
            'phase' => $skripsi->current_phase,
            'meeting_date' => $validated['meeting_date'],
            'lecturer_notes' => $validated['lecturer_notes'] ?? null,
        ]);

        $notifications->send([$skripsi->student], [
            'type' => 'bimbingan_note_added',
            'title' => 'Catatan bimbingan baru',
            'message' => $request->user()->name . ' menambahkan catatan bimbingan untuk ' . $skripsi->title,
            'url' => route('mahasiswa.skripsi.bimbingan.index', $skripsi, false),
            'actor' => $request->user()->name,
            'meta' => ['skripsi_id' => $skripsi->id],
        ]);

        return redirect()->route('dosen.skripsi.show', $skripsi, false)->with('success', 'Catatan bimbingan berhasil disimpan.');
    }

    public function update(Request $request, Bimbingan $bimbingan): RedirectResponse
    {
        $this->ensureAssigned($request, $bimbingan->skripsi);
        $validated = $request->validate([
            'meeting_date' => ['required', 'date'],
            'lecturer_notes' => ['nullable', 'string'],
        ]);
        $bimbingan->update($validated);
        return redirect()->route('dosen.skripsi.show', $bimbingan->skripsi)->with('success', 'Catatan bimbingan berhasil diperbarui.');
    }

    public function destroy(Request $request, Bimbingan $bimbingan): RedirectResponse
    {
        $this->ensureAssigned($request, $bimbingan->skripsi);
        $bimbingan->delete();
        return redirect()->route('dosen.skripsi.show', $bimbingan->skripsi)->with('success', 'Catatan bimbingan berhasil dihapus.');
    }

    private function ensureAssigned(Request $request, Skripsi $skripsi): void
    {
        $assigned = ReviewerAssignment::query()
            ->where('skripsi_id', $skripsi->id)
            ->where('lecturer_id', $request->user()->id)
            ->exists();

        if (! $assigned) {
            abort(403);
        }
    }
}
