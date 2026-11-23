<?php

use App\Models\Periode;
use App\Models\ReviewerAssignment;
use App\Models\TahunAkademik;
use App\Models\TugasAkhir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function kaprodiUser(): User
{
    return User::query()->create([
        'name' => 'Kaprodi Sistem Informasi',
        'email' => 'kaprodi.crud@example.test',
        'password' => 'password',
        'role' => 'kaprodi',
    ]);
}

test('kaprodi can view paginated dosen list', function () {
    $kaprodi = kaprodiUser();

    User::query()->create([
        'name' => 'Dr. Sarah Wijaya',
        'email' => 'sarah.crud@example.test',
        'password' => 'password',
        'role' => 'dosen',
    ]);

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.dosen.index'))
        ->assertOk()
        ->assertSee('Dr. Sarah Wijaya')
        ->assertSee('Master Dosen')
        ->assertSee('Hapus')
        ->assertSee('class="stack-list"', false)
        ->assertDontSee('<aside class="stack-list">', false);
});

test('kaprodi can view dosen detail with mahasiswa bimbingan list', function () {
    $kaprodi = kaprodiUser();

    $dosen = User::query()->create([
        'name' => 'Dr. Sarah Wijaya',
        'email' => 'sarah.detail@example.test',
        'password' => 'password',
        'role' => 'dosen',
    ]);

    $mahasiswa = User::query()->create([
        'name' => 'Adrian Sterling',
        'email' => 'adrian.detail@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
        'nim' => '2021004592',
    ]);

    $tahunAkademik = TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    $periode = Periode::query()->create([
        'tahun_akademik_id' => $tahunAkademik->id,
        'kode_periode' => '20251',
        'semester' => 1,
        'sk_nomor' => 'SK-1',
        'tgl_mulai' => '2025-08-01',
        'tgl_selesai' => '2026-01-31',
        'is_aktif' => true,
        'status' => 'active',
    ]);
    $tugasAkhir = TugasAkhir::query()->create([
        'student_id' => $mahasiswa->id,
        'periode_id' => $periode->id,
        'title' => 'Sistem Informasi Desa',
        'type' => 'non_skripsi',
        'current_phase' => 'bimbingan_skripsi',
    ]);
    ReviewerAssignment::query()->create([
        'tugas_akhir_id' => $tugasAkhir->id,
        'lecturer_id' => $dosen->id,
        'role_type' => 'pembimbing_1',
    ]);

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.dosen.show', $dosen))
        ->assertOk()
        ->assertSee('Mahasiswa Bimbingan')
        ->assertSee('Adrian Sterling')
        ->assertSee('2021004592')
        ->assertSee('Judul Tugas Akhir')
        ->assertSee('Sistem Informasi Desa')
        ->assertSee('Non-Skripsi')
        ->assertSee('Arsipkan');
});

test('kaprodi create dosen form shows password toggle controls', function () {
    $kaprodi = kaprodiUser();

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.dosen.index'))
        ->assertOk()
        ->assertSee('data-password-toggle', false)
        ->assertSee('dosen-password', false)
        ->assertSee('dosen-password-confirmation', false);
});

test('kaprodi can create dosen account', function () {
    $kaprodi = kaprodiUser();

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.dosen.store'), [
            'name' => 'Dr. Bima Prakoso',
            'email' => 'bima.crud@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('kaprodi.dosen.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'Dr. Bima Prakoso',
        'email' => 'bima.crud@example.test',
        'role' => 'dosen',
    ]);
});

test('dosen creation ignores a forged privileged role', function () {
    $kaprodi = kaprodiUser();

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.dosen.store'), [
            'name' => 'Forged Role',
            'email' => 'forged-role@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'kaprodi',
        ])
        ->assertRedirect(route('kaprodi.dosen.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'forged-role@example.test',
        'role' => 'dosen',
    ]);
});

test('kaprodi can update dosen account', function () {
    $kaprodi = kaprodiUser();

    $dosen = User::query()->create([
        'name' => 'Dr. Retno Ayu',
        'email' => 'retno.old@example.test',
        'password' => 'password',
        'role' => 'dosen',
    ]);

    $this->actingAs($kaprodi)
        ->put(route('kaprodi.dosen.update', $dosen), [
            'name' => 'Dr. Retno Ayu Pratiwi',
            'email' => 'retno.new@example.test',
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect(route('kaprodi.dosen.show', $dosen));

    $this->assertDatabaseHas('users', [
        'id' => $dosen->id,
        'name' => 'Dr. Retno Ayu Pratiwi',
        'email' => 'retno.new@example.test',
        'role' => 'dosen',
    ]);
});

test('kaprodi can delete dosen account', function () {
    $kaprodi = kaprodiUser();

    $dosen = User::query()->create([
        'name' => 'Dr. Dosen Hapus',
        'email' => 'hapus.dosen@example.test',
        'password' => 'password',
        'role' => 'dosen',
    ]);

    $this->actingAs($kaprodi)
        ->delete(route('kaprodi.dosen.destroy', $dosen))
        ->assertRedirect(route('kaprodi.dosen.index'));

    $this->assertDatabaseMissing('users', [
        'id' => $dosen->id,
    ]);
});

test('non kaprodi cannot access dosen crud', function () {
    $mahasiswa = User::query()->create([
        'name' => 'Adrian Sterling',
        'email' => 'adrian.crud@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
        'nim' => '2021004592',
    ]);

    $this->actingAs($mahasiswa)
        ->get(route('kaprodi.dosen.index'))
        ->assertForbidden();
});
