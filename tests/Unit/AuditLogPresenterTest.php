<?php

use App\Models\AuditLog;
use App\Models\Bimbingan;
use App\Models\DocumentVersion;
use App\Models\Grade;
use App\Models\Periode;
use App\Models\SidangRequest;
use App\Models\Skripsi;
use App\Models\User;
use App\Services\AuditLogPresenter;

it('presents a role change as a readable activity with small details', function () {
    $actor = new User(['name' => 'Vinno']);
    $target = new User(['name' => 'Budi Santoso']);
    $log = new AuditLog([
        'action' => 'user.roles_changed',
        'target_type' => User::class,
        'target_id' => 184,
        'before' => ['roles' => ['dosen']],
        'after' => ['roles' => ['kaprodi']],
        'ip_address' => '192.168.1.24',
    ]);
    $log->id = 10;
    $log->setRelation('actor', $actor);
    $log->setRelation('target', $target);

    expect(app(AuditLogPresenter::class)->present($log))->toMatchArray([
        'title' => 'Peran pengguna diubah',
        'summary' => 'Vinno mengubah peran Budi Santoso dari Dosen menjadi Kaprodi.',
        'category' => 'Akun',
        'target' => 'Budi Santoso',
        'before' => ['roles' => ['dosen']],
        'after' => ['roles' => ['kaprodi']],
        'ip_address' => '192.168.1.24',
        'reference' => 'User #184',
    ]);
});

it('presents audit maintenance without exposing raw action names', function () {
    $log = new AuditLog([
        'action' => 'audit.purged',
        'actor_email' => 'operator@example.test',
        'after' => ['deleted_count' => 25, 'reason' => 'manual'],
    ]);
    $log->id = 11;

    expect(app(AuditLogPresenter::class)->present($log))->toMatchArray([
        'title' => 'Log sistem dihapus',
        'summary' => 'operator@example.test menghapus 25 log sistem.',
        'category' => 'Pengaturan',
        'reference' => 'Log #11',
    ]);
});

it('removes sensitive metadata from displayed log details', function () {
    $log = new AuditLog([
        'action' => 'settings.updated',
        'before' => ['application_name' => 'Old', 'api_token' => 'hidden'],
        'after' => ['application_name' => 'New', 'nested' => ['secret' => 'hidden']],
    ]);

    expect(app(AuditLogPresenter::class)->present($log))->toMatchArray([
        'before' => ['application_name' => 'Old'],
        'after' => ['application_name' => 'New', 'nested' => []],
    ]);
});

it('presents representative topics and titles across every role workflow', function (string $action, ?string $targetType, string $topic, string $title) {
    $log = new AuditLog(['action' => $action, 'target_type' => $targetType, 'target_id' => $targetType ? 1 : null]);

    expect(app(AuditLogPresenter::class)->present($log))->toMatchArray([
        'category' => $topic,
        'title' => $title,
    ]);
})->with([
    'akun kaprodi' => ['kaprodi.mahasiswa.update', User::class, 'Akun', 'Akun diubah'],
    'keamanan superadmin' => ['superadmin.reauthenticated', User::class, 'Keamanan', 'Verifikasi ulang berhasil'],
    'skripsi mahasiswa' => ['model.created', Skripsi::class, 'Skripsi', 'Skripsi disimpan'],
    'bimbingan dosen' => ['model.updated', Bimbingan::class, 'Bimbingan', 'Bimbingan diubah'],
    'dokumen mahasiswa' => ['model.created', DocumentVersion::class, 'Dokumen', 'Versi dokumen disimpan'],
    'penilaian dosen' => ['model.updated', Grade::class, 'Penilaian', 'Penilaian diubah'],
    'sidang dosen' => ['model.created', SidangRequest::class, 'Sidang', 'Permohonan sidang disimpan'],
    'akademik kaprodi' => ['model.updated', Periode::class, 'Akademik', 'Periode diubah'],
    'pengaturan superadmin' => ['settings.updated', null, 'Pengaturan', 'Pengaturan aplikasi diperbarui'],
    'fallback lengkap' => ['notifications.read-all', null, 'Aktivitas Lain', 'Notifications Read All'],
]);
