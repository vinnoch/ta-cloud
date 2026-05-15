<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mahasiswa\NonSkripsiRequest;
use App\Models\NonSkripsiRecord;
use App\Models\Skripsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NonSkripsiController extends Controller
{
    public function index(): View
    {
        $records = NonSkripsiRecord::query()
            ->whereHas('skripsi', function ($query) {
                $query->where('student_id', Auth::id());
            })
            ->with('skripsi')
            ->latest()
            ->get();

        return view('mahasiswa.non-skripsi.index', compact('records'));
    }

    public function create(): View
    {
        $skripsi = Skripsi::query()
            ->where('student_id', Auth::id())
            ->where('type', 'non_skripsi')
            ->firstOrFail();

        return view('mahasiswa.non-skripsi.create', compact('skripsi'));
    }


    public function store(NonSkripsiRequest $request): RedirectResponse
    {
        $skripsi = Skripsi::query()
            ->where('student_id', Auth::id())
            ->where('type', 'non_skripsi')
            ->firstOrFail();

        $record = new NonSkripsiRecord();
        $record->skripsi_id = $skripsi->id;
        $this->fillRecord($record, $request);
        $record->save();

        return redirect()->route('mahasiswa.non-skripsi.show', $record)
            ->with('success', 'Data non-skripsi berhasil disimpan.');
    }

    public function show(NonSkripsiRecord $non_skripsi): View
    {
        $this->authorizeOwner($non_skripsi);
        return view('mahasiswa.non-skripsi.show', compact('non_skripsi'));
    }

    public function edit(NonSkripsiRecord $non_skripsi): View
    {
        $this->authorizeOwner($non_skripsi);

        return view('mahasiswa.non-skripsi.edit', compact('non_skripsi'));
    }


    public function update(NonSkripsiRequest $request, NonSkripsiRecord $non_skripsi): RedirectResponse
    {
        $this->authorizeOwner($non_skripsi);
        $this->fillRecord($non_skripsi, $request);
        $non_skripsi->save();

        return redirect()->route('mahasiswa.non-skripsi.show', $non_skripsi)
            ->with('success', 'Data non-skripsi berhasil diperbarui.');
    }

    public function destroy(NonSkripsiRecord $non_skripsi): RedirectResponse
    {
        $this->authorizeOwner($non_skripsi);
        $non_skripsi->delete();

        return redirect()->route('mahasiswa.non-skripsi.index')
            ->with('success', 'Data non-skripsi berhasil dihapus.');
    }

    private function authorizeOwner(NonSkripsiRecord $record): void
    {
        if ($record->skripsi->student_id !== Auth::id()) {
            abort(403);
        }
    }

    private function fillRecord(NonSkripsiRecord $record, NonSkripsiRequest $request): void
    {
        // Model uses 'summary' for title/summary and 'publication_url' for link_publikasi
        $record->summary = $request->input('title');
        $record->abstract = $request->input('abstract');
        $record->final_score = $request->input('final_score');
        $record->publication_url = $request->input('link_publikasi');

        if ($request->hasFile('report_file')) {
            $file = $request->file('report_file');
            $path = $file->storeAs('private/non-skripsi/' . $record->skripsi_id, 'report_' . time() . '.pdf', 'local');
            $record->report_path = $path;
        }
    }
}
