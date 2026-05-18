<?php

use App\Services\RoleNavigationService;
use Illuminate\Support\Facades\Route;

$sampleId = 101;
$sampleMeetingId = 12;
$sampleSlug = 'arsitektur-microservices-cloud';

$footerItems = [
    ['label' => 'Bantuan', 'href' => '#', 'icon' => 'partials.icons.help'],
    ['label' => 'Keluar', 'href' => '#', 'icon' => 'partials.icons.logout', 'danger' => true],
];

$tableAction = fn(string $label, string $href) => '<a class="text-link" href="' . $href . '">' . $label . '</a>';
$tableActionPair = fn(string $firstLabel, string $firstHref, string $secondLabel, string $secondHref) => '<a class="text-link" href="' . $firstHref . '">' . $firstLabel . '</a> | <a class="text-link" href="' . $secondHref . '">' . $secondLabel . '</a>';

$crudSideCards = fn(string $context, string $note) => [
    ['eyebrow' => 'Frontend Only', 'title' => $context, 'description' => $note],
    ['eyebrow' => 'Delete State', 'title' => 'Representasi hapus tersedia', 'description' => 'Delete saat ini berupa state aksi/konfirmasi frontend, belum persistence backend.'],
];

$adminFields = fn(string $entity) => match ($entity) {
    'program-studi' => [
        ['label' => 'Nama Program Studi', 'placeholder' => 'Contoh: Sistem Informasi'],
        ['label' => 'Kode Program Studi', 'placeholder' => 'SI'],
        ['label' => 'Jenjang', 'placeholder' => 'S1'],
        ['label' => 'Status', 'placeholder' => 'Aktif'],
    ],
    'hak-akses' => [
        ['label' => 'Role Target', 'placeholder' => 'Kaprodi'],
        ['label' => 'Program Studi', 'placeholder' => 'Teknik Informatika'],
        ['label' => 'Capability', 'type' => 'textarea', 'placeholder' => 'Contoh: Boleh modifikasi Dosen, tidak boleh hapus Mahasiswa'],
    ],
    'dosen' => [
        ['label' => 'Nama Dosen', 'placeholder' => 'Dr. Sarah Wijaya'],
        ['label' => 'NIDN', 'placeholder' => '0412345678'],
        ['label' => 'Program Studi', 'placeholder' => 'Sistem Informasi'],
        ['label' => 'Status', 'placeholder' => 'Aktif'],
    ],
    'mahasiswa' => [
        ['label' => 'Nama Mahasiswa', 'placeholder' => 'Adrian Sterling'],
        ['label' => 'NIM', 'placeholder' => '2021004592'],
        ['label' => 'Program Studi', 'placeholder' => 'Sistem Informasi'],
        ['label' => 'Status', 'placeholder' => 'Aktif'],
    ],
    'tahun-akademik' => [
        ['label' => 'Nama Tahun Akademik', 'placeholder' => '2026 / 2027'],
        ['label' => 'Nomor SK', 'placeholder' => 'SK-FTI/2026/014'],
        ['label' => 'Berlaku Dari', 'value' => '2026-08-01'],
        ['label' => 'Berlaku Sampai', 'value' => '2027-07-31'],
    ],
    'periode' => [
        ['label' => 'Kode Periode', 'placeholder' => '20261'],
        ['label' => 'Tahun Akademik', 'placeholder' => '2026 / 2027'],
        ['label' => 'Status', 'placeholder' => 'Aktif'],
    ],
    default => [
        ['label' => 'Nama Format Nilai', 'placeholder' => 'Template Sidang 20261'],
        ['label' => 'Periode', 'placeholder' => '20261'],
        ['label' => 'Bobot Komponen', 'type' => 'textarea', 'placeholder' => 'Proposal 20, Bimbingan 30, Sidang 50'],
        ['label' => 'Status', 'placeholder' => 'Draft'],
    ],
};

$navFactory = function (string $role) use ($sampleId, $footerItems) {
    $navigation = app(RoleNavigationService::class);

    return match ($role) {
        'admin' => [
            'navItems' => [
                ['label' => 'Dashboard', 'href' => route('admin.dashboard'), 'active' => 'admin.dashboard', 'icon' => 'partials.icons.grid'],
                ['label' => 'Program Studi', 'href' => route('admin.program-studi.index'), 'active' => 'admin.program-studi.*', 'icon' => 'partials.icons.file'],
                ['label' => 'Hak Akses', 'href' => route('admin.hak-akses.index'), 'active' => 'admin.hak-akses.*', 'icon' => 'partials.icons.phase-shield'],
                ['label' => 'Master Dosen', 'href' => route('admin.dosen.index'), 'active' => 'admin.dosen.*', 'icon' => 'partials.icons.chat'],
                ['label' => 'Master Mahasiswa', 'href' => route('admin.mahasiswa.index'), 'active' => 'admin.mahasiswa.*', 'icon' => 'partials.icons.folder'],
                ['label' => 'Tahun Akademik', 'href' => route('admin.tahun-akademik.index'), 'active' => 'admin.tahun-akademik.*', 'icon' => 'partials.icons.phase-flag'],
                ['label' => 'Periode', 'href' => route('admin.periode.index'), 'active' => 'admin.periode.*', 'icon' => 'partials.icons.clipboard'],
                ['label' => 'Format Nilai', 'href' => route('admin.format-penilaian.index'), 'active' => 'admin.format-penilaian.*', 'icon' => 'partials.icons.clipboard'],
                ['label' => 'Import Dosen', 'href' => route('admin.import.dosen'), 'active' => 'admin.import.dosen', 'icon' => 'partials.icons.download'],
                ['label' => 'Import Mahasiswa', 'href' => route('admin.import.mahasiswa'), 'active' => 'admin.import.mahasiswa', 'icon' => 'partials.icons.download'],
            ],
            'primaryCta' => ['label' => 'Tambah Program Studi', 'href' => route('admin.program-studi.create')],
            'navFooterItems' => $footerItems,
            'navRole' => 'admin',
        ],
        'mahasiswa' => [
            'navItems' => $navigation->mahasiswaNavItems(null, $sampleId),
            'primaryCta' => null,
            'navFooterItems' => $footerItems,
            'navRole' => 'mahasiswa',
        ],
        'dosen' => [
            'navItems' => [
                ['label' => 'Dashboard', 'href' => route('dosen.dashboard'), 'active' => 'dosen.dashboard', 'icon' => 'partials.icons.grid'],
                ['label' => 'Antrian', 'href' => route('dosen.antrian.index'), 'active' => 'dosen.antrian.*', 'icon' => 'partials.icons.file'],
                ['label' => 'Review', 'href' => route('dosen.review.index', ['id' => $sampleId]), 'active' => 'dosen.review.*', 'icon' => 'partials.icons.phase-chat'],
                ['label' => 'Penilaian', 'href' => route('dosen.penilaian.index'), 'active' => 'dosen.penilaian.*', 'icon' => 'partials.icons.clipboard'],
            ],
            'primaryCta' => null,
            'navFooterItems' => $footerItems,
            'navRole' => 'dosen',
        ],
        'kaprodi' => [
            'navItems' => $navigation->kaprodiNavItems(),
            'primaryCta' => null,
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'kaprodi',
        ],
        default => [
            'navItems' => [
                ['label' => 'Overview', 'href' => route('dashboard.index'), 'active' => 'dashboard.*', 'icon' => 'partials.icons.grid'],
                ['label' => 'Admin', 'href' => route('admin.dashboard'), 'active' => 'admin.*', 'icon' => 'partials.icons.phase-shield'],
                ['label' => 'Master Mahasiswa', 'href' => route('mahasiswa.skripsi.index'), 'active' => 'mahasiswa.*', 'icon' => 'partials.icons.file'],
                ['label' => 'Master Dosen', 'href' => route('dosen.dashboard'), 'active' => 'dosen.*', 'icon' => 'partials.icons.chat'],
                ['label' => 'Kaprodi', 'href' => route('kaprodi.dashboard'), 'active' => 'kaprodi.*', 'icon' => 'partials.icons.phase-flag'],
                ['label' => 'Library', 'href' => route('library.index'), 'active' => 'library.*', 'icon' => 'partials.icons.folder'],
            ],
            'primaryCta' => ['label' => 'Buka Prototype Mahasiswa', 'href' => route('skripsi.detail')],
            'navFooterItems' => $footerItems,
            'navRole' => 'global',
        ],
    };
};

$page = function (string $role, string $heading, string $crumbs, array $extra = []) use ($navFactory) {
    return array_merge([
        'title' => $heading,
        'heading' => $heading,
        'crumbs' => $crumbs,
    ], $navFactory($role), $extra);
};

$dashboardForRole = fn(string $role): string => match ($role) {
    'mahasiswa' => route('mahasiswa.skripsi.index'),
    'dosen' => route('dosen.dashboard'),
    'kaprodi' => route('kaprodi.dashboard'),
    default => route('dashboard.index'),
};

$skripsiDetailPage = function (string $role, string $heading, string $crumbs, string $id = '101', array $overrides = []) use ($page) {
    $base = [
        'id' => $id,
        'student' => [
            'avatar' => 'AS',
            'name' => 'Adrian Sterling',
            'nim' => '2021004592',
            'program' => 'Sistem Informasi',
            'status' => 'AKTIF',
            'title' => '"Analisis Sentimen Pengguna Aplikasi E-Commerce Menggunakan Algoritma Support Vector Machine (SVM)"',
        ],
        'advisor' => [
            'name' => 'Dr. Danang Murdiyanto, S.T,. M.T.',
            'nidn' => 'NIDN: 0412345678',
            'sessions' => '12 sesi',
            'completion' => '65%',
        ],
        'phases' => [
            ['label' => 'Proposal', 'state' => 'done', 'caption' => null],
            ['label' => 'Bimbingan', 'state' => 'current', 'caption' => 'Tahap Sekarang'],
            ['label' => 'Pasca Sidang', 'state' => 'review', 'caption' => null],
            ['label' => 'Hasil Akhir', 'state' => 'upcoming', 'caption' => null],
        ],
        'history' => [
            ['date' => '24 Okt 2023', 'time' => '09:30 WIB', 'topic' => 'Diskusi Metodologi', 'summary' => 'Penentuan parameter SVM dan kernel RBF', 'note' => '"Pastikan dataset sudah di-balancing menggunakan SMOTE sebelum masuk ke tahap training model."', 'status' => 'Selesai'],
            ['date' => '12 Okt 2023', 'time' => '14:00 WIB', 'topic' => 'Revisi Bab 1', 'summary' => 'Latar belakang dan urgensi penelitian', 'note' => '"Perjelas gap penelitian antara studi terdahulu dengan yang sedang dikerjakan."', 'status' => 'Selesai'],
        ],
        'historyAction' => ['label' => 'Unduh Logbook', 'href' => '#'],
        'historyFooterAction' => ['label' => 'Tampilkan Semua Histori', 'href' => '#'],
        'validation' => [
            'title' => 'Validasi Tahap',
            'message' => 'Minimum 12 pertemuan bimbingan sudah terpenuhi dan progres siap dipantau menuju fase pasca sidang.',
            'hint' => 'Menuju: Tahap Pasca Sidang',
            'actionLabel' => 'Lihat Kesiapan Fase',
            'actionHref' => '#',
        ],
        'files' => ['Draf_Skripsi_Rev2.pdf', 'Dataset_SVM.csv'],
    ];

    return $page($role, $heading, $crumbs, array_merge($base, $overrides));
};

require __DIR__ . '/web/global.php';
require __DIR__ . '/web/admin.php';
require __DIR__ . '/web/mahasiswa.php';
require __DIR__ . '/web/dosen.php';
require __DIR__ . '/web/kaprodi.php';
require __DIR__ . '/web/notifications.php';
require __DIR__ . '/auth.php';
