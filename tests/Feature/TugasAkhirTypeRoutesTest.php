<?php

use App\Models\Periode;
use App\Models\TahunAkademik;
use App\Models\TugasAkhir;
use App\Models\User;
use App\Services\TugasAkhirRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
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

    $this->mahasiswa = User::factory()->mahasiswa()->create();
    $this->skripsi = TugasAkhir::query()->create([
        'student_id' => $this->mahasiswa->id,
        'periode_id' => $this->periode->id,
        'title' => 'Skripsi',
        'type' => 'skripsi',
        'current_phase' => 'proposal',
    ]);
    $this->nonSkripsi = TugasAkhir::query()->create([
        'student_id' => User::factory()->mahasiswa()->create()->id,
        'periode_id' => $this->periode->id,
        'title' => 'Non-Skripsi',
        'type' => 'non_skripsi',
        'current_phase' => 'proposal',
    ]);
});

it('generates canonical role routes from tugas akhir type', function () {
    $routes = app(TugasAkhirRouteService::class);

    expect($routes->route('kaprodi', $this->skripsi, 'show', $this->skripsi, false))
        ->toBe('/kaprodi/skripsi/'.$this->skripsi->id)
        ->and($routes->route('kaprodi', $this->nonSkripsi, 'show', $this->nonSkripsi, false))
        ->toBe('/kaprodi/non-skripsi/'.$this->nonSkripsi->id)
        ->and($routes->route('dosen', $this->nonSkripsi, 'show', $this->nonSkripsi, false))
        ->toBe('/dosen/non-skripsi/'.$this->nonSkripsi->id)
        ->and($routes->route('mahasiswa', $this->nonSkripsi, 'show', $this->nonSkripsi, false))
        ->toBe('/mahasiswa/non-skripsi/'.$this->nonSkripsi->id)
        ->and($routes->route('superadmin', $this->nonSkripsi, 'show', $this->nonSkripsi, false))
        ->toBe('/superadmin/non-skripsi/'.$this->nonSkripsi->id);
});

it('scopes the unified model by type', function () {
    expect(TugasAkhir::query()->skripsi()->pluck('id')->all())->toBe([$this->skripsi->id])
        ->and(TugasAkhir::query()->nonSkripsi()->pluck('id')->all())->toBe([$this->nonSkripsi->id]);
});

it('returns 404 when an id is opened through the wrong type slug', function (string $role, string $routeName) {
    $user = $role === 'mahasiswa'
        ? $this->mahasiswa
        : User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route($routeName, $this->skripsi))
        ->assertNotFound();
})->with([
    ['superadmin', 'superadmin.non-skripsi.show'],
    ['kaprodi', 'kaprodi.non-skripsi.show'],
    ['dosen', 'dosen.non-skripsi.show'],
    ['mahasiswa', 'mahasiswa.non-skripsi.show'],
]);
