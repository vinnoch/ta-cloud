<?php

use App\Models\FormatPenilaian;
use App\Models\Periode;
use App\Models\ReviewerAssignment;
use App\Models\SidangRequest;
use App\Models\TahunAkademik;
use App\Models\TugasAkhir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;

uses(RefreshDatabase::class);

function phaseFourTugasAkhir(User $student, string $phase = 'proposal'): TugasAkhir
{
    $tahun = TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    $periode = Periode::query()->create([
        'tahun_akademik_id' => $tahun->id,
        'kode_periode' => '20251',
        'semester' => 1,
        'sk_nomor' => 'SK-PHASE-4',
        'tgl_mulai' => '2025-08-01',
        'tgl_selesai' => '2026-01-31',
        'is_aktif' => true,
        'status' => 'active',
    ]);

    return TugasAkhir::query()->create([
        'student_id' => $student->id,
        'periode_id' => $periode->id,
        'title' => 'Judul Panjang untuk Kartu Data Dosen',
        'type' => 'skripsi',
        'current_phase' => $phase,
    ]);
}

it('renders the Dosen dashboard queue as mobile data cards without an empty table header', function () {
    $dosen = User::factory()->dosen()->create();

    $this->actingAs($dosen)
        ->get(route('dosen.dashboard'))
        ->assertOk()
        ->assertDontSee('acss-table-cols-dosen-dash-grading', false);

    $skripsi = phaseFourTugasAkhir(User::factory()->mahasiswa()->create(), 'sidang_skripsi');
    ReviewerAssignment::query()->create([
        'tugas_akhir_id' => $skripsi->id,
        'lecturer_id' => $dosen->id,
        'role_type' => 'penguji_1',
    ]);

    $this->actingAs($dosen)
        ->get(route('dosen.dashboard'))
        ->assertOk()
        ->assertSee('pilot-record-card--dosen-dashboard', false)
        ->assertSee('data-mobile-label="Peran"', false)
        ->assertSee('pilot-record-card__primary-action', false);
});

it('renders assigned proposals as mobile data cards', function () {
    $dosen = User::factory()->dosen()->create();
    $skripsi = phaseFourTugasAkhir(User::factory()->mahasiswa()->create());
    ReviewerAssignment::query()->create([
        'tugas_akhir_id' => $skripsi->id,
        'lecturer_id' => $dosen->id,
        'role_type' => 'pembimbing_1',
    ]);

    $this->actingAs($dosen)
        ->get(route('dosen.proposal.index'))
        ->assertOk()
        ->assertSee('pilot-record-card--dosen-proposal', false)
        ->assertSee('data-mobile-label="Periode"', false)
        ->assertSee('Buka Proposal');
});

it('renders sidang requests as mobile data cards', function () {
    $dosen = User::factory()->dosen()->create();
    $skripsi = phaseFourTugasAkhir(User::factory()->mahasiswa()->create(), 'bimbingan_skripsi');
    SidangRequest::query()->create([
        'tugas_akhir_id' => $skripsi->id,
        'lecturer_id' => $dosen->id,
        'role_type' => 'pembimbing_1',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($dosen)
        ->get(route('dosen.sidang-request.index'))
        ->assertOk()
        ->assertSee('pilot-record-card--dosen-sidang-request', false)
        ->assertSee('data-mobile-label="Status"', false)
        ->assertSee('Lihat Detail');
});

it('renders grading items as mobile data cards with the existing modal action', function () {
    $format = new FormatPenilaian;
    $format->setRelation('items', collect());
    $item = [
        'date' => now(),
        'student' => 'Mahasiswa Uji',
        'nim' => '2025001',
        'title' => 'Judul Uji',
        'skripsi_href' => '#skripsi',
        'type_label' => 'Skripsi',
        'fase' => 'Sidang Skripsi',
        'role' => 'Penguji 1',
        'modal_id' => 'grade-test',
        'is_locked' => false,
        'has_grade' => false,
        'format' => $format,
        'itemScores' => [],
        'store_url' => '#store',
        'unlock_url' => '#unlock',
        'unlock_requested' => false,
        'grade' => null,
    ];
    $queue = new LengthAwarePaginator([$item], 1, 10);

    $html = view('dosen.penilaian.partials.table', [
        'gradingQueue' => $queue,
        'sort' => 'tanggal',
        'direction' => 'desc',
    ])->render();

    expect($html)
        ->toContain('pilot-record-card--dosen-grading')
        ->toContain('data-mobile-label="Nilai Sidang"')
        ->toContain('data-grade-modal-open="grade-test"');
});
