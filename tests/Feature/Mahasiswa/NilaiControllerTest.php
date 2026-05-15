<?php

use App\Models\Grade;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

it('can view own nilai', function () {
    \App\Models\FormatPenilaian::query()->create(['id' => 1, 'nama' => 'Format 1', 'template_type' => 'sidang_skripsi', 'is_published' => true]);
    Grade::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'format_penilaian_id' => 1,
        'reviewer_id' => $this->dosen->id,
        'role_type' => 'penguji_1',
        'grade_event' => 'sidang_skripsi',
        'status' => 'published',
        'score' => 95,
    ]);

    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.skripsi.nilai.index', $this->skripsi))
        ->assertOk()
        ->assertSee('95');
});

it('blocks other mahasiswa from nilai', function () {
    $this->actingAs($this->otherMahasiswa)
        ->get(route('mahasiswa.skripsi.nilai.index', $this->skripsi))
        ->assertForbidden();
});
