<?php

use App\Models\Skripsi;
use App\Models\User;
use App\Models\Periode;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mahasiswa = User::factory()->mahasiswa()->create();
    $this->otherMahasiswa = User::factory()->mahasiswa()->create();
    $tahun = \App\Models\TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    $this->periode = Periode::query()->create([
        'id' => 1, 'tahun_akademik_id' => $tahun->id, 'kode_periode' => '20251',
        'semester' => 1, 'sk_nomor' => 'SK-1', 'tgl_mulai' => '2025-08-01',
        'tgl_selesai' => '2026-01-31', 'is_aktif' => true, 'status' => 'active',
    ]);
});

it('lists own skripsi', function () {
    Skripsi::query()->create(['student_id' => $this->mahasiswa->id, 'periode_id' => 1, 'title' => 'Mine', 'type' => 'skripsi', 'current_phase' => 'proposal']);
    Skripsi::query()->create(['student_id' => $this->otherMahasiswa->id, 'periode_id' => 1, 'title' => 'Theirs', 'type' => 'skripsi', 'current_phase' => 'proposal']);

    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.skripsi.index'))
        ->assertOk()
        ->assertSee('Mine')
        ->assertDontSee('Theirs');
});

it('stores new skripsi', function () {
    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.skripsi.store'), [
            'periode_id' => 1,
            'title' => 'New Skripsi',
            'type' => 'skripsi',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('skripsis', [
        'student_id' => $this->mahasiswa->id,
        'title' => 'New Skripsi',
        'type' => 'skripsi',
        'current_phase' => 'proposal',
    ]);
});

it('shows edit updates softdeletes own skripsi', function () {
    $skripsi = Skripsi::query()->create(['student_id' => $this->mahasiswa->id, 'periode_id' => 1, 'title' => 'Old', 'type' => 'skripsi', 'current_phase' => 'proposal']);

    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.skripsi.show', $skripsi))
        ->assertOk();

    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.skripsi.edit', $skripsi))
        ->assertOk();

    $this->actingAs($this->mahasiswa)
        ->put(route('mahasiswa.skripsi.update', $skripsi), [
            'title' => 'Updated', 'type' => 'non_skripsi'
        ])
        ->assertRedirect();
    $this->assertDatabaseHas('skripsis', ['id' => $skripsi->id, 'title' => 'Updated', 'type' => 'non_skripsi']);

    $this->actingAs($this->mahasiswa)
        ->delete(route('mahasiswa.skripsi.destroy', $skripsi))
        ->assertRedirect();
    expect(Skripsi::withTrashed()->find($skripsi->id)->deleted_at)->not->toBeNull();
});

it('blocks other mahasiswa', function () {
    $skripsi = Skripsi::query()->create(['student_id' => $this->mahasiswa->id, 'periode_id' => 1, 'title' => 'Mine', 'type' => 'skripsi', 'current_phase' => 'proposal']);
    $this->actingAs($this->otherMahasiswa)->get(route('mahasiswa.skripsi.show', $skripsi))->assertForbidden();
    $this->actingAs($this->otherMahasiswa)->get(route('mahasiswa.skripsi.edit', $skripsi))->assertForbidden();
    $this->actingAs($this->otherMahasiswa)->put(route('mahasiswa.skripsi.update', $skripsi), ['title'=>'A','type'=>'skripsi'])->assertForbidden();
    $this->actingAs($this->otherMahasiswa)->delete(route('mahasiswa.skripsi.destroy', $skripsi))->assertForbidden();
});
