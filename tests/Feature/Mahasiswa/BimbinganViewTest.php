<?php

use App\Models\Bimbingan;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mahasiswa = User::factory()->mahasiswa()->create();
    $this->otherMahasiswa = User::factory()->mahasiswa()->create();
    $this->dosen = User::factory()->dosen()->create();
    $tahun = \App\Models\TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    $this->periode = \App\Models\Periode::query()->create([
        'id' => 1, 'tahun_akademik_id' => $tahun->id, 'kode_periode' => '20251',
        'semester' => 1, 'sk_nomor' => 'SK-1', 'tgl_mulai' => '2025-08-01',
        'tgl_selesai' => '2026-01-31', 'is_aktif' => true, 'status' => 'active',
    ]);
    $this->skripsi = Skripsi::query()->create(['student_id' => $this->mahasiswa->id, 'periode_id' => 1, 'title' => 'TA', 'type' => 'skripsi', 'current_phase' => 'proposal']);
});

it('can view own bimbingan list', function () {
    Bimbingan::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'reviewer_id' => $this->dosen->id,
        'phase' => 'proposal',
        'meeting_date' => now(),
        'student_notes' => 'Some notes'
    ]);

    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.skripsi.bimbingan.index', $this->skripsi))
        ->assertOk()
        ->assertSee('Some notes');
});

it('blocks other mahasiswa from bimbingan', function () {
    $this->actingAs($this->otherMahasiswa)
        ->get(route('mahasiswa.skripsi.bimbingan.index', $this->skripsi))
        ->assertForbidden();
});


it('can update own student notes and upload revision file', function () {
    Storage::fake();

    $bimbingan = Bimbingan::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'reviewer_id' => $this->dosen->id,
        'phase' => 'proposal',
        'meeting_date' => now(),
        'student_notes' => 'Old notes'
    ]);

    $file = UploadedFile::fake()->create('revisi.docx', 500, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $this->actingAs($this->mahasiswa)
        ->put(route('mahasiswa.skripsi.bimbingan.update', [$this->skripsi, $bimbingan]), [
            'student_notes' => 'Updated notes',
            'revision_file' => $file,
        ])
        ->assertRedirect(route('mahasiswa.skripsi.bimbingan.index', $this->skripsi) . '#bimbingan-' . $bimbingan->id);

    $bimbingan->refresh();

    expect($bimbingan->student_notes)->toBe('Updated notes');
    expect($bimbingan->reviewed_version_id)->not->toBeNull();
});

it('rejects oversize or invalid revision file type', function () {
    Storage::fake();

    $bimbingan = Bimbingan::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'reviewer_id' => $this->dosen->id,
        'phase' => 'proposal',
        'meeting_date' => now(),
    ]);

    $file = UploadedFile::fake()->create('bad.png', 500, 'image/png');

    $this->actingAs($this->mahasiswa)
        ->put(route('mahasiswa.skripsi.bimbingan.update', [$this->skripsi, $bimbingan]), [
            'revision_file' => $file,
        ])
        ->assertSessionHasErrors('revision_file');
});
