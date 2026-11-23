<?php

use App\Http\Controllers\Dosen\BimbinganController;
use App\Http\Controllers\Dosen\PenilaianController;
use App\Http\Controllers\Dosen\SidangRequestController;
use App\Http\Controllers\Dosen\TugasAkhirViewController;
use App\Http\Middleware\EnsureTugasAkhirRouteType;
use App\Models\ReviewerAssignment;
use App\Models\SidangRequest;
use App\Services\RoleNavigationService;
use Illuminate\Support\Facades\Route;

Route::prefix('dosen')->name('dosen.')->middleware(['auth', 'role:dosen'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        $lecturerId = $user->id;

        $gradingAssignments = ReviewerAssignment::query()
            ->with(['tugasAkhir.student', 'tugasAkhir.periode'])
            ->where('lecturer_id', $lecturerId)
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2', 'penguji_1', 'penguji_2'])
            ->whereHas('tugasAkhir', fn ($q) => $q->whereIn('current_phase', ['sidang_skripsi', 'revisi_sidang_skripsi']))
            ->get();

        $gradingQueue = $gradingAssignments
            ->map(fn ($assignment) => [
                'student' => $assignment->tugasAkhir?->student?->name ?? '-',
                'title' => $assignment->tugasAkhir?->title ?? '-',
                'type_label' => $assignment->tugasAkhir?->type === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi',
                'role' => str($assignment->role_type)->replace('_', ' ')->title()->toString(),
                'href' => route('dosen.penilaian.show', $assignment->tugasAkhir),
            ])
            ->take(5);

        $activeSkripsiCount = ReviewerAssignment::query()
            ->where('lecturer_id', $lecturerId)
            ->whereHas('tugasAkhir', fn ($q) => $q->whereNotIn('current_phase', ['skripsi_selesai']))
            ->distinct('tugas_akhir_id')
            ->count('tugas_akhir_id');

        $bimbinganCount = ReviewerAssignment::query()
            ->where('lecturer_id', $lecturerId)
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])
            ->distinct('tugas_akhir_id')
            ->count('tugas_akhir_id');

        $sidangRequestCount = SidangRequest::query()
            ->where('lecturer_id', $lecturerId)
            ->count();

        $stats = [
            ['label' => 'Tugas Akhir Aktif', 'value' => (string) $activeSkripsiCount, 'hint' => 'Tugas aktif Anda', 'href' => null, 'featured' => true],
            ['label' => 'Total Bimbingan', 'value' => (string) $bimbinganCount, 'hint' => 'Sebagai pembimbing', 'href' => route('dosen.skripsi.index')],
            ['label' => 'Menunggu Nilai', 'value' => (string) $gradingAssignments->count(), 'hint' => 'Sidang perlu dinilai', 'href' => route('dosen.penilaian.index')],
            ['label' => 'Pengajuan Sidang', 'value' => (string) $sidangRequestCount, 'hint' => 'Total pengajuan Anda', 'href' => route('dosen.sidang-request.index')],
        ];

        $navigation = app(RoleNavigationService::class);

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

    Route::get('/proposal', [TugasAkhirViewController::class, 'proposals'])->name('proposal.index');
    $registerTugasAkhirRoutes = function (string $slug, string $namespace): void {
        Route::prefix($slug)->name($namespace.'.')->middleware(EnsureTugasAkhirRouteType::class)->group(function (): void {
            Route::get('/search', [TugasAkhirViewController::class, 'search'])->name('search');
            Route::get('/', [TugasAkhirViewController::class, 'index'])->name('index');
            Route::get('/{skripsi}', [TugasAkhirViewController::class, 'show'])->name('show');
            Route::post('/{skripsi}/bimbingan', [BimbinganController::class, 'store'])->name('bimbingan.store');
            Route::post('/{skripsi}/permohonan-sidang', [SidangRequestController::class, 'store'])->name('sidang-request.store');
            Route::delete('/{skripsi}/permohonan-sidang/{sidangRequest}', [SidangRequestController::class, 'destroy'])->name('sidang-request.destroy');
        });
    };

    $registerTugasAkhirRoutes('skripsi', 'skripsi');
    $registerTugasAkhirRoutes('non-skripsi', 'non-skripsi');

    // Compatibility names used by existing forms, notifications, and tests.
    Route::post('/skripsi/{skripsi}/bimbingan', [BimbinganController::class, 'store'])->middleware(EnsureTugasAkhirRouteType::class)->name('bimbingan.store');
    Route::post('/skripsi/{skripsi}/permohonan-sidang', [SidangRequestController::class, 'store'])->middleware(EnsureTugasAkhirRouteType::class)->name('sidang-request.store');
    Route::delete('/skripsi/{skripsi}/permohonan-sidang/{sidangRequest}', [SidangRequestController::class, 'destroy'])->middleware(EnsureTugasAkhirRouteType::class)->name('sidang-request.destroy');
    Route::put('/bimbingan/{bimbingan}', [BimbinganController::class, 'update'])->name('bimbingan.update');
    Route::delete('/bimbingan/{bimbingan}', [BimbinganController::class, 'destroy'])->name('bimbingan.destroy');

    Route::get('/penilaian', [PenilaianController::class, 'index'])->name('penilaian.index');
    Route::get('/penilaian/{skripsi}', [PenilaianController::class, 'show'])->name('penilaian.show');
    Route::post('/penilaian/{skripsi}', [PenilaianController::class, 'store'])->name('penilaian.store');
    Route::post('/penilaian/{skripsi}/request-unlock', [PenilaianController::class, 'requestUnlock'])->name('penilaian.request-unlock');

    Route::get('/pengajuan-sidang-skripsi', [SidangRequestController::class, 'index'])->name('sidang-request.index');

});
