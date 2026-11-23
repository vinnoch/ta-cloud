<?php

use App\Models\Periode;
use App\Models\TahunAkademik;
use App\Models\TugasAkhir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows a unified tugas akhir distribution with type column', function () {
    $kaprodi = User::factory()->kaprodi()->create();
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

    foreach (['skripsi', 'non_skripsi'] as $type) {
        TugasAkhir::query()->create([
            'student_id' => User::factory()->mahasiswa()->create()->id,
            'periode_id' => $periode->id,
            'title' => $type,
            'type' => $type,
            'current_phase' => 'bimbingan_skripsi',
        ]);
    }

    $response = $this->actingAs($kaprodi)->get(route('kaprodi.dashboard'));

    $response->assertOk()
        ->assertSee('Distribusi Tugas Akhir Aktif')
        ->assertSee('Fase')
        ->assertSee('Tipe')
        ->assertSee('Jumlah')
        ->assertSee('Skripsi')
        ->assertSee('Non-Skripsi');

    expect(collect($response->viewData('chartData'))->where('label', 'Bimbingan')->pluck('type_label')->all())
        ->toBe(['Skripsi', 'Non-Skripsi']);
});
