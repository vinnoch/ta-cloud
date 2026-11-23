<?php

use App\Models\Periode;
use App\Models\TahunAkademik;
use App\Models\TugasAkhir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mahasiswa = User::factory()->mahasiswa()->create();
    $this->otherMahasiswa = User::factory()->mahasiswa()->create();
    $this->dosen = User::factory()->dosen()->create();

    $tahun = TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    $this->periode = Periode::query()->create([
        'id' => 1,
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

it('opens the unified create form with non skripsi selected', function () {
    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.non-skripsi.create'))
        ->assertOk()
        ->assertSee('Buat Non-Skripsi Baru')
        ->assertSee('Saya memahami perbedaan Skripsi dan Non-Skripsi')
        ->assertSee('name="understands_tugas_akhir_types"', false);
});

it('registers either tugas akhir type through one pendaftaran form', function () {
    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.pendaftaran.create'))
        ->assertOk()
        ->assertSee('Pendaftaran Tugas Akhir')
        ->assertDontSee('>Batal<', false)
        ->assertSee('menjelaskan, menguji, mengevaluasi, membandingkan, memprediksi')
        ->assertSee('sistem, aplikasi, prototipe, dasbor, model operasional');

    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.pendaftaran.store'), [
            'periode_id' => $this->periode->id,
            'title' => 'Proyek Operasional',
            'type' => 'non_skripsi',
        ])
        ->assertRedirect(route('mahasiswa.non-skripsi.index'));

    $this->assertDatabaseHas('tugas_akhirs', [
        'student_id' => $this->mahasiswa->id,
        'title' => 'Proyek Operasional',
        'type' => 'non_skripsi',
    ]);
});

it('requires a valid type on the unified pendaftaran route', function () {
    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.pendaftaran.store'), [
            'periode_id' => $this->periode->id,
            'title' => 'Tipe Tidak Valid',
            'type' => 'lainnya',
        ])
        ->assertSessionHasErrors('type');

    $this->assertDatabaseCount('tugas_akhirs', 0);
});

it('requires understanding confirmation before submitting a new proposal', function () {
    $payload = [
        'periode_id' => $this->periode->id,
        'title' => 'Proyek Dengan Konfirmasi',
        'type' => 'non_skripsi',
        'save_mode' => 'published',
    ];

    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.pendaftaran.store'), $payload)
        ->assertSessionHasErrors('understands_tugas_akhir_types');

    $this->assertDatabaseCount('tugas_akhirs', 0);

    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.pendaftaran.store'), $payload + [
            'understands_tugas_akhir_types' => '1',
        ])
        ->assertRedirect(route('mahasiswa.non-skripsi.index'));

    $this->assertDatabaseHas('tugas_akhirs', [
        'student_id' => $this->mahasiswa->id,
        'proposal_review_status' => 'published',
    ]);
});

it('requires understanding confirmation before publishing a saved draft', function () {
    $draft = TugasAkhir::query()->create([
        'student_id' => $this->mahasiswa->id,
        'periode_id' => $this->periode->id,
        'title' => 'Draft Proposal',
        'type' => 'non_skripsi',
        'current_phase' => 'proposal',
        'proposal_review_status' => 'draft',
    ]);

    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.non-skripsi.publish', $draft))
        ->assertSessionHasErrors('understands_tugas_akhir_types');

    expect($draft->fresh()->proposal_review_status)->toBe('draft');

    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.non-skripsi.publish', $draft), [
            'understands_tugas_akhir_types' => '1',
        ])
        ->assertRedirect(route('mahasiswa.non-skripsi.index'));

    expect($draft->fresh()->proposal_review_status)->toBe('published');
});

it('notifies only active kaprodi and dosen in the student study program', function () {
    $departmentId = DB::table('departments')->insertGetId([
        'code' => 'FT', 'name' => 'Fakultas Teknik', 'is_active' => true,
    ]);
    $programId = DB::table('study_programs')->insertGetId([
        'department_id' => $departmentId, 'code' => 'SI', 'name' => 'Sistem Informasi', 'is_active' => true,
    ]);
    $otherProgramId = DB::table('study_programs')->insertGetId([
        'department_id' => $departmentId, 'code' => 'TI', 'name' => 'Teknik Informatika', 'is_active' => true,
    ]);
    $this->mahasiswa->update(['study_program_id' => $programId]);

    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $programId]);
    $dosen = User::factory()->dosen()->create(['study_program_id' => $programId]);
    $otherProgramDosen = User::factory()->dosen()->create(['study_program_id' => $otherProgramId]);
    $pendingDosen = User::factory()->dosen()->create([
        'study_program_id' => $programId,
        'account_status' => 'pending',
    ]);
    $draft = TugasAkhir::query()->create([
        'student_id' => $this->mahasiswa->id,
        'periode_id' => $this->periode->id,
        'title' => 'Scoped Proposal',
        'type' => 'non_skripsi',
        'current_phase' => 'proposal',
        'proposal_review_status' => 'draft',
    ]);

    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.non-skripsi.publish', $draft), [
            'understands_tugas_akhir_types' => '1',
        ])
        ->assertRedirect(route('mahasiswa.non-skripsi.index'));

    expect($kaprodi->notifications()->count())->toBe(1)
        ->and($dosen->notifications()->count())->toBe(1)
        ->and($otherProgramDosen->notifications()->count())->toBe(0)
        ->and($pendingDosen->notifications()->count())->toBe(0)
        ->and($kaprodi->notifications()->sole()->data['url'])->toContain('/kaprodi/')
        ->and($dosen->notifications()->sole()->data['url'])->toBe('/dosen/proposal');
});

it('stores non skripsi through the unified tugas akhir route', function () {
    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.non-skripsi.store'), [
            'periode_id' => $this->periode->id,
            'title' => 'Tugas Akhir Non Skripsi',
            'type' => 'non_skripsi',
            'journal_article_url' => 'https://example.com/article',
        ])
        ->assertRedirect(route('mahasiswa.non-skripsi.index'));

    $this->assertDatabaseHas('tugas_akhirs', [
        'student_id' => $this->mahasiswa->id,
        'title' => 'Tugas Akhir Non Skripsi',
        'type' => 'non_skripsi',
        'journal_article_url' => 'https://example.com/article',
        'current_phase' => 'proposal',
    ]);
});

it('shows and updates owned non skripsi through stable public routes', function () {
    $tugasAkhir = TugasAkhir::query()->create([
        'student_id' => $this->mahasiswa->id,
        'periode_id' => $this->periode->id,
        'title' => 'Non Skripsi Lama',
        'type' => 'non_skripsi',
        'current_phase' => 'proposal',
        'proposal_review_status' => 'draft',
    ]);

    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.non-skripsi.show', $tugasAkhir))
        ->assertOk();

    $this->actingAs($this->mahasiswa)
        ->put(route('mahasiswa.non-skripsi.update', $tugasAkhir), [
            'title' => 'Non Skripsi Baru',
            'type' => 'non_skripsi',
            'journal_article_url' => 'https://example.com/updated',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('tugas_akhirs', [
        'id' => $tugasAkhir->id,
        'student_id' => $this->mahasiswa->id,
        'title' => 'Non Skripsi Baru',
        'type' => 'non_skripsi',
        'journal_article_url' => 'https://example.com/updated',
    ]);
});

it('blocks another mahasiswa from owned non skripsi routes', function () {
    $tugasAkhir = TugasAkhir::query()->create([
        'student_id' => $this->mahasiswa->id,
        'periode_id' => $this->periode->id,
        'title' => 'Owner Only',
        'type' => 'non_skripsi',
        'current_phase' => 'proposal',
    ]);

    $this->actingAs($this->otherMahasiswa)
        ->get(route('mahasiswa.non-skripsi.show', $tugasAkhir))
        ->assertForbidden();

    $this->actingAs($this->otherMahasiswa)
        ->put(route('mahasiswa.non-skripsi.update', $tugasAkhir), [
            'title' => 'Forged',
            'type' => 'non_skripsi',
        ])
        ->assertForbidden();

    $this->actingAs($this->otherMahasiswa)
        ->delete(route('mahasiswa.non-skripsi.destroy', $tugasAkhir))
        ->assertForbidden();
});

it('blocks dosen from mahasiswa tugas akhir routes', function () {
    $this->actingAs($this->dosen)
        ->get(route('mahasiswa.non-skripsi.index'))
        ->assertForbidden();
});
