<?php

use App\Models\Periode;
use App\Models\TahunAkademik;
use App\Models\TugasAkhir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->create(['role' => 'superadmin']);
    $tahun = TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    $this->periode = Periode::query()->create([
        'tahun_akademik_id' => $tahun->id,
        'kode_periode' => '20251',
        'semester' => 1,
        'sk_nomor' => 'SK-1',
        'tgl_mulai' => '2025-08-01',
        'tgl_selesai' => '2026-01-31',
        'is_aktif' => true,
        'status' => 'active',
    ]);
});

it('uses the Kaprodi filter layout and filters AJAX results by type and phase', function () {
    foreach ([
        ['title' => 'Skripsi Proposal', 'type' => 'skripsi', 'current_phase' => 'proposal'],
        ['title' => 'Riset Bimbingan', 'type' => 'skripsi', 'current_phase' => 'bimbingan_skripsi'],
        ['title' => 'Proyek Bimbingan', 'type' => 'non_skripsi', 'current_phase' => 'bimbingan_skripsi'],
    ] as $record) {
        TugasAkhir::query()->create($record + [
            'student_id' => User::factory()->mahasiswa()->create()->id,
            'periode_id' => $this->periode->id,
        ]);
    }

    $this->actingAs($this->superadmin)
        ->get(route('superadmin.skripsi.index'))
        ->assertOk()
        ->assertSee('id="filter-form"', false)
        ->assertSee('id="search-input"', false)
        ->assertSee('id="status-select"', false)
        ->assertDontSee('>Cari<', false);

    $response = $this->getJson(route('superadmin.non-skripsi.index', [
        'status' => 'bimbingan_skripsi',
    ]), ['X-Requested-With' => 'XMLHttpRequest']);

    $response->assertOk()
        ->assertJsonPath('count_text', '1 non-skripsi ditemukan.');

    expect($response->json('table_html'))
        ->toContain('Proyek Bimbingan')
        ->not->toContain('Riset Bimbingan');
});
