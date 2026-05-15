<?php

use App\Models\FormatPenilaian;
use App\Models\Grade;
use App\Models\ReviewerAssignment;
use App\Models\Skripsi;
use App\Models\TahunAkademik;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');

    $this->mahasiswa = User::factory()->mahasiswa()->create();
    $this->other = User::factory()->mahasiswa()->create();
    $this->penguji1 = User::factory()->dosen()->create();
    $this->penguji2 = User::factory()->dosen()->create();
    $this->pembimbing1 = User::factory()->dosen()->create();
    $this->kaprodi = User::factory()->kaprodi()->create();

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

    $this->skripsi = Skripsi::query()->create([
        'student_id' => $this->mahasiswa->id,
        'periode_id' => $periode->id,
        'title' => 'TA Final Submission',
        'type' => 'skripsi',
        'current_phase' => 'sidang_proposal',
    ]);

    ReviewerAssignment::query()->create(['skripsi_id' => $this->skripsi->id, 'lecturer_id' => $this->penguji1->id, 'role_type' => 'penguji_1']);
    ReviewerAssignment::query()->create(['skripsi_id' => $this->skripsi->id, 'lecturer_id' => $this->penguji2->id, 'role_type' => 'penguji_2']);
    ReviewerAssignment::query()->create(['skripsi_id' => $this->skripsi->id, 'lecturer_id' => $this->pembimbing1->id, 'role_type' => 'pembimbing_1']);

    $formatProposal = FormatPenilaian::query()->create(['nama' => 'Proposal', 'template_type' => 'sidang_proposal', 'is_published' => true]);
    $formatSkripsi = FormatPenilaian::query()->create(['nama' => 'Skripsi', 'template_type' => 'sidang_skripsi', 'is_published' => true]);

    Grade::query()->create(['skripsi_id' => $this->skripsi->id, 'format_penilaian_id' => $formatProposal->id, 'reviewer_id' => $this->penguji1->id, 'role_type' => 'penguji_1', 'grade_event' => 'sidang_proposal', 'status' => 'published', 'score' => 80]);
    Grade::query()->create(['skripsi_id' => $this->skripsi->id, 'format_penilaian_id' => $formatProposal->id, 'reviewer_id' => $this->penguji2->id, 'role_type' => 'penguji_2', 'grade_event' => 'sidang_proposal', 'status' => 'published', 'score' => 84]);

    $this->formatSkripsi = $formatSkripsi;
});

it('shows final proposal submission form when final grades complete', function () {
    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.final.index', [$this->skripsi, 'sidang_proposal']))
        ->assertOk()
        ->assertSee('Final Submission Proposal')
        ->assertSee('Kirim Final Submission');
});

it('stores final proposal submission and advances phase', function () {
    $file = UploadedFile::fake()->create('proposal-final.pdf', 1000, 'application/pdf');

    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.final.submit', [$this->skripsi, 'sidang_proposal']), [
            'file' => $file,
            'notes' => 'Revisi proposal selesai.',
        ])
        ->assertRedirect(route('mahasiswa.skripsi.show', $this->skripsi));

    $this->skripsi->refresh();
    expect($this->skripsi->current_phase)->toBe('bimbingan_skripsi');
    $this->assertDatabaseHas('document_versions', [
        'skripsi_id' => $this->skripsi->id,
        'phase' => 'proposal_final',
        'version_number' => 1,
    ]);
});

it('blocks final submission form for other mahasiswa', function () {
    $this->actingAs($this->other)
        ->get(route('mahasiswa.final.index', [$this->skripsi, 'sidang_proposal']))
        ->assertForbidden();
});

it('stores final skripsi submission when final grades complete', function () {
    $this->skripsi->update(['current_phase' => 'sidang_skripsi']);

    Grade::query()->create(['skripsi_id' => $this->skripsi->id, 'format_penilaian_id' => $this->formatSkripsi->id, 'reviewer_id' => $this->penguji1->id, 'role_type' => 'penguji_1', 'grade_event' => 'sidang_skripsi', 'status' => 'published', 'score' => 86]);
    Grade::query()->create(['skripsi_id' => $this->skripsi->id, 'format_penilaian_id' => $this->formatSkripsi->id, 'reviewer_id' => $this->penguji2->id, 'role_type' => 'penguji_2', 'grade_event' => 'sidang_skripsi', 'status' => 'published', 'score' => 88]);
    Grade::query()->create(['skripsi_id' => $this->skripsi->id, 'format_penilaian_id' => $this->formatSkripsi->id, 'reviewer_id' => $this->pembimbing1->id, 'role_type' => 'pembimbing_1', 'grade_event' => 'sidang_skripsi', 'status' => 'published', 'score' => 90]);

    $file = UploadedFile::fake()->create('skripsi-final.docx', 1000, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.final.submit', [$this->skripsi, 'sidang_skripsi']), [
            'file' => $file,
            'journal_article_url' => 'https://example.com/journal',
        ])
        ->assertRedirect(route('mahasiswa.skripsi.show', $this->skripsi));

    $this->skripsi->refresh();
    expect($this->skripsi->current_phase)->toBe('review_dokumen_final');
    expect($this->skripsi->journal_article_url)->toBe('https://example.com/journal');
});
