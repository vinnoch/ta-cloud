<?php

use App\Models\DocumentVersion;
use App\Models\FormatPenilaian;
use App\Models\Grade;
use App\Models\Periode;
use App\Models\SidangRequest;
use App\Models\TahunAkademik;
use App\Models\TugasAkhir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->kaprodi = User::factory()->kaprodi()->create();
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

    $make = function (string $type, string $title): TugasAkhir {
        return TugasAkhir::query()->create([
            'student_id' => User::factory()->mahasiswa()->create()->id,
            'periode_id' => $this->periode->id,
            'title' => $title,
            'type' => $type,
            'current_phase' => 'sidang_skripsi',
        ]);
    };

    $this->skripsi = $make('skripsi', 'Penelitian Prediksi');
    $this->nonSkripsi = $make('non_skripsi', 'Proyek Dasbor');
});

it('filters proposal submissions by tugas akhir type', function () {
    foreach ([$this->skripsi, $this->nonSkripsi] as $item) {
        $item->update(['current_phase' => 'proposal']);
        DocumentVersion::query()->create([
            'tugas_akhir_id' => $item->id,
            'phase' => 'proposal',
            'version_number' => 1,
            'file_path' => "proposal/{$item->id}.pdf",
            'mime_type' => 'application/pdf',
            'size' => 100,
            'uploaded_by' => $item->student_id,
        ]);
    }

    $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.proposal-submissions.index', ['tugas_akhir_type' => 'non_skripsi']))
        ->assertOk()
        ->assertSee('Judul Tugas Akhir')
        ->assertSee('Non-Skripsi')
        ->assertSee('Proyek Dasbor')
        ->assertDontSee('Penelitian Prediksi');
});

it('separates skripsi and non skripsi final hearing requests', function () {
    foreach ([$this->skripsi, $this->nonSkripsi] as $item) {
        SidangRequest::query()->create([
            'tugas_akhir_id' => $item->id,
            'lecturer_id' => User::factory()->dosen()->create()->id,
            'role_type' => 'pembimbing_1',
            'status' => 'submitted',
        ]);
    }

    $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.sidang-requests.index', ['sidang_type' => 'non_skripsi']))
        ->assertOk()
        ->assertSee('Judul Tugas Akhir')
        ->assertSee('Sidang Non-Skripsi')
        ->assertSee('Proyek Dasbor')
        ->assertDontSee('Penelitian Prediksi');
});

it('filters grades with the same assessment types as format penilaian', function () {
    $format = FormatPenilaian::query()->create([
        'nama' => 'Format Sidang',
        'template_type' => 'sidang_skripsi',
    ]);

    foreach ([$this->skripsi, $this->nonSkripsi] as $item) {
        Grade::query()->create([
            'tugas_akhir_id' => $item->id,
            'format_penilaian_id' => $format->id,
            'reviewer_id' => User::factory()->dosen()->create()->id,
            'role_type' => 'penguji_1',
            'grade_event' => 'sidang_skripsi',
            'status' => 'published',
            'score' => 80,
        ]);
    }

    $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.nilai.index', ['jenis' => 'sidang_non_skripsi']))
        ->assertOk()
        ->assertSee('Proyek Dasbor')
        ->assertDontSee('Penelitian Prediksi');
});

it('renders shared phase choices across kaprodi filters and phase controls', function () {
    $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.non-skripsi.index'))
        ->assertOk()
        ->assertSee('Bimbingan')
        ->assertSee('Sidang')
        ->assertSee('Tugas Akhir Selesai')
        ->assertDontSee('Bimbingan Non-Skripsi')
        ->assertDontSee('Sidang Non-Skripsi')
        ->assertDontSee('Non-Skripsi Selesai')
        ->assertSee('Edit Fase Tugas Akhir');

    $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.proposal-submissions.index'))
        ->assertOk()
        ->assertSee('name="tugas_akhir_type"', false)
        ->assertSee('Non-Skripsi');

    $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.sidang-requests.index'))
        ->assertOk()
        ->assertSee('value="non_skripsi"', false);

    $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.formats.index'))
        ->assertOk()
        ->assertSee('value="sidang_non_skripsi"', false);

    $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.nilai.index'))
        ->assertOk()
        ->assertSee('value="sidang_non_skripsi"', false);
});

it('shows generic tugas akhir copy and the actual type on mahasiswa detail', function () {
    $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.mahasiswa.show', $this->nonSkripsi->student))
        ->assertOk()
        ->assertSee('Tugas Akhir Aktif')
        ->assertSee('Judul Tugas Akhir')
        ->assertSee('Proyek Dasbor')
        ->assertSee('Non-Skripsi')
        ->assertDontSee('>Skripsi Aktif<', false)
        ->assertDontSee('>Judul Skripsi<', false);
});

it('filters every non skripsi phase card through the ajax endpoint', function (string $status, string $phase) {
    $this->nonSkripsi->update(['current_phase' => $phase]);
    $this->skripsi->update(['current_phase' => $phase]);

    TugasAkhir::query()->create([
        'student_id' => User::factory()->mahasiswa()->create()->id,
        'periode_id' => $this->periode->id,
        'title' => 'Proyek Fase Lain',
        'type' => 'non_skripsi',
        'current_phase' => $phase === 'proposal' ? 'bimbingan_skripsi' : 'proposal',
    ]);

    $response = $this->actingAs($this->kaprodi)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('kaprodi.non-skripsi.index', ['status' => $status]));

    $response
        ->assertOk()
        ->assertJsonPath('count_text', '1 non-skripsi ditemukan.')
        ->assertJsonFragment(['table_html' => $response->json('table_html')]);

    expect($response->json('table_html'))
        ->toContain('Proyek Dasbor')
        ->not->toContain('Penelitian Prediksi')
        ->not->toContain('Proyek Fase Lain');
})->with([
    'proposal' => ['Proposal', 'proposal'],
    'bimbingan non skripsi' => ['Bimbingan Non-Skripsi', 'bimbingan_skripsi'],
    'sidang non skripsi' => ['Sidang Non-Skripsi', 'sidang_skripsi'],
    'non skripsi selesai' => ['Non-Skripsi Selesai', 'skripsi_selesai'],
]);

it('merges monitoring types and supports the shared type and phase filters', function () {
    $page = $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.tugas-akhir.index'));

    $page
        ->assertOk()
        ->assertSee('Monitoring Tugas Akhir')
        ->assertSee('name="type"', false)
        ->assertSee('value="skripsi"', false)
        ->assertSee('value="non_skripsi"', false)
        ->assertSee('Tugas Akhir')
        ->assertDontSee('>Skripsi</a>', false)
        ->assertDontSee('>Non-Skripsi</a>', false);

    $response = $this->actingAs($this->kaprodi)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('kaprodi.tugas-akhir.index'));

    $response->assertOk();
    expect($response->json('table_html'))
        ->toContain('Penelitian Prediksi')
        ->toContain('Proyek Dasbor');

    $filtered = $this->actingAs($this->kaprodi)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('kaprodi.tugas-akhir.index', [
            'type' => 'non_skripsi',
            'status' => 'Sidang',
        ]));

    $filtered
        ->assertOk()
        ->assertJsonPath('count_text', '1 tugas akhir ditemukan.');
    expect($filtered->json('table_html'))
        ->toContain('Proyek Dasbor')
        ->not->toContain('Penelitian Prediksi');

    foreach (['Proposal', 'Sidang Proposal', 'Bimbingan', 'Sidang', 'Revisi Sidang', 'Dokumen Final', 'Tugas Akhir Selesai'] as $phase) {
        expect($filtered->json('stats_html'))->toContain($phase);
    }
});

it('rejects an unknown monitoring type', function () {
    $this->actingAs($this->kaprodi)
        ->get(route('kaprodi.tugas-akhir.index', ['type' => 'unknown']))
        ->assertSessionHasErrors('type');
});

it('filters every shared phase without leaking another type or phase', function (string $status, string $phase) {
    $this->nonSkripsi->update(['current_phase' => $phase]);
    $this->skripsi->update(['current_phase' => $phase]);

    TugasAkhir::query()->create([
        'student_id' => User::factory()->mahasiswa()->create()->id,
        'periode_id' => $this->periode->id,
        'title' => 'Proyek Fase Berbeda',
        'type' => 'non_skripsi',
        'current_phase' => $phase === 'proposal' ? 'sidang_proposal' : 'proposal',
    ]);

    $response = $this->actingAs($this->kaprodi)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('kaprodi.tugas-akhir.index', [
            'type' => 'non_skripsi',
            'status' => $status,
        ]));

    $response
        ->assertOk()
        ->assertJsonPath('count_text', '1 tugas akhir ditemukan.');

    expect($response->json('table_html'))
        ->toContain('Proyek Dasbor')
        ->not->toContain('Penelitian Prediksi')
        ->not->toContain('Proyek Fase Berbeda');
})->with([
    'proposal' => ['Proposal', 'proposal'],
    'sidang proposal' => ['Sidang Proposal', 'sidang_proposal'],
    'bimbingan' => ['Bimbingan', 'bimbingan_skripsi'],
    'sidang' => ['Sidang', 'sidang_skripsi'],
    'revisi sidang' => ['Revisi Sidang', 'revisi_sidang_skripsi'],
    'dokumen final' => ['Dokumen Final', 'review_dokumen_final'],
    'tugas akhir selesai' => ['Tugas Akhir Selesai', 'skripsi_selesai'],
]);
