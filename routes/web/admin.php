<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:kaprodi'])->group(function () use ($page, $crudSideCards, $adminFields, $tableAction) {
    Route::get('/dashboard', function () use ($page) {
        return view('admin.dashboard', $page('admin', 'Dashboard Admin', 'ADMIN • DASHBOARD', [
            'stats' => [
                ['label' => 'Program Studi', 'value' => '7', 'hint' => 'Multi program studi aktif'],
                ['label' => 'Hak Akses Kustom', 'value' => '12', 'hint' => 'Matrix capability aktif'],
                ['label' => 'Format Nilai', 'value' => '14', 'hint' => 'Lintas periode dan prodi'],
            ],
            'ops' => [
                ['title' => 'Review capability Kaprodi non-SI', 'description' => 'Validasi hak modifikasi dosen agar admin tidak jadi bottleneck.', 'status' => 'Perlu Cek'],
                ['title' => 'Sinkronisasi data master', 'description' => 'Import dosen dan mahasiswa untuk gelombang baru siap dijalankan.', 'status' => 'Terjadwal'],
            ],
            'cards' => [
                ['eyebrow' => 'Context', 'title' => 'Workspace Admin global', 'description' => 'Semua master data dan capability matrix dikelola di sini.'],
                ['eyebrow' => 'Mode', 'title' => 'Frontend-only CRUD', 'description' => 'Seluruh screen siap untuk wiring backend berikutnya.'],
            ],
        ]));
    })->name('dashboard');

    Route::get('/program-studi', function () use ($page, $tableAction, $crudSideCards) {
        return view('admin.program-studi.index', $page('admin', 'Program Studi', 'ADMIN • PROGRAM STUDI', [
            'rows' => [
                ['Sistem Informasi', 'S1', $tableAction('Detail', route('admin.program-studi.show', 1)) . ' | ' . $tableAction('Edit', route('admin.program-studi.edit', 1))],
                ['Teknik Informatika', 'S1', $tableAction('Detail', route('admin.program-studi.show', 2)) . ' | ' . $tableAction('Edit', route('admin.program-studi.edit', 2))],
            ],
            'sideCards' => $crudSideCards('Program studi multi-tenant', 'Data ini menjadi dasar pembatasan akses dan ownership workspace lintas prodi.'),
        ]));
    })->name('program-studi.index');
    Route::get('/program-studi/create', fn() => view('admin.program-studi.create', $page('admin', 'Tambah Program Studi', 'ADMIN • PROGRAM STUDI', ['fields' => $adminFields('program-studi'), 'sideCards' => $crudSideCards('Create flow', 'Tambah prodi baru siap sebelum integrasi backend.')])))->name('program-studi.create');
    Route::get('/program-studi/{id}', fn(string $id) => view('admin.program-studi.show', $page('admin', 'Detail Program Studi', 'ADMIN • PROGRAM STUDI', ['id' => $id, 'cards' => [['eyebrow' => 'Program Studi', 'title' => 'Sistem Informasi', 'description' => "Entity ID {$id}."], ['eyebrow' => 'Jenjang', 'title' => 'S1', 'description' => 'Status aktif dan dipakai di seluruh workspace.']], 'sideCards' => $crudSideCards('Usage', 'Program studi dipakai untuk segmentasi user, template, dan hak akses.')])))->name('program-studi.show');
    Route::get('/program-studi/{id}/edit', fn(string $id) => view('admin.program-studi.edit', $page('admin', 'Edit Program Studi', 'ADMIN • PROGRAM STUDI', ['id' => $id, 'fields' => $adminFields('program-studi'), 'sideCards' => $crudSideCards('Edit flow', 'Frontend edit state mencakup representasi delete/disable secara visual.')])))->name('program-studi.edit');

    Route::get('/hak-akses', function () use ($page, $crudSideCards, $tableAction) {
        return view('admin.hak-akses.index', $page('admin', 'Hak Akses', 'ADMIN • HAK AKSES', [
            'actions' => [['href' => route('admin.hak-akses.create'), 'label' => 'Tambah Hak Akses']],
            'rows' => [
                ['Kaprodi', 'Teknik Informatika', 'Modifikasi Dosen: Diizinkan', $tableAction('Detail', route('admin.hak-akses.show', 1)) . ' | ' . $tableAction('Edit', route('admin.hak-akses.edit', 1))],
                ['Kaprodi', 'Sistem Informasi', 'Modifikasi Dosen: Full', $tableAction('Detail', route('admin.hak-akses.show', 2)) . ' | ' . $tableAction('Edit', route('admin.hak-akses.edit', 2))],
            ],
            'sideCards' => $crudSideCards('Capability matrix', 'Representasi frontend capability per role/program studi sesuai kebutuhan delegasi admin.'),
        ]));
    })->name('hak-akses.index');
    Route::get('/hak-akses/create', fn() => view('admin.hak-akses.create', $page('admin', 'Tambah Hak Akses', 'ADMIN • HAK AKSES', ['fields' => $adminFields('hak-akses'), 'sideCards' => $crudSideCards('Create flow', 'Tambahkan rule capability baru untuk role dan program studi tertentu.')])))->name('hak-akses.create');
    Route::get('/hak-akses/{id}', fn(string $id) => view('admin.hak-akses.show', $page('admin', 'Detail Hak Akses', 'ADMIN • HAK AKSES', ['id' => $id, 'cards' => [['eyebrow' => 'Role', 'title' => 'Kaprodi TI', 'description' => 'Scope khusus non Sistem Informasi.'], ['eyebrow' => 'Capability', 'title' => 'Dosen: Modify allowed', 'description' => 'Create/edit/view diizinkan, delete mengikuti policy frontend.']], 'sideCards' => $crudSideCards('Policy note', 'Capability matrix nanti dapat dipetakan ke permission nyata di backend.')])))->name('hak-akses.show');
    Route::get('/hak-akses/{id}/edit', fn(string $id) => view('admin.hak-akses.edit', $page('admin', 'Edit Hak Akses', 'ADMIN • HAK AKSES', ['id' => $id, 'fields' => $adminFields('hak-akses'), 'sideCards' => $crudSideCards('Edit flow', 'Perubahan capability siap diwiring ke backend permission layer.')])))->name('hak-akses.edit');

    Route::get('/dosen', function () use ($page, $crudSideCards, $tableAction) {
        return view('admin.dosen.index', $page('admin', 'Data Dosen', 'ADMIN • DOSEN', [
            'rows' => [
                ['Dr. Sarah Wijaya', 'Sistem Informasi', 'Aktif', $tableAction('Detail', route('admin.dosen.show', 1)) . ' | ' . $tableAction('Edit', route('admin.dosen.edit', 1))],
                ['Dr. Bima Prakoso', 'Teknik Informatika', 'Aktif', $tableAction('Detail', route('admin.dosen.show', 2)) . ' | ' . $tableAction('Edit', route('admin.dosen.edit', 2))],
            ],
            'sideCards' => $crudSideCards('Master Dosen', 'Admin dan Kaprodi sama-sama punya coverage CRUD pada level frontend.'),
        ]));
    })->name('dosen.index');
    Route::get('/dosen/create', fn() => view('admin.dosen.create', $page('admin', 'Tambah Dosen', 'ADMIN • DOSEN', ['fields' => $adminFields('dosen'), 'sideCards' => $crudSideCards('Create flow', 'Input dosen baru berikut program studi dan status.')])))->name('dosen.create');
    Route::get('/dosen/{id}', fn(string $id) => view('admin.dosen.show', $page('admin', 'Detail Dosen', 'ADMIN • DOSEN', ['id' => $id, 'cards' => [['eyebrow' => 'Dosen', 'title' => 'Dr. Sarah Wijaya', 'description' => 'NIDN 0412345678 • Sistem Informasi'], ['eyebrow' => 'Status', 'title' => 'Aktif', 'description' => 'Bisa ditugaskan sebagai pembimbing dan penguji.']], 'sideCards' => $crudSideCards('Master entity', 'Profil dosen juga nanti bisa dihubungkan ke assignment dan grading.'), 'timeline' => [['title' => 'Import sync berhasil', 'description' => 'Data dosen tervalidasi di CSV gelombang terbaru.', 'meta' => '14 Apr 2026'], ['title' => 'Dipakai oleh 4 skripsi aktif', 'description' => 'Saat ini menjadi pembimbing dan penguji pada beberapa kasus.', 'meta' => 'Realtime mock']],])))->name('dosen.show');
    Route::get('/dosen/{id}/edit', fn(string $id) => view('admin.dosen.edit', $page('admin', 'Edit Dosen', 'ADMIN • DOSEN', ['id' => $id, 'fields' => $adminFields('dosen'), 'sideCards' => $crudSideCards('Edit flow', 'State edit siap untuk dihubungkan ke form request backend.')])))->name('dosen.edit');

    Route::get('/mahasiswa', function () use ($page, $crudSideCards, $tableAction) {
        return view('admin.mahasiswa.index', $page('admin', 'Data Mahasiswa', 'ADMIN • MAHASISWA', [
            'rows' => [
                ['Adrian Sterling', 'Sistem Informasi', 'Aktif', $tableAction('Detail', route('admin.mahasiswa.show', 1)) . ' | ' . $tableAction('Edit', route('admin.mahasiswa.edit', 1))],
                ['Arya Wiguna Saputra', 'Sistem Informasi', 'Aktif', $tableAction('Detail', route('admin.mahasiswa.show', 2)) . ' | ' . $tableAction('Edit', route('admin.mahasiswa.edit', 2))],
            ],
            'sideCards' => $crudSideCards('Master Mahasiswa', 'Admin dapat mengelola profil mahasiswa lintas program studi dari workspace global.'),
        ]));
    })->name('mahasiswa.index');
    Route::get('/mahasiswa/create', fn() => view('admin.mahasiswa.create', $page('admin', 'Tambah Mahasiswa', 'ADMIN • MAHASISWA', ['fields' => $adminFields('mahasiswa'), 'sideCards' => $crudSideCards('Create flow', 'Tambah mahasiswa baru siap untuk onboarding lintas prodi.')])))->name('mahasiswa.create');
    Route::get('/mahasiswa/{id}', fn(string $id) => view('admin.mahasiswa.show', $page('admin', 'Detail Mahasiswa', 'ADMIN • MAHASISWA', ['id' => $id, 'cards' => [['eyebrow' => 'Mahasiswa', 'title' => 'Adrian Sterling', 'description' => 'NIM 2021004592 • Sistem Informasi'], ['eyebrow' => 'Status', 'title' => 'Aktif', 'description' => 'Mahasiswa memiliki satu skripsi aktif.']], 'sideCards' => $crudSideCards('Student profile', 'Profil mahasiswa akan terhubung ke workflow skripsi aktif saat backend lengkap.')])))->name('mahasiswa.show');
    Route::get('/mahasiswa/{id}/edit', fn(string $id) => view('admin.mahasiswa.edit', $page('admin', 'Edit Mahasiswa', 'ADMIN • MAHASISWA', ['id' => $id, 'fields' => $adminFields('mahasiswa'), 'sideCards' => $crudSideCards('Edit flow', 'State edit mahasiswa siap dipakai saat backend form binding ditambahkan.')])))->name('mahasiswa.edit');

    Route::get('/import/dosen', fn() => view('admin.import.dosen', $page('admin', 'Import Dosen', 'ADMIN • IMPORT DOSEN', ['sideCards' => $crudSideCards('CSV import', 'Frontend import menyediakan upload point dan status parse placeholder.')])))->name('import.dosen');
    Route::get('/import/mahasiswa', fn() => view('admin.import.mahasiswa', $page('admin', 'Import Mahasiswa', 'ADMIN • IMPORT MAHASISWA', ['sideCards' => $crudSideCards('CSV import', 'Import mahasiswa siap dipisah dari import dosen sesuai kebutuhan operasional.')])))->name('import.mahasiswa');

    Route::get('/tahun-akademik', function () use ($page, $crudSideCards, $tableAction) {
        return view('admin.tahun-akademik.index', $page('admin', 'Tahun Akademik', 'ADMIN • TAHUN AKADEMIK', [
            'rows' => [
                ['2026 / 2027', '01 Aug 2026 - 31 Jul 2027', 'Aktif', $tableAction('Detail', route('admin.tahun-akademik.show', 1)) . ' | ' . $tableAction('Edit', route('admin.tahun-akademik.edit', 1))],
                ['2025 / 2026', '01 Aug 2025 - 31 Jul 2026', 'Arsip', $tableAction('Detail', route('admin.tahun-akademik.show', 2)) . ' | ' . $tableAction('Edit', route('admin.tahun-akademik.edit', 2))],
            ],
            'sideCards' => $crudSideCards('Academic year entity', 'Admin kini bisa tambah, lihat, edit, dan representasi delete untuk tahun akademik.'),
        ]));
    })->name('tahun-akademik.index');
    Route::get('/tahun-akademik/create', fn() => view('admin.tahun-akademik.create', $page('admin', 'Tambah Tahun Akademik', 'ADMIN • TAHUN AKADEMIK', ['fields' => $adminFields('tahun-akademik'), 'sideCards' => $crudSideCards('Create flow', 'Tahun akademik baru bisa ditambahkan dari Admin tanpa bergantung Kaprodi.')])))->name('tahun-akademik.create');
    Route::get('/tahun-akademik/{id}', fn(string $id) => view('admin.tahun-akademik.show', $page('admin', 'Detail Tahun Akademik', 'ADMIN • TAHUN AKADEMIK', ['id' => $id, 'cards' => [['eyebrow' => 'Tahun', 'title' => '2026 / 2027', 'description' => 'Status aktif untuk seluruh workspace.'], ['eyebrow' => 'SK Fakultas', 'title' => 'SK-FTI/2026/014', 'description' => 'Tersedia dokumen dan rentang masa berlaku.']], 'sideCards' => $crudSideCards('Periode terpisah', 'Periode dikelola di entitas sendiri, tidak ditanam di halaman ini.')])))->name('tahun-akademik.show');
    Route::get('/tahun-akademik/{id}/edit', fn(string $id) => view('admin.tahun-akademik.edit', $page('admin', 'Edit Tahun Akademik', 'ADMIN • TAHUN AKADEMIK', ['id' => $id, 'fields' => $adminFields('tahun-akademik'), 'sideCards' => $crudSideCards('Edit flow', 'Perubahan tahun akademik akan berpengaruh ke template dan periode aktif.')])))->name('tahun-akademik.edit');

    Route::get('/periode', function () use ($page, $crudSideCards, $tableAction) {
        return view('admin.periode.index', $page('admin', 'Periode Akademik', 'ADMIN • PERIODE', [
            'rows' => [
                ['20261', '2026 / 2027', 'Aktif', $tableAction('Detail', route('admin.periode.show', 1)) . ' | ' . $tableAction('Edit', route('admin.periode.edit', 1))],
                ['20262', '2026 / 2027', 'Draft', $tableAction('Detail', route('admin.periode.show', 2)) . ' | ' . $tableAction('Edit', route('admin.periode.edit', 2))],
            ],
            'sideCards' => $crudSideCards('Periode entity', 'Periode adalah entitas terpisah agar penilaian dan sidang lebih fleksibel.'),
        ]));
    })->name('periode.index');
    Route::get('/periode/create', fn() => view('admin.periode.create', $page('admin', 'Tambah Periode', 'ADMIN • PERIODE', ['fields' => $adminFields('periode'), 'sideCards' => $crudSideCards('Create flow', 'Tambah periode baru seperti 20261 atau 20262.')])))->name('periode.create');
    Route::get('/periode/{id}', fn(string $id) => view('admin.periode.show', $page('admin', 'Detail Periode', 'ADMIN • PERIODE', ['id' => $id, 'cards' => [['eyebrow' => 'Periode', 'title' => '20261', 'description' => 'Periode aktif semester 1.'], ['eyebrow' => 'Tahun Akademik', 'title' => '2026 / 2027', 'description' => 'Dipakai oleh template nilai dan sidang aktif.']], 'sideCards' => $crudSideCards('Usage', 'Periode dipakai di assignment template dan grade submission.')])))->name('periode.show');
    Route::get('/periode/{id}/edit', fn(string $id) => view('admin.periode.edit', $page('admin', 'Edit Periode', 'ADMIN • PERIODE', ['id' => $id, 'fields' => $adminFields('periode'), 'sideCards' => $crudSideCards('Edit flow', 'Periode aktif bisa dipindah atau ditutup secara visual dari sini.')])))->name('periode.edit');

    Route::get('/format-penilaian', function () use ($page, $crudSideCards, $tableAction) {
        return view('admin.format-penilaian.index', $page('admin', 'Format Nilai', 'ADMIN • TEMPLATE NILAI', [
            'rows' => [
                ['Template Sidang 20261', '20261', 'Published', $tableAction('Detail', route('admin.format-penilaian.show', 1)) . ' | ' . $tableAction('Edit', route('admin.format-penilaian.edit', 1))],
                ['Template Sidang 20262', '20262', 'Draft', $tableAction('Detail', route('admin.format-penilaian.show', 2)) . ' | ' . $tableAction('Edit', route('admin.format-penilaian.edit', 2))],
            ],
            'sideCards' => $crudSideCards('Template entity', 'Admin kini bisa create/show/edit template, bukan hanya modify state.'),
        ]));
    })->name('format-penilaian.index');
    Route::get('/format-penilaian/create', fn() => view('admin.format-penilaian.create', $page('admin', 'Tambah Format Nilai', 'ADMIN • TEMPLATE NILAI', ['fields' => $adminFields('format-penilaian'), 'sideCards' => $crudSideCards('Create flow', 'Template baru dapat disiapkan sebelum publish ke periode aktif.')])))->name('format-penilaian.create');
    Route::get('/format-penilaian/{id}', fn(string $id) => view('admin.format-penilaian.show', $page('admin', 'Detail Format Nilai', 'ADMIN • TEMPLATE NILAI', ['id' => $id, 'cards' => [['eyebrow' => 'Template', 'title' => 'Template Sidang 20261', 'description' => 'Proposal 20, Bimbingan 30, Sidang 50.'], ['eyebrow' => 'Status', 'title' => 'Published', 'description' => 'Terkait ke beberapa sidang aktif.']], 'sideCards' => $crudSideCards('Lock rule', 'Template yang sudah terpakai nanti perlu duplicate flow saat backend aktif.'), 'timeline' => [['title' => 'Draft dibuat', 'description' => 'Template awal disusun oleh admin.', 'meta' => '01 Apr 2026'], ['title' => 'Publish ke periode 20261', 'description' => 'Template aktif dipakai pada sidang berjalan.', 'meta' => '10 Apr 2026']]])))->name('format-penilaian.show');
    Route::get('/format-penilaian/{id}/edit', fn(string $id) => view('admin.format-penilaian.edit', $page('admin', 'Edit Format Nilai', 'ADMIN • TEMPLATE NILAI', ['id' => $id, 'fields' => $adminFields('format-penilaian'), 'sideCards' => $crudSideCards('Edit flow', 'Frontend edit state siap untuk dihubungkan ke duplicate/lock logic backend.')])))->name('format-penilaian.edit');
});
