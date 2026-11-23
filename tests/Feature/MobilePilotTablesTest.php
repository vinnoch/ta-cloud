<?php

use App\Models\Periode;
use App\Models\ReviewerAssignment;
use App\Models\TugasAkhir;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function mobilePilotPeriod(): Periode
{
    $tahun = TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);

    return Periode::query()->create([
        'tahun_akademik_id' => $tahun->id,
        'kode_periode' => '20251',
        'semester' => 1,
        'sk_nomor' => 'SK-MOBILE',
        'tgl_mulai' => '2025-08-01',
        'tgl_selesai' => '2026-01-31',
        'is_aktif' => true,
        'status' => 'active',
    ]);
}

function mobilePilotSkripsi(User $student): TugasAkhir
{
    return TugasAkhir::query()->create([
        'student_id' => $student->id,
        'periode_id' => mobilePilotPeriod()->id,
        'title' => 'Mobile Card Thesis',
        'type' => 'skripsi',
        'current_phase' => 'bimbingan_skripsi',
    ]);
}

it('renders the Kaprodi skripsi pilot as mobile record cards', function () {
    $kaprodi = User::factory()->kaprodi()->create();
    $skripsi = mobilePilotSkripsi(User::factory()->mahasiswa()->create());

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.skripsi.index'))
        ->assertOk()
        ->assertSee($skripsi->title)
        ->assertSee('pilot-record-card--kaprodi-skripsi', false)
        ->assertSee('pilot-record-card__primary-action', false)
        ->assertSee('Edit Fase');
});

it('renders the Kaprodi mahasiswa pilot with email disclosure and overflow actions', function () {
    $kaprodi = User::factory()->kaprodi()->create();
    $mahasiswa = User::factory()->mahasiswa()->create();

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.mahasiswa.index'))
        ->assertOk()
        ->assertSee($mahasiswa->email)
        ->assertSee('pilot-record-card--kaprodi-mahasiswa', false)
        ->assertSee('pilot-record-card__details', false)
        ->assertSee('pilot-record-card__overflow', false);
});

it('renders the Dosen skripsi pilot as mobile record cards', function () {
    $dosen = User::factory()->dosen()->create();
    $skripsi = mobilePilotSkripsi(User::factory()->mahasiswa()->create());
    ReviewerAssignment::query()->create([
        'tugas_akhir_id' => $skripsi->id,
        'lecturer_id' => $dosen->id,
        'role_type' => 'pembimbing_1',
    ]);

    $this->actingAs($dosen)
        ->get(route('dosen.skripsi.index'))
        ->assertOk()
        ->assertSee($skripsi->title)
        ->assertSee('pilot-record-card--dosen-skripsi', false)
        ->assertSee('pilot-record-card__primary-action', false);
});
