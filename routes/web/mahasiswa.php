<?php

use App\Http\Controllers\Mahasiswa\FinalSubmissionController;
use App\Http\Controllers\Mahasiswa\SkripsiSubmissionController;
use App\Http\Controllers\Mahasiswa\NonSkripsiController;
use Illuminate\Support\Facades\Route;

Route::prefix('mahasiswa')->name('mahasiswa.')->middleware(['auth', 'role:mahasiswa'])->group(function () use ($page, $sampleId, $sampleMeetingId, $tableActionPair, $tableAction, $skripsiDetailPage) {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $skripsiData = app(\App\Services\MahasiswaSkripsiDataService::class);

        $activeTugasAkhir = $skripsiData->activeForUser($user);

        $latestBimbingans = collect();
        $pembimbing = '-';

        $proposalUploaded = false;

        if ($activeTugasAkhir) {
            $latestBimbingans = $skripsiData->latestBimbingans($activeTugasAkhir);

            $pembimbing = optional($activeTugasAkhir->assignments->firstWhere('role_type', 'pembimbing_1'))->lecturer?->name ?? '-';
            $proposalUploaded = $activeTugasAkhir->documentVersions()->where('phase', 'proposal')->exists();
        }

        return view('mahasiswa.dashboard', [
            'title' => 'Dashboard Mahasiswa',
            'stats' => [
                ['label' => 'Tugas Akhir Aktif', 'value' => $activeTugasAkhir ? '1' : '0'],
                ['label' => 'Dokumen', 'value' => (string) ($activeTugasAkhir?->documentVersions()->count() ?? 0)],
                ['label' => 'Bimbingan', 'value' => (string) ($activeTugasAkhir?->bimbingans()->count() ?? 0)],
            ],
            'activeSkripsi' => $activeTugasAkhir ? [
                'title' => $activeTugasAkhir->title,
                'phase_formatted' => str($activeTugasAkhir->current_phase)->replace(['_', '-'], ' ')->title()->toString(),
                'pembimbing' => $pembimbing,
                'bimbingan_count' => $activeTugasAkhir->bimbingans->count(),
                'detail_url' => route('mahasiswa.skripsi.show', $activeTugasAkhir),
                'type' => str($activeTugasAkhir->type)->replace('_', ' ')->title()->toString(),
            ] : null,
            'activeSkripsiRecord' => $activeTugasAkhir,
            'latestBimbingans' => $latestBimbingans,
            'needsProposalUpload' => ! $proposalUploaded && ! empty($activeTugasAkhir),
            'proposalUploadUrl' => ! empty($activeTugasAkhir) ? route('mahasiswa.skripsi.documents.store', $activeTugasAkhir) : null,
        ]);
    })->name('dashboard');

    Route::get('/progress-tugas-akhir', [\App\Http\Controllers\Mahasiswa\SkripsiController::class, 'index'])->name('progres.index');

    Route::prefix('skripsi')->name('skripsi.')->group(function () {
        Route::get('/search', [\App\Http\Controllers\Mahasiswa\SkripsiController::class, 'search'])->name('search');
        Route::get('/', [\App\Http\Controllers\Mahasiswa\SkripsiController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Mahasiswa\SkripsiController::class, 'create'])->name('create');
        Route::get('/buat', fn() => redirect()->route('mahasiswa.skripsi.create'));
        Route::post('/', [\App\Http\Controllers\Mahasiswa\SkripsiController::class, 'store'])->name('store');
        Route::get('/{skripsi}', [\App\Http\Controllers\Mahasiswa\SkripsiController::class, 'show'])->name('show');
        Route::get('/{skripsi}/edit', [\App\Http\Controllers\Mahasiswa\SkripsiController::class, 'edit'])->name('edit');
        Route::put('/{skripsi}', [\App\Http\Controllers\Mahasiswa\SkripsiController::class, 'update'])->name('update');
        Route::delete('/{skripsi}', [\App\Http\Controllers\Mahasiswa\SkripsiController::class, 'destroy'])->name('destroy');

        Route::get('/{skripsi}/proposal/upload', [\App\Http\Controllers\Mahasiswa\DocumentVersionController::class, 'createProposalUpload'])->name('proposal.upload');
        Route::post('/{skripsi}/documents', [\App\Http\Controllers\Mahasiswa\DocumentVersionController::class, 'store'])->name('documents.store');
        Route::get('/{skripsi}/proposal/{document}/file', [\App\Http\Controllers\Mahasiswa\DocumentVersionController::class, 'showProposalFile'])->name('proposal.file');
        Route::delete('/{skripsi}/documents/{document}', [\App\Http\Controllers\Mahasiswa\DocumentVersionController::class, 'destroy'])->name('documents.destroy');

        Route::get('/{skripsi}/bimbingan', [\App\Http\Controllers\Mahasiswa\BimbinganController::class, 'index'])->name('bimbingan.index');
        Route::get('/{skripsi}/bimbingan/export/csv', [\App\Http\Controllers\Mahasiswa\BimbinganController::class, 'exportCsv'])->name('bimbingan.export.csv');
        Route::get('/{skripsi}/bimbingan/export/pdf', [\App\Http\Controllers\Mahasiswa\BimbinganController::class, 'exportPdf'])->name('bimbingan.export.pdf');
        Route::match(['post', 'put'], '/{skripsi}/bimbingan/{bimbingan}', [\App\Http\Controllers\Mahasiswa\BimbinganController::class, 'update'])->name('bimbingan.update');
        Route::delete('/{skripsi}/bimbingan/{bimbingan}/revision', [\App\Http\Controllers\Mahasiswa\BimbinganController::class, 'destroyRevision'])->name('bimbingan.revision.destroy');

        Route::get('/{skripsi}/nilai', [\App\Http\Controllers\Mahasiswa\NilaiController::class, 'index'])->name('nilai.index');
    });

    Route::get('/skripsi/{skripsi}/final-submission/{event}', [FinalSubmissionController::class, 'index'])->name('final.index');
    Route::post('/skripsi/{skripsi}/final-submission/{event}', [FinalSubmissionController::class, 'store'])->name('final.submit');

Route::prefix('non-skripsi')->name('non-skripsi.')->group(function () {
        Route::get('/', [NonSkripsiController::class, 'index'])->name('index');
        Route::get('/create', [NonSkripsiController::class, 'create'])->name('create');
        Route::post('/', [NonSkripsiController::class, 'store'])->name('store');
        Route::get('/{non_skripsi}', [NonSkripsiController::class, 'show'])->name('show');
        Route::get('/{non_skripsi}/edit', [NonSkripsiController::class, 'edit'])->name('edit');
        Route::put('/{non_skripsi}', [NonSkripsiController::class, 'update'])->name('update');
        Route::delete('/{non_skripsi}', [NonSkripsiController::class, 'destroy'])->name('destroy');
    });
});
