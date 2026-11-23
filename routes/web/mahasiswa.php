<?php

use App\Http\Controllers\Mahasiswa\BimbinganController;
use App\Http\Controllers\Mahasiswa\DocumentVersionController;
use App\Http\Controllers\Mahasiswa\FinalSubmissionController;
use App\Http\Controllers\Mahasiswa\NilaiController;
use App\Http\Controllers\Mahasiswa\TugasAkhirController;
use App\Http\Middleware\EnsureTugasAkhirRouteType;
use Illuminate\Support\Facades\Route;

Route::prefix('mahasiswa')->name('mahasiswa.')->middleware(['auth', 'role:mahasiswa'])->group(function () {
    Route::get('/progress-tugas-akhir', [TugasAkhirController::class, 'index'])->name('progres.index');
    Route::get('/pendaftaran', [TugasAkhirController::class, 'create'])->name('pendaftaran.create');
    Route::post('/pendaftaran', [TugasAkhirController::class, 'store'])->name('pendaftaran.store');

    $registerTugasAkhirRoutes = function (string $slug, string $namespace): void {
        Route::prefix($slug)->name($namespace.'.')->middleware(EnsureTugasAkhirRouteType::class)->group(function () use ($namespace) {
            Route::get('/search', [TugasAkhirController::class, 'search'])->name('search');
            Route::get('/', [TugasAkhirController::class, 'index'])->name('index');
            Route::get('/create', [TugasAkhirController::class, 'create'])->name('create');
            Route::get('/buat', fn () => redirect()->route("mahasiswa.{$namespace}.create"));
            Route::post('/', [TugasAkhirController::class, 'store'])->name('store');
            Route::get('/{skripsi}', [TugasAkhirController::class, 'show'])->name('show');
            Route::post('/{skripsi}/publish', [TugasAkhirController::class, 'publish'])->name('publish');
            Route::get('/{skripsi}/edit', [TugasAkhirController::class, 'edit'])->name('edit');
            Route::put('/{skripsi}', [TugasAkhirController::class, 'update'])->name('update');
            Route::delete('/{skripsi}', [TugasAkhirController::class, 'destroy'])->name('destroy');

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
    };

    $registerTugasAkhirRoutes('skripsi', 'skripsi');
    $registerTugasAkhirRoutes('non-skripsi', 'non-skripsi');

    Route::get('/skripsi/{skripsi}/final-submission/{event}', [FinalSubmissionController::class, 'index'])->middleware(EnsureTugasAkhirRouteType::class)->name('final.index');
    Route::post('/skripsi/{skripsi}/final-submission/{event}', [FinalSubmissionController::class, 'store'])->middleware(EnsureTugasAkhirRouteType::class)->name('final.submit');
    Route::get('/non-skripsi/{skripsi}/final-submission/{event}', [FinalSubmissionController::class, 'index'])->middleware(EnsureTugasAkhirRouteType::class)->name('non-skripsi.final.index');
    Route::post('/non-skripsi/{skripsi}/final-submission/{event}', [FinalSubmissionController::class, 'store'])->middleware(EnsureTugasAkhirRouteType::class)->name('non-skripsi.final.submit');
});
