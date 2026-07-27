<?php

use App\Http\Controllers\Mahasiswa\BimbinganController;
use App\Http\Controllers\Mahasiswa\DocumentVersionController;
use App\Http\Controllers\Mahasiswa\FinalSubmissionController;
use App\Http\Controllers\Mahasiswa\NilaiController;
use App\Http\Controllers\Mahasiswa\NonSkripsiController;
use App\Http\Controllers\Mahasiswa\SkripsiController;
use Illuminate\Support\Facades\Route;

Route::prefix('mahasiswa')->name('mahasiswa.')->middleware(['auth', 'role:mahasiswa'])->group(function () {
    Route::get('/progress-tugas-akhir', [SkripsiController::class, 'index'])->name('progres.index');

    Route::prefix('skripsi')->name('skripsi.')->group(function () {
        Route::get('/search', [SkripsiController::class, 'search'])->name('search');
        Route::get('/', [SkripsiController::class, 'index'])->name('index');
        Route::get('/create', [SkripsiController::class, 'create'])->name('create');
        Route::get('/buat', fn () => redirect()->route('mahasiswa.skripsi.create'));
        Route::post('/', [SkripsiController::class, 'store'])->name('store');
        Route::get('/{skripsi}', [SkripsiController::class, 'show'])->name('show');
        Route::post('/{skripsi}/publish', [SkripsiController::class, 'publish'])->name('publish');
        Route::get('/{skripsi}/edit', [SkripsiController::class, 'edit'])->name('edit');
        Route::put('/{skripsi}', [SkripsiController::class, 'update'])->name('update');
        Route::delete('/{skripsi}', [SkripsiController::class, 'destroy'])->name('destroy');

        Route::get('/{skripsi}/proposal/upload', [DocumentVersionController::class, 'createProposalUpload'])->name('proposal.upload');
        Route::post('/{skripsi}/documents', [DocumentVersionController::class, 'store'])->name('documents.store');
        Route::get('/{skripsi}/proposal/{document}/file', [DocumentVersionController::class, 'showProposalFile'])->name('proposal.file');
        Route::delete('/{skripsi}/documents/{document}', [DocumentVersionController::class, 'destroy'])->name('documents.destroy');

        Route::get('/{skripsi}/bimbingan', [BimbinganController::class, 'index'])->name('bimbingan.index');
        Route::get('/{skripsi}/bimbingan/export/csv', [BimbinganController::class, 'exportCsv'])->name('bimbingan.export.csv');
        Route::get('/{skripsi}/bimbingan/export/pdf', [BimbinganController::class, 'exportPdf'])->name('bimbingan.export.pdf');
        Route::match(['post', 'put'], '/{skripsi}/bimbingan/{bimbingan}', [BimbinganController::class, 'update'])->name('bimbingan.update');
        Route::delete('/{skripsi}/bimbingan/{bimbingan}/revision', [BimbinganController::class, 'destroyRevision'])->name('bimbingan.revision.destroy');

        Route::get('/{skripsi}/nilai', [NilaiController::class, 'index'])->name('nilai.index');
        Route::get('/{skripsi}/dokumen-final', [FinalSubmissionController::class, 'skripsiFinal'])->name('final.skripsi.index');
        Route::post('/{skripsi}/dokumen-final', [FinalSubmissionController::class, 'storeSkripsiFinal'])->name('final.skripsi.store');
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
