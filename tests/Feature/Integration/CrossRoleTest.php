<?php

use App\Models\Bimbingan;
use App\Models\FormatPenilaian;
use App\Models\ItemPenilaian;
use App\Models\ReviewerAssignment;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->kaprodi = User::factory()->kaprodi()->create();
    $this->dosen = User::factory()->dosen()->create();
    $this->mahasiswa = User::factory()->mahasiswa()->create();
    $this->otherMahasiswa = User::factory()->mahasiswa()->create();

    $tahun = \App\Models\TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    $this->periode = \App\Models\Periode::query()->create([
        'id' => 1,'tahun_akademik_id' => $tahun->id,'kode_periode' => '20251','semester' => 1,
        'sk_nomor' => 'SK-1','tgl_mulai' => '2025-08-01','tgl_selesai' => '2026-01-31','is_aktif' => true,'status' => 'active',
    ]);
    $this->skripsi = Skripsi::query()->create([
        'student_id' => $this->mahasiswa->id,'periode_id' => 1,'title' => 'TA Integrasi','type' => 'skripsi','current_phase' => 'sidang_skripsi'
    ]);
    ReviewerAssignment::query()->create(['skripsi_id' => $this->skripsi->id, 'lecturer_id' => $this->dosen->id, 'role_type' => 'penguji_1']);

    $this->format = FormatPenilaian::query()->create(['nama' => 'Format Sidang', 'template_type' => 'sidang_skripsi', 'is_published' => true, 'is_locked' => false, 'is_default' => true]);
    DB::table('format_periode')->insert(['format_penilaian_id' => $this->format->id, 'periode_id' => $this->periode->id, 'created_at' => now(), 'updated_at' => now()]);
    ItemPenilaian::query()->create(['format_penilaian_id' => $this->format->id, 'nama' => 'Aspek 1', 'kode' => 'A1', 'bobot' => 100, 'sort_order' => 1]);
    Storage::fake('local');
});

it('mahasiswa upload -> dosen can see doc in bimbingan create form', function () {
    $file = UploadedFile::fake()->create('doc.pdf', 1000, 'application/pdf');

    $this->actingAs($this->mahasiswa)->post(route('mahasiswa.skripsi.documents.store', $this->skripsi), [
        'file' => $file,
        'phase' => 'proposal',
    ])->assertRedirect();

    $this->actingAs($this->dosen)
        ->get(route('dosen.bimbingan.create', $this->skripsi))
        ->assertOk()
        ->assertSee('proposal v1');
});

it('dosen creates bimbingan -> mahasiswa can view', function () {
    $this->actingAs($this->dosen)->post(route('dosen.bimbingan.store', $this->skripsi), [
        'meeting_date' => '2026-05-05',
        'phase' => 'proposal',
        'lecturer_notes' => 'Catatan dosen',
    ])->assertRedirect();

    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.skripsi.bimbingan.index', $this->skripsi))
        ->assertOk()
        ->assertSee('Catatan dosen');
});

it('dosen stores grade -> mahasiswa can view', function () {
    $item = $this->format->items()->first();

    $this->actingAs($this->dosen)->post(route('dosen.penilaian.store', $this->skripsi), [
        'submit_action' => 'final',
        'scores' => [$item->id => 88],
    ])->assertRedirect();

    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.skripsi.nilai.index', $this->skripsi))
        ->assertOk()
        ->assertSee('88');
});

it('wrong role on route -> 403', function () {
    $this->actingAs($this->otherMahasiswa)
        ->get(route('dosen.skripsi.show', $this->skripsi))
        ->assertForbidden();
});
