<?php

use App\Models\ReviewerAssignment;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dosen = User::factory()->dosen()->create();
    $this->otherDosen = User::factory()->dosen()->create();
    $this->mahasiswa = User::factory()->mahasiswa()->create();
    $tahun = \App\Models\TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    \App\Models\Periode::query()->create([
        'id' => 1,'tahun_akademik_id' => $tahun->id,'kode_periode' => '20251','semester' => 1,
        'sk_nomor' => 'SK-1','tgl_mulai' => '2025-08-01','tgl_selesai' => '2026-01-31','is_aktif' => true,'status' => 'active',
    ]);
    $this->skripsi = Skripsi::query()->create(['student_id' => $this->mahasiswa->id,'periode_id' => 1,'title' => 'TA','type' => 'skripsi','current_phase' => 'proposal']);
    ReviewerAssignment::query()->create(['skripsi_id' => $this->skripsi->id, 'lecturer_id' => $this->dosen->id, 'role_type' => 'pembimbing_1']);
});

it('can view assigned skripsi', function () {
    $this->actingAs($this->dosen)->get(route('dosen.skripsi.index'))->assertOk();
    $this->actingAs($this->dosen)->get(route('dosen.skripsi.show', $this->skripsi))->assertOk();
});

it('cannot view unassigned skripsi', function () {
    $this->actingAs($this->otherDosen)->get(route('dosen.skripsi.show', $this->skripsi))->assertForbidden();
});

it('includes final document review in the summary cards', function () {
    $this->skripsi->update(['current_phase' => 'review_dokumen_final']);

    $this->actingAs($this->dosen)
        ->get(route('dosen.skripsi.index'))
        ->assertOk()
        ->assertSeeInOrder(['Review Dokumen Final', '1']);
});

it('merges proposal phases in one summary card', function () {
    $otherStudent = User::factory()->mahasiswa()->create();
    $sidangProposal = Skripsi::query()->create([
        'student_id' => $otherStudent->id,
        'periode_id' => 1,
        'title' => 'TA Sidang Proposal',
        'type' => 'skripsi',
        'current_phase' => 'sidang_proposal',
    ]);
    ReviewerAssignment::query()->create([
        'skripsi_id' => $sidangProposal->id,
        'lecturer_id' => $this->dosen->id,
        'role_type' => 'penguji_1',
    ]);

    $response = $this->actingAs($this->dosen)->get(route('dosen.skripsi.index'));

    $proposalStat = collect($response->viewData('chartData'))->firstWhere('label', 'Proposal');

    expect($proposalStat['value'])->toBe(2);
    expect(collect($response->viewData('chartData'))->pluck('label'))->not->toContain('Sidang Proposal');
});

it('lists only assigned proposals on the proposal page', function () {
    $unassignedStudent = User::factory()->mahasiswa()->create();
    Skripsi::query()->create([
        'student_id' => $unassignedStudent->id,
        'periode_id' => 1,
        'title' => 'Proposal Tidak Ditugaskan',
        'type' => 'skripsi',
        'current_phase' => 'proposal',
    ]);

    $this->actingAs($this->dosen)
        ->get(route('dosen.proposal.index'))
        ->assertOk()
        ->assertSee('TA')
        ->assertDontSee('Proposal Tidak Ditugaskan');
});
