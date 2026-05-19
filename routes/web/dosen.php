<?php

use App\Http\Controllers\Dosen\BimbinganController;
use App\Http\Controllers\Dosen\PenilaianController;
use App\Http\Controllers\Dosen\SidangRequestController;
use App\Http\Controllers\Dosen\SkripsiViewController;
use Illuminate\Support\Facades\Route;

Route::prefix('dosen')->name('dosen.')->middleware(['auth', 'role:dosen'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $lecturerId = $user->id;

        $gradingAssignments = \App\Models\ReviewerAssignment::query()
            ->with(['skripsi.student', 'skripsi.periode'])
            ->where('lecturer_id', $lecturerId)
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2', 'penguji_1', 'penguji_2'])
            ->whereHas('skripsi', fn ($q) => $q->whereIn('current_phase', ['sidang_skripsi', 'revisi_sidang_skripsi']))
            ->get();

        $gradingQueue = $gradingAssignments
            ->map(fn ($assignment) => [
                'student' => $assignment->skripsi?->student?->name ?? '-',
                'title' => $assignment->skripsi?->title ?? '-',
                'role' => str($assignment->role_type)->replace('_', ' ')->title()->toString(),
                'href' => route('dosen.penilaian.show', $assignment->skripsi),
            ])
            ->take(5);

        $activeSkripsiCount = \App\Models\ReviewerAssignment::query()
            ->where('lecturer_id', $lecturerId)
            ->whereHas('skripsi', fn ($q) => $q->whereNotIn('current_phase', ['skripsi_selesai']))
            ->distinct('skripsi_id')
            ->count('skripsi_id');

        $bimbinganCount = \App\Models\ReviewerAssignment::query()
            ->where('lecturer_id', $lecturerId)
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])
            ->distinct('skripsi_id')
            ->count('skripsi_id');

        $pendingSidangRequestCount = \App\Models\SidangRequest::query()
            ->where('lecturer_id', $lecturerId)
            ->where('status', 'submitted')
            ->count();

        $stats = [
            ['label' => 'Skripsi Aktif', 'value' => (string) $activeSkripsiCount, 'hint' => 'Tugas aktif Anda', 'href' => route('dosen.skripsi.index'), 'featured' => true],
            ['label' => 'Total Bimbingan', 'value' => (string) $bimbinganCount, 'hint' => 'Sebagai pembimbing', 'href' => route('dosen.skripsi.index')],
            ['label' => 'Menunggu Nilai', 'value' => (string) $gradingAssignments->count(), 'hint' => 'Sidang perlu dinilai', 'href' => route('dosen.penilaian.index')],
            ['label' => 'Pengajuan Sidang', 'value' => (string) $pendingSidangRequestCount, 'hint' => 'Menunggu approval Kaprodi', 'href' => route('dosen.sidang-request.index')],
        ];

        $navigation = app(\App\Services\RoleNavigationService::class);

        return view('dosen.dashboard', [
            'title' => 'Dashboard',
            'heading' => 'Dashboard',
            'crumbs' => 'DOSEN • DASHBOARD',
            'navItems' => $navigation->dosenNavItems(),
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'dosen',
            'primaryCta' => null,
            'stats' => $stats,
            'gradingQueue' => $gradingQueue,
        ]);
    })->name('dashboard');

    Route::get('/skripsi/search', [SkripsiViewController::class, 'search'])->name('skripsi.search');
    Route::get('/skripsi', [SkripsiViewController::class, 'index'])->name('skripsi.index');
    Route::get('/skripsi/{skripsi}', [SkripsiViewController::class, 'show'])->name('skripsi.show');

    Route::post('/skripsi/{skripsi}/bimbingan', [BimbinganController::class, 'store'])->name('bimbingan.store');
    Route::put('/bimbingan/{bimbingan}', [BimbinganController::class, 'update'])->name('bimbingan.update');
    Route::delete('/bimbingan/{bimbingan}', [BimbinganController::class, 'destroy'])->name('bimbingan.destroy');

    Route::get('/penilaian', [PenilaianController::class, 'index'])->name('penilaian.index');
    Route::get('/penilaian/{skripsi}', [PenilaianController::class, 'show'])->name('penilaian.show');
    Route::post('/penilaian/{skripsi}', [PenilaianController::class, 'store'])->name('penilaian.store');
    Route::post('/penilaian/{skripsi}/request-unlock', [PenilaianController::class, 'requestUnlock'])->name('penilaian.request-unlock');

    Route::get('/pengajuan-sidang-skripsi', [SidangRequestController::class, 'index'])->name('sidang-request.index');

    Route::post('/skripsi/{skripsi}/permohonan-sidang', [SidangRequestController::class, 'store'])->name('sidang-request.store');
});
