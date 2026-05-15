<?php

use App\Models\DocumentVersion;
use App\Models\FinalDocumentApproval;
use App\Models\Periode;
use App\Models\ReviewerAssignment;
use App\Models\Skripsi;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dosen = User::factory()->dosen()->create();
    $this->otherDosen = User::factory()->dosen()->create();
    $this->mahasiswa = User::factory()->mahasiswa()->create();
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
        'title' => 'TA Final Review',
        'type' => 'skripsi',
        'current_phase' => 'review_dokumen_final',
    ]);

    ReviewerAssignment::query()->create(['skripsi_id' => $this->skripsi->id, 'lecturer_id' => $this->dosen->id, 'role_type' => 'pembimbing_1']);
    ReviewerAssignment::query()->create(['skripsi_id' => $this->skripsi->id, 'lecturer_id' => $this->otherDosen->id, 'role_type' => 'penguji_1']);

    $document = DocumentVersion::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'phase' => 'skripsi_final',
        'version_number' => 1,
        'file_path' => 'documents/mahasiswa/' . ($this->mahasiswa->nim ?: ('mahasiswa-' . $this->mahasiswa->id)) . '/skripsi-' . $this->skripsi->id . '/skripsi-final/doc.pdf',
        'mime_type' => 'application/pdf',
        'size' => 12345,
        'uploaded_by' => $this->mahasiswa->id,
    ]);

    $this->approval = FinalDocumentApproval::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'document_version_id' => $document->id,
        'reviewer_id' => $this->dosen->id,
        'role_type' => 'pembimbing_1',
        'status' => 'pending',
    ]);

    FinalDocumentApproval::query()->create([
        'skripsi_id' => $this->skripsi->id,
        'document_version_id' => $document->id,
        'reviewer_id' => $this->otherDosen->id,
        'role_type' => 'penguji_1',
        'status' => 'pending',
    ]);
});

it('shows pending final approval queue for assigned dosen', function () {
    $this->actingAs($this->dosen)
        ->get(route('dosen.approval.index'))
        ->assertOk()
        ->assertSee('TA Final Review')
        ->assertSee('PENDING');
});

it('blocks other dosen from deciding another dosen approval row', function () {
    $this->actingAs(User::factory()->dosen()->create())
        ->post(route('dosen.approval.store', $this->approval), ['status' => 'approved'])
        ->assertForbidden();
});

it('marks skripsi selesai when all approvals approved', function () {
    FinalDocumentApproval::query()
        ->where('reviewer_id', $this->otherDosen->id)
        ->update(['status' => 'approved', 'reviewed_at' => now()]);

    $this->actingAs($this->dosen)
        ->post(route('dosen.approval.store', $this->approval), [
            'status' => 'approved',
            'note' => 'Sudah sesuai.',
        ])
        ->assertRedirect(route('dosen.approval.index'));

    $this->approval->refresh();
    $this->skripsi->refresh();

    expect($this->approval->status)->toBe('approved');
    expect($this->skripsi->current_phase)->toBe('skripsi_selesai');
});
