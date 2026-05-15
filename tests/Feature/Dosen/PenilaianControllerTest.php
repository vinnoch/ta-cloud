<?php

use App\Models\FormatPenilaian;
use App\Models\ItemPenilaian;
use App\Models\ReviewerAssignment;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->dosen = User::factory()->dosen()->create();
    $this->otherDosen = User::factory()->dosen()->create();
    $this->mahasiswa = User::factory()->mahasiswa()->create();
    $tahun = \App\Models\TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    $this->periode = \App\Models\Periode::query()->create([
        'id' => 1,'tahun_akademik_id' => $tahun->id,'kode_periode' => '20251','semester' => 1,
        'sk_nomor' => 'SK-1','tgl_mulai' => '2025-08-01','tgl_selesai' => '2026-01-31','is_aktif' => true,'status' => 'active',
    ]);
    $this->skripsi = Skripsi::query()->create(['student_id' => $this->mahasiswa->id,'periode_id' => 1,'title' => 'TA','type' => 'skripsi','current_phase' => 'sidang_skripsi']);
    ReviewerAssignment::query()->create(['skripsi_id' => $this->skripsi->id, 'lecturer_id' => $this->dosen->id, 'role_type' => 'penguji_1']);

    $this->format = FormatPenilaian::query()->create(['nama' => 'Format Sidang', 'template_type' => 'sidang_skripsi', 'is_published' => true, 'is_locked' => false, 'is_default' => true]);
    DB::table('format_periode')->insert(['format_penilaian_id' => $this->format->id, 'periode_id' => $this->periode->id, 'created_at' => now(), 'updated_at' => now()]);
    ItemPenilaian::query()->create(['format_penilaian_id' => $this->format->id, 'nama' => 'Aspek 1', 'kode' => 'A1', 'bobot' => 40, 'sort_order' => 1]);
    ItemPenilaian::query()->create(['format_penilaian_id' => $this->format->id, 'nama' => 'Aspek 2', 'kode' => 'A2', 'bobot' => 60, 'sort_order' => 2]);
});

it('stores weighted grade for assigned dosen', function () {
    $this->actingAs($this->dosen)->get(route('dosen.penilaian.show', $this->skripsi))->assertOk();

    $items = $this->format->items()->orderBy('sort_order')->get();
    $this->actingAs($this->dosen)->post(route('dosen.penilaian.store', $this->skripsi), [
        'submit_action' => 'final',
        'scores' => [
            $items[0]->id => 80,
            $items[1]->id => 90,
        ],
    ])->assertRedirect();

    $this->assertDatabaseHas('grades', [
        'skripsi_id' => $this->skripsi->id,
        'reviewer_id' => $this->dosen->id,
        'grade_event' => 'sidang_skripsi',
        'status' => 'published',
        'score' => 86,
    ]);
});

it('blocks unassigned dosen from grading', function () {
    $this->actingAs($this->otherDosen)->get(route('dosen.penilaian.show', $this->skripsi))->assertSessionHasErrors();
});
