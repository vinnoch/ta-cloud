<?php

use App\Models\DocumentVersion;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mahasiswa = User::factory()->mahasiswa()->create();
    $this->otherMahasiswa = User::factory()->mahasiswa()->create();
    $tahun = \App\Models\TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    \App\Models\Periode::query()->create([
        'id' => 1, 'tahun_akademik_id' => $tahun->id, 'kode_periode' => '20251',
        'semester' => 1, 'sk_nomor' => 'SK-1', 'tgl_mulai' => '2025-08-01',
        'tgl_selesai' => '2026-01-31', 'is_aktif' => true, 'status' => 'active',
    ]);
    $this->skripsi = Skripsi::query()->create(['student_id' => $this->mahasiswa->id, 'periode_id' => 1, 'title' => 'TA', 'type' => 'skripsi', 'current_phase' => 'proposal']);
    Storage::fake('local');
});

it('uploads valid pdf', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 1000, 'application/pdf');

    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.skripsi.documents.store', $this->skripsi), [
            'file' => $file,
            'phase' => 'proposal',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('document_versions', [
        'skripsi_id' => $this->skripsi->id,
        'phase' => 'proposal',
        'version_number' => 1,
    ]);
});

it('rejects wrong mime', function () {
    $file = UploadedFile::fake()->create('doc.txt', 1000, 'text/plain');

    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.skripsi.documents.store', $this->skripsi), [
            'file' => $file,
            'phase' => 'proposal',
        ])
        ->assertSessionHasErrors(['file']);
});

it('blocks other mahasiswa upload', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 1000, 'application/pdf');
    $this->actingAs($this->otherMahasiswa)
        ->post(route('mahasiswa.skripsi.documents.store', $this->skripsi), [
            'file' => $file, 'phase' => 'proposal'
        ])
        ->assertForbidden();
});
