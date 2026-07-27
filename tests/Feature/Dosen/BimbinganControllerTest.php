<?php

use App\Models\Bimbingan;
use App\Models\Periode;
use App\Models\ReviewerAssignment;
use App\Models\Skripsi;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dosen = User::factory()->dosen()->create();
    $this->otherDosen = User::factory()->dosen()->create();
    $this->mahasiswa = User::factory()->mahasiswa()->create();
    $tahun = TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    Periode::query()->create([
        'id' => 1, 'tahun_akademik_id' => $tahun->id, 'kode_periode' => '20251', 'semester' => 1,
        'sk_nomor' => 'SK-1', 'tgl_mulai' => '2025-08-01', 'tgl_selesai' => '2026-01-31', 'is_aktif' => true, 'status' => 'active',
    ]);
    $this->skripsi = Skripsi::query()->create(['student_id' => $this->mahasiswa->id, 'periode_id' => 1, 'title' => 'TA', 'type' => 'skripsi', 'current_phase' => 'bimbingan_skripsi']);
    ReviewerAssignment::query()->create(['skripsi_id' => $this->skripsi->id, 'lecturer_id' => $this->dosen->id, 'role_type' => 'pembimbing_1']);
});

it('creates updates deletes bimbingan for assigned dosen', function () {
    $this->actingAs($this->dosen)->get(route('dosen.skripsi.show', $this->skripsi))->assertOk()->assertSee('Tambah Bimbingan');

    $this->actingAs($this->dosen)->post(route('dosen.bimbingan.store', $this->skripsi), [
        'meeting_date' => '2026-05-05',
        'lecturer_notes' => 'Lecturer notes',
    ])->assertRedirect();

    $bimbingan = Bimbingan::query()->first();
    expect($bimbingan)->not->toBeNull();

    $this->actingAs($this->dosen)->get(route('dosen.skripsi.show', $this->skripsi))->assertOk()->assertSee('data-bimbingan-edit-modal-open', false);
    $this->actingAs($this->dosen)->put(route('dosen.bimbingan.update', $bimbingan), [
        'meeting_date' => '2026-05-06',
        'lecturer_notes' => 'Updated notes',
    ])->assertRedirect();
    $this->assertDatabaseHas('bimbingans', ['id' => $bimbingan->id, 'lecturer_notes' => 'Updated notes']);

    $this->actingAs($this->dosen)->delete(route('dosen.bimbingan.destroy', $bimbingan))->assertRedirect();
    $this->assertDatabaseMissing('bimbingans', ['id' => $bimbingan->id]);
});

it('blocks unassigned dosen', function () {
    $this->actingAs($this->otherDosen)->get(route('dosen.skripsi.show', $this->skripsi))->assertForbidden();
    $this->actingAs($this->otherDosen)->post(route('dosen.bimbingan.store', $this->skripsi), [
        'meeting_date' => '2026-05-05', 'phase' => 'proposal',
    ])->assertForbidden();
});

it('blocks an assigned dosen from changing another dosen bimbingan', function () {
    ReviewerAssignment::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'lecturer_id' => $this->otherDosen->id,
        'role_type' => 'pembimbing_2',
    ]);
    $bimbingan = Bimbingan::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'reviewer_id' => $this->dosen->id,
        'phase' => 'bimbingan_skripsi',
        'meeting_date' => '2026-05-05',
        'lecturer_notes' => 'Original notes',
    ]);

    $this->actingAs($this->otherDosen)->put(route('dosen.bimbingan.update', $bimbingan), [
        'meeting_date' => '2026-05-06',
        'lecturer_notes' => 'Tampered notes',
    ])->assertForbidden();

    $this->actingAs($this->otherDosen)
        ->delete(route('dosen.bimbingan.destroy', $bimbingan))
        ->assertForbidden();
});
