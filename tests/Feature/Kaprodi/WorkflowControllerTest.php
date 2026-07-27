<?php

use App\Models\DocumentVersion;
use App\Models\FormatPenilaian;
use App\Models\Grade;
use App\Models\Periode;
use App\Models\ReviewerAssignment;
use App\Models\Skripsi;
use App\Models\TahunAkademik;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function workflowPeriod(bool $active): Periode
{
    $tahun = TahunAkademik::query()->create(['tahun_awal' => $active ? 2025 : 2018, 'tahun_akhir' => $active ? 2026 : 2019]);

    return Periode::query()->create([
        'tahun_akademik_id' => $tahun->id,
        'kode_periode' => $active ? '20251' : '20181',
        'semester' => 1,
        'sk_nomor' => $active ? 'SK-ACTIVE' : 'SK-OLD',
        'tgl_mulai' => $active ? '2025-08-01' : '2018-08-01',
        'tgl_selesai' => $active ? '2026-01-31' : '2019-01-31',
        'is_aktif' => $active,
        'status' => $active ? 'active' : 'closed',
    ]);
}

function workflowSkripsi(User $student, Periode $periode, string $phase = 'review_dokumen_final'): Skripsi
{
    return Skripsi::query()->create([
        'student_id' => $student->id,
        'periode_id' => $periode->id,
        'title' => 'Workflow Final Review',
        'type' => 'skripsi',
        'current_phase' => $phase,
    ]);
}

it('does not complete final review without the required workflow evidence', function () {
    $kaprodi = User::factory()->kaprodi()->create();
    $student = User::factory()->mahasiswa()->create();
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
        'title' => 'Incomplete Final Review',
        'type' => 'skripsi',
        'current_phase' => 'review_dokumen_final',
    ]);

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.skripsi.final-review.approve', $skripsi))
        ->assertSessionHas('error');

    expect($skripsi->fresh()->current_phase)->toBe('review_dokumen_final');
});

it('completes an eligible normal final review', function () {
    $kaprodi = User::factory()->kaprodi()->create();
    $student = User::factory()->mahasiswa()->create();
    $reviewer = User::factory()->dosen()->create();
    $skripsi = workflowSkripsi($student, workflowPeriod(true));
    $assignment = ReviewerAssignment::query()->create([
        'skripsi_id' => $skripsi->id,
        'lecturer_id' => $reviewer->id,
        'role_type' => 'pembimbing_1',
    ]);
    $format = FormatPenilaian::query()->create(['nama' => 'Sidang Skripsi', 'template_type' => 'sidang_skripsi']);
    Grade::query()->create([
        'skripsi_id' => $skripsi->id,
        'format_penilaian_id' => $format->id,
        'reviewer_id' => $reviewer->id,
        'role_type' => $assignment->role_type,
        'grade_event' => 'sidang_skripsi',
        'status' => 'published',
        'score' => 85,
    ]);
    DocumentVersion::query()->create([
        'skripsi_id' => $skripsi->id,
        'phase' => 'skripsi_final',
        'version_number' => 1,
        'file_path' => 'documents/final.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
        'uploaded_by' => $student->id,
    ]);
    $this->actingAs($kaprodi)
        ->post(route('kaprodi.skripsi.final-review.approve', $skripsi))
        ->assertSessionHas('success');

    expect($skripsi->fresh()->current_phase)->toBe('skripsi_selesai');

    $this->get(route('library.index'))
        ->assertOk()
        ->assertSee($skripsi->title);
});

it('stores and completes an inactive-period legacy final document', function () {
    Storage::fake('local');
    $kaprodi = User::factory()->kaprodi()->create();
    $skripsi = workflowSkripsi(User::factory()->mahasiswa()->create(), workflowPeriod(false), 'proposal');

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.skripsi.final-review.legacy-complete', $skripsi), [
            'file' => UploadedFile::fake()->create('final.pdf', 100, 'application/pdf'),
            'phase' => 'proposal',
        ])
        ->assertSessionHas('success');

    $document = DocumentVersion::query()->where('skripsi_id', $skripsi->id)->sole();
    expect($skripsi->fresh()->current_phase)->toBe('skripsi_selesai')
        ->and($document->phase)->toBe('skripsi_final')
        ->and($document->uploaded_by)->toBe($kaprodi->id);
    Storage::disk('local')->assertExists($document->file_path);
});

it('rejects legacy completion for an active period', function () {
    Storage::fake('local');
    $kaprodi = User::factory()->kaprodi()->create();
    $skripsi = workflowSkripsi(User::factory()->mahasiswa()->create(), workflowPeriod(true));

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.skripsi.final-review.legacy-complete', $skripsi), [
            'file' => UploadedFile::fake()->create('final.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHas('error');

    expect($skripsi->fresh()->current_phase)->toBe('review_dokumen_final');
    $this->assertDatabaseCount('document_versions', 0);
});

it('validates the legacy final document file', function () {
    Storage::fake('local');
    $kaprodi = User::factory()->kaprodi()->create();
    $skripsi = workflowSkripsi(User::factory()->mahasiswa()->create(), workflowPeriod(false));

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.skripsi.final-review.legacy-complete', $skripsi))
        ->assertSessionHasErrors('file');

    $this->actingAs($kaprodi)
        ->post(route('kaprodi.skripsi.final-review.legacy-complete', $skripsi), [
            'file' => UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
        ])
        ->assertSessionHasErrors('file');

    $this->assertDatabaseCount('document_versions', 0);
});

it('forbids non kaprodi from legacy completion', function () {
    Storage::fake('local');
    $student = User::factory()->mahasiswa()->create();
    $skripsi = workflowSkripsi($student, workflowPeriod(false));

    $this->actingAs($student)
        ->post(route('kaprodi.skripsi.final-review.legacy-complete', $skripsi), [
            'file' => UploadedFile::fake()->create('final.pdf', 100, 'application/pdf'),
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('document_versions', 0);
});
