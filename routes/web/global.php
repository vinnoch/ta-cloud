<?php

use App\Http\Controllers\DocumentAccessController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->intended(match (auth()->user()->role) {
            'mahasiswa' => route('mahasiswa.dashboard'),
            'dosen' => route('dosen.dashboard'),
            'kaprodi' => route('kaprodi.dashboard'),
            default => route('dashboard.index'),
        });
    }

    return redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/overview', function () {
        if (auth()->user()->role !== 'kaprodi') {
            abort(403);
        }

        return view('dashboard.index', [
            'title' => 'TA Cloud Frontend',
            'heading' => 'TA Cloud Frontend',
            'crumbs' => 'SYSTEM • OVERVIEW',
            'roleCards' => [
                ['tag' => 'Mahasiswa', 'title' => 'Workspace Mahasiswa', 'description' => 'Progress personal, revisi, bimbingan, dan final submission.', 'hint' => 'Tanpa metrik operasional lintas user', 'href' => route('mahasiswa.dashboard')],
                ['tag' => 'Dosen', 'title' => 'Workspace Dosen', 'description' => 'Queue review, detail skripsi reviewer, dan penilaian sidang.', 'hint' => 'Perlu Review hanya tampil di sini', 'href' => route('dosen.dashboard')],
                ['tag' => 'Kaprodi', 'title' => 'Workspace Kaprodi', 'description' => 'Full CRUD master data, template, dan global view skripsi.', 'hint' => 'Semua fitur monitoring ada di sini', 'href' => route('kaprodi.dashboard')],
            ],
            'featureCards' => [
                ['label' => 'Sistem Skripsi', 'description' => 'CRUD flow pengajuan', 'completed' => true],
                ['label' => 'Bimbingan', 'description' => 'Log bimbingan dan review', 'completed' => true],
                ['label' => 'Dokumen', 'description' => 'Upload file dan versi', 'completed' => true],
                ['label' => 'Jadwal & Review', 'description' => 'Penjadwalan sidang', 'completed' => true],
                ['label' => 'Penilaian & Final', 'description' => 'Bobot nilai dan transkrip', 'completed' => true],
                ['label' => 'Master Data', 'description' => 'Kelola prodi, periode', 'completed' => true],
            ],
            'routeGroups' => [
                ['title' => 'Mahasiswa', 'description' => 'Skripsi list, bimbingan detail, doc versi, sidang.', 'count' => '4+', 'scope' => 'Personal'],
                ['title' => 'Dosen', 'description' => 'Queue reviewer, input nilai, bimbingan detail.', 'count' => '3+', 'scope' => 'Assigned'],
                ['title' => 'Kaprodi', 'description' => 'Tahun Akademik, Periode, Dosen, Mahasiswa, Template.', 'count' => '10+', 'scope' => 'Global'],
                ['title' => 'Library', 'description' => 'Index pencarian dan detail arsip publik.', 'count' => '2', 'scope' => 'Guest/Auth'],
            ],
        ]);
    })->name('dashboard.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/documents/{document}/preview', [DocumentAccessController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/download', [DocumentAccessController::class, 'download'])->name('documents.download');
});

Route::prefix('library')->name('library.')->group(function () {
    Route::get('/', function () {
        return view('library.index', [
            'title' => 'Library Skripsi',
            'heading' => 'Library Skripsi',
            'crumbs' => 'LIBRARY • INDEX',
            'libraryStats' => [
                ['label' => 'Total Skripsi', 'value' => '1,240'],
                ['label' => 'Tahun Aktif', 'value' => '2024'],
            ],
            'rows' => [
                [
                    'Arsitektur Microservices Cloud',
                    'Adrian Sterling',
                    'Sistem Informasi',
                    '<a class="text-link" href="' . route('library.show', 'arsitektur-microservices-cloud') . '">Detail</a>',
                ],
            ],
            'filters' => [
                ['eyebrow' => 'Filter', 'title' => 'Tahun Lulus', 'description' => '2023, 2024'],
            ],
        ]);
    })->name('index');

    Route::get('/{slug}', function () {
        return response('<html><body>Detail Library Skripsi</body></html>');
    })->name('show');
});
