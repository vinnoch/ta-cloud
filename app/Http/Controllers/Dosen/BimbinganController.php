<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Bimbingan;
use App\Models\ReviewerAssignment;
use App\Models\TugasAkhir;
use App\Services\NotificationService;
use App\Services\TugasAkhirRouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BimbinganController extends Controller
{
    public function store(Request $request, TugasAkhir $skripsi, NotificationService $notifications): RedirectResponse
    {
        $this->ensureAssigned($request, $skripsi);

        $validated = $request->validate([
            'meeting_date' => ['required', 'date'],
            'lecturer_notes' => ['nullable', 'string'],
        ]);

        Bimbingan::query()->create([
            'tugas_akhir_id' => $skripsi->id,
            'reviewer_id' => $request->user()->id,
            'phase' => $skripsi->current_phase,
            'meeting_date' => $validated['meeting_date'],
            'lecturer_notes' => $validated['lecturer_notes'] ?? null,
        ]);

        $notifications->send([$skripsi->student], [
            'type' => 'bimbingan_note_added',
            'title' => 'Catatan bimbingan baru',
            'message' => $request->user()->name.' menambahkan catatan bimbingan untuk '.$skripsi->title,
            'url' => app(TugasAkhirRouteService::class)->route('mahasiswa', $skripsi, 'bimbingan.index', $skripsi, false),
            'actor' => $request->user()->name,
            'meta' => ['tugas_akhir_id' => $skripsi->id],
        ]);

        return redirect()->to(app(TugasAkhirRouteService::class)->route('dosen', $skripsi, 'show', $skripsi))->with('success', 'Catatan bimbingan berhasil disimpan.');
    }

    public function update(Request $request, Bimbingan $bimbingan): RedirectResponse
    {
        $this->ensureAssigned($request, $bimbingan->tugasAkhir);
        abort_unless($bimbingan->reviewer_id === $request->user()->id, 403);
        $validated = $request->validate([
            'meeting_date' => ['required', 'date'],
            'lecturer_notes' => ['nullable', 'string'],
        ]);
        $bimbingan->update($validated);

        return redirect()->to(app(TugasAkhirRouteService::class)->route('dosen', $bimbingan->tugasAkhir, 'show', $bimbingan->tugasAkhir))->with('success', 'Catatan bimbingan berhasil diperbarui.');
    }

    public function destroy(Request $request, Bimbingan $bimbingan): RedirectResponse
    {
        $this->ensureAssigned($request, $bimbingan->tugasAkhir);
        abort_unless($bimbingan->reviewer_id === $request->user()->id, 403);
        $bimbingan->delete();

        return redirect()->to(app(TugasAkhirRouteService::class)->route('dosen', $bimbingan->tugasAkhir, 'show', $bimbingan->tugasAkhir))->with('success', 'Catatan bimbingan berhasil dihapus.');
    }

    private function ensureAssigned(Request $request, TugasAkhir $skripsi): void
    {
        $assigned = ReviewerAssignment::query()
            ->where('tugas_akhir_id', $skripsi->id)
            ->where('lecturer_id', $request->user()->id)
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])
            ->exists();

        if (! $assigned) {
            abort(403);
        }
    }
}
