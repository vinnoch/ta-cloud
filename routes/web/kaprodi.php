<?php

use App\Http\Controllers\Kaprodi\DosenController;
use App\Http\Controllers\Kaprodi\FormatPenilaianController;
use App\Http\Controllers\Kaprodi\FinalReviewController;
use App\Http\Controllers\Kaprodi\ImportUserController;
use App\Http\Controllers\Kaprodi\MahasiswaController;
use App\Http\Controllers\Kaprodi\NilaiController;
use App\Http\Controllers\Kaprodi\PeriodeController;
use App\Http\Controllers\Kaprodi\ProposalSubmissionController;
use App\Http\Controllers\Kaprodi\SkripsiController;
use App\Http\Controllers\Kaprodi\SidangRequestController;
use App\Http\Controllers\Kaprodi\TahunAkademikController;
use Illuminate\Support\Facades\Route;

Route::prefix('kaprodi')->name('kaprodi.')->middleware(['auth', 'role:kaprodi'])->group(function () use ($page, $crudSideCards, $adminFields, $tableAction, $sampleId, $skripsiDetailPage) {
        Route::get('/dashboard', function (\Illuminate\Http\Request $request) use ($page) {
        $periodes = \App\Models\Periode::query()->with('tahunAkademik')->orderByDesc('is_aktif')->orderByDesc('kode_periode')->get();
        $defaultPeriodeId = (int) ($periodes->firstWhere('is_aktif', true)?->id ?? $periodes->first()?->id ?? 0);
        $selectedPeriodeId = (int) $request->query('periode_id', $defaultPeriodeId);

        $baseSkripsiQuery = \App\Models\Skripsi::query()
            ->when($selectedPeriodeId > 0, fn ($query) => $query->where('periode_id', $selectedPeriodeId));

        $allSkripsi = (clone $baseSkripsiQuery)->get(['id', 'current_phase']);
        $skripsiIds = $allSkripsi->pluck('id');
        $phaseValues = $allSkripsi->map(fn ($item) => strtolower(str_replace(['-', '_'], ' ', (string) $item->current_phase)));

        $totalAktif = (clone $baseSkripsiQuery)
            ->where('current_phase', '!=', 'skripsi_selesai')
            ->count();

        $menungguAssign = (clone $baseSkripsiQuery)
            ->where('current_phase', 'sidang_proposal')
            ->whereHas('documentVersions', fn ($query) => $query->where('phase', 'proposal'))
            ->whereDoesntHave('assignments', function ($query) {
                $query->whereIn('role_type', ['pembimbing_1', 'pembimbing_2']);
            })
            ->count();

        $pendingSidangRequests = \App\Models\SidangRequest::query()
            ->where('status', 'submitted')
            ->when($selectedPeriodeId > 0, fn ($query) => $query->whereIn('skripsi_id', $skripsiIds))
            ->count();

        $proposalCount = (clone $baseSkripsiQuery)
            ->whereIn('current_phase', ['proposal', 'sidang_proposal'])
            ->whereHas('documentVersions', fn ($query) => $query->where('phase', 'proposal'))
            ->count();

        $proposalSubmittedCount = (clone $baseSkripsiQuery)
            ->where('current_phase', 'proposal')
            ->whereHas('documentVersions', fn ($query) => $query->where('phase', 'proposal'))
            ->count();

        $bimbinganCount = (clone $baseSkripsiQuery)
            ->whereIn('current_phase', ['bimbingan_skripsi'])
            ->count();

        $sidangSkripsiCount = (clone $baseSkripsiQuery)
            ->where('current_phase', 'sidang_skripsi')
            ->count();

        $reviewFinalCount = (clone $baseSkripsiQuery)
            ->where('current_phase', 'review_dokumen_final')
            ->count();

        $finalCount = (clone $baseSkripsiQuery)
            ->where('current_phase', 'skripsi_selesai')
            ->count();

        $periodQuery = $selectedPeriodeId > 0 ? ['periode_id' => $selectedPeriodeId] : [];

        return view('kaprodi.dashboard', $page('kaprodi', 'Dashboard Kaprodi', 'KAPRODI • DASHBOARD', [
            'title' => 'Dashboard Kaprodi',
            'periodes' => $periodes,
            'selectedPeriodeId' => $selectedPeriodeId,
            'stats' => [
                ['label' => 'Proposal Diajukan', 'value' => (string) $proposalSubmittedCount, 'hint' => 'Menunggu approval Kaprodi', 'href' => route('kaprodi.proposal-submissions.index', array_merge($periodQuery, ['reviewer_status' => 'pending_approval']))],
                ['label' => 'Menunggu Assign', 'value' => (string) $menungguAssign, 'hint' => 'Reviewer belum ditetapkan', 'href' => route('kaprodi.proposal-submissions.index', array_merge($periodQuery, ['reviewer_status' => 'unassigned']))],
                ['label' => 'Permohonan Sidang', 'value' => (string) $pendingSidangRequests, 'hint' => 'Menunggu persetujuan Kaprodi', 'href' => route('kaprodi.sidang-requests.index', $periodQuery)],
                ['label' => 'Review Dokumen Final', 'value' => (string) $reviewFinalCount, 'hint' => 'Menunggu validasi dokumen akhir', 'href' => route('kaprodi.final-reviews.index', $periodQuery)],
            ],
            'chartData' => [
                ['label' => 'Proposal', 'value' => $proposalCount],
                ['label' => 'Bimbingan Skripsi', 'value' => $bimbinganCount],
                ['label' => 'Sidang Skripsi', 'value' => $sidangSkripsiCount],
                ['label' => 'Review Dokumen Final', 'value' => $reviewFinalCount],
                ['label' => 'Skripsi Selesai', 'value' => $finalCount],
            ],
        ]));
    })->name('dashboard');


    Route::match(['GET', 'POST'], '/skripsi', [SkripsiController::class, 'index'])->name('skripsi.index');
    Route::get('/skripsi/{skripsi}/proposal', [SkripsiController::class, 'showProposal'])->name('skripsi.proposal');
    Route::get('/skripsi/{skripsi}/bimbingan', [SkripsiController::class, 'showBimbingan'])->name('skripsi.bimbingan');
    Route::get('/skripsi/{skripsi}/bimbingan/{bimbingan}', [SkripsiController::class, 'showBimbinganItem'])->name('skripsi.bimbingan.show');
    Route::get('/skripsi/{skripsi}/reviewers/search', [SkripsiController::class, 'searchReviewers'])->name('skripsi.reviewers.search');
    Route::post('/skripsi/{skripsi}/reviewers', [SkripsiController::class, 'storeReviewer'])->name('skripsi.reviewers.store');
    Route::post('/skripsi/{skripsi}/pembimbing', [SkripsiController::class, 'assignPembimbing'])->name('skripsi.assign.pembimbing');
    Route::post('/skripsi/{skripsi}/penguji', [SkripsiController::class, 'assignPenguji'])->name('skripsi.assign.penguji');
    Route::post('/skripsi/{skripsi}/permohonan-sidang/{sidangRequest}/approve', [SidangRequestController::class, 'approve'])->name('skripsi.sidang-request.approve');
    Route::post('/skripsi/{skripsi}/permohonan-sidang/{sidangRequest}/reject', [SidangRequestController::class, 'reject'])->name('skripsi.sidang-request.reject');
    Route::get('/skripsi/{skripsi}/logbook', [SkripsiController::class, 'downloadLogbook'])->name('skripsi.logbook');
    Route::get('/skripsi/{skripsi}/documents/{document}', [SkripsiController::class, 'downloadDocument'])->name('skripsi.documents.download');
    Route::post('/skripsi/{skripsi}/proposal/approve', [ProposalSubmissionController::class, 'approve'])->name('skripsi.proposal.approve');
    Route::post('/skripsi/{skripsi}/proposal/reject', [ProposalSubmissionController::class, 'reject'])->name('skripsi.proposal.reject');
    Route::post('/skripsi/{skripsi}/final-review/approve', [FinalReviewController::class, 'approve'])->name('skripsi.final-review.approve');
    Route::delete('/skripsi/{skripsi}/reviewers/{assignment}', [SkripsiController::class, 'unassignReviewer'])->name('skripsi.reviewers.destroy');
    Route::put('/skripsi/{skripsi}/status', [SkripsiController::class, 'updateStatus'])->name('skripsi.status.update');
    Route::put('/skripsi/{skripsi}/sidang-schedule', [SkripsiController::class, 'updateSidangSchedule'])->name('skripsi.sidang-schedule.update');
    Route::get('/skripsi/{skripsi}', [SkripsiController::class, 'show'])->name('skripsi.show');

    Route::get('/dosen', [DosenController::class, 'index'])->name('dosen.index');
    Route::post('/dosen', [DosenController::class, 'store'])->name('dosen.store');
    Route::get('/dosen/{dosen}', [DosenController::class, 'show'])->name('dosen.show');
    Route::put('/dosen/{dosen}', [DosenController::class, 'update'])->name('dosen.update');
    Route::post('/dosen/{dosen}/archive', [DosenController::class, 'archive'])->name('dosen.archive');
    Route::post('/dosen/{id}/restore', [DosenController::class, 'restore'])->name('dosen.restore');
    Route::delete('/dosen/{id}', [DosenController::class, 'destroy'])->name('dosen.destroy');

    Route::get('/mahasiswa', [MahasiswaController::class, 'index'])->name('mahasiswa.index');
    Route::post('/mahasiswa', [MahasiswaController::class, 'store'])->name('mahasiswa.store');
    Route::get('/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'show'])->name('mahasiswa.show');
    Route::put('/mahasiswa/{mahasiswa}/skripsi-status', [MahasiswaController::class, 'updateSkripsiStatus'])->name('mahasiswa.skripsi-status.update');
    Route::put('/mahasiswa/{mahasiswa}', [MahasiswaController::class, 'update'])->name('mahasiswa.update');
    Route::post('/mahasiswa/{mahasiswa}/archive', [MahasiswaController::class, 'archive'])->name('mahasiswa.archive');
    Route::post('/mahasiswa/{id}/restore', [MahasiswaController::class, 'restore'])->name('mahasiswa.restore');
    Route::delete('/mahasiswa/{id}', [MahasiswaController::class, 'destroy'])->name('mahasiswa.destroy');

    Route::get('/tahun-akademik', [TahunAkademikController::class, 'index'])->name('tahun-akademik.index');
    Route::post('/tahun-akademik', [TahunAkademikController::class, 'store'])->name('tahun-akademik.store');
    Route::get('/tahun-akademik/{tahunAkademik}', [TahunAkademikController::class, 'show'])->name('tahun-akademik.show');
    Route::put('/tahun-akademik/{tahunAkademik}', [TahunAkademikController::class, 'update'])->name('tahun-akademik.update');
    Route::post('/tahun-akademik/{tahunAkademik}/archive', [TahunAkademikController::class, 'archive'])->name('tahun-akademik.archive');
    Route::delete('/tahun-akademik/{tahunAkademik}', [TahunAkademikController::class, 'destroy'])->name('tahun-akademik.destroy');

    Route::get('/periode', [PeriodeController::class, 'index'])->name('periode.index');
    Route::post('/periode', [PeriodeController::class, 'store'])->name('periode.store');
    Route::get('/periode/{periode}', [PeriodeController::class, 'show'])->name('periode.show');
    Route::put('/periode/{periode}', [PeriodeController::class, 'update'])->name('periode.update');
    Route::post('/periode/{periode}/archive', [PeriodeController::class, 'archive'])->name('periode.archive');
    Route::delete('/periode/{periode}', [PeriodeController::class, 'destroy'])->name('periode.destroy');

    Route::get('/format-penilaian', [FormatPenilaianController::class, 'index'])->name('formats.index');
    Route::get('/nilai', [NilaiController::class, 'index'])->name('nilai.index');
    Route::get('/format-penilaian/create', [FormatPenilaianController::class, 'create'])->name('formats.create');
    Route::post('/format-penilaian', [FormatPenilaianController::class, 'store'])->name('formats.store');
    Route::get('/format-penilaian/{format}/grades/{skripsi}', [FormatPenilaianController::class, 'showGrades'])->name('formats.grades.show');
    Route::get('/format-penilaian/{format}', [FormatPenilaianController::class, 'show'])->name('formats.show');
    Route::get('/format-penilaian/{format}/edit', [FormatPenilaianController::class, 'edit'])->name('formats.edit');
    Route::put('/format-penilaian/{format}', [FormatPenilaianController::class, 'update'])->name('formats.update');
    Route::post('/format-penilaian/{format}/duplicate', [FormatPenilaianController::class, 'duplicate'])->name('formats.duplicate');
    Route::delete('/format-penilaian/{format}', [FormatPenilaianController::class, 'destroy'])->name('formats.destroy');

    Route::get('/import/dosen', [ImportUserController::class, 'showDosen'])->name('import.dosen');
    Route::post('/import/dosen', [ImportUserController::class, 'importDosen'])->name('import.dosen.store');
    Route::get('/import/mahasiswa', [ImportUserController::class, 'showMahasiswa'])->name('import.mahasiswa');
    Route::post('/import/mahasiswa', [ImportUserController::class, 'importMahasiswa'])->name('import.mahasiswa.store');

    Route::get('/permohonan-sidang', [SidangRequestController::class, 'index'])->name('sidang-requests.index');
    Route::get('/pengajuan-proposal', [ProposalSubmissionController::class, 'index'])->name('proposal-submissions.index');
    Route::get('/review-dokumen-final', [FinalReviewController::class, 'index'])->name('final-reviews.index');

    Route::get('/fase', function () use ($page) {
        return view('kaprodi.fase.index', $page('kaprodi', 'Phase Control Board', 'KAPRODI • PHASE CONTROL', [
            'phases' => [
                ['eyebrow' => 'Proposal', 'title' => '32 mahasiswa', 'description' => 'Masih menunggu assignment pembimbing.'],
                ['eyebrow' => 'Bimbingan', 'title' => '104 mahasiswa', 'description' => 'Aktif di siklus revisi dan milestone meeting.'],
                ['eyebrow' => 'Pasca Sidang', 'title' => '21 mahasiswa', 'description' => 'Menunggu approval seluruh reviewer.'],
                ['eyebrow' => 'Final', 'title' => '27 mahasiswa', 'description' => 'Siap publish ke library atau close-out final.'],
            ],
        ]));
    })->name('fase.index');
    Route::get('/keputusan/{id}', function (string $id) use ($page) {
        return view('kaprodi.keputusan.show', $page('kaprodi', 'Keputusan Akhir Sidang', 'KAPRODI • FINAL DECISION', [
            'cards' => [
                ['eyebrow' => 'Mahasiswa', 'title' => 'Arya Wiguna Saputra', 'description' => "Final decision for thesis {$id}."],
                ['eyebrow' => 'Reviewer Consensus', 'title' => '2/2 reviewer approved', 'description' => 'Semua reviewer menyatakan layak lulus.'],
            ],
            'decisionCard' => ['eyebrow' => 'Decision', 'title' => 'Rekomendasi: Lulus', 'description' => 'Nilai akhir 88.5 dengan predikat A.'],
        ]));
    })->name('keputusan.show');
    Route::get('/library', function () use ($page) {
        return view('kaprodi.library.index', $page('kaprodi', 'Publish to Library', 'KAPRODI • LIBRARY CONTROL', [
            'items' => [
                ['student' => 'Arya Wiguna Saputra', 'title' => 'Implementasi Arsitektur Microservices', 'document' => 'final_microservices_arya.pdf', 'status' => 'Siap Publish'],
                ['student' => 'Nadya Putri', 'title' => 'Perancangan Sistem Monitoring PKL', 'document' => 'final_monitoring_pkl.pdf', 'status' => 'Butuh Verifikasi'],
            ],
        ]));
    })->name('library.index');
});
