<?php

use App\Models\DocumentVersion;
use App\Models\Periode;
use App\Models\Skripsi;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('escapes stored user content in the public library', function () {
    $payload = '<img src=x onerror=alert(1)>';
    $student = User::factory()->mahasiswa()->create(['name' => $payload]);
    $tahun = TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    $periode = Periode::query()->create([
        'tahun_akademik_id' => $tahun->id,
        'kode_periode' => '20251',
        'semester' => 1,
        'sk_nomor' => 'SK-1',
        'tgl_mulai' => '2025-08-01',
        'tgl_selesai' => '2026-01-31',
        'is_aktif' => true,
        'status' => 'active',
    ]);
    $skripsi = Skripsi::query()->create([
        'student_id' => $student->id,
        'periode_id' => $periode->id,
        'title' => $payload,
        'type' => 'skripsi',
        'current_phase' => 'skripsi_selesai',
    ]);
    DocumentVersion::query()->create([
        'skripsi_id' => $skripsi->id,
        'phase' => 'skripsi_final',
        'version_number' => 1,
        'file_path' => 'documents/final.pdf',
        'mime_type' => 'application/pdf',
        'size' => 1,
        'uploaded_by' => $student->id,
    ]);

    $this->get(route('library.index'))
        ->assertOk()
        ->assertDontSee($payload, false)
        ->assertSee($payload);
});
