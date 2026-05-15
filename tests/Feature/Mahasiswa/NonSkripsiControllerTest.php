<?php

use App\Models\NonSkripsiRecord;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createNonSkripsiFor(User $user): Skripsi {
    return Skripsi::query()->create([
        'student_id' => $user->id,
        'periode_id' => 1,
        'title' => 'TA Non Skripsi',
        'type' => 'non_skripsi',
        'current_phase' => 'proposal',
    ]);
}

beforeEach(function () {
    $this->mahasiswa = User::factory()->mahasiswa()->create();
    $this->otherMahasiswa = User::factory()->mahasiswa()->create();
    $this->dosen = User::factory()->dosen()->create();

    // periode seed minimal
    $tahun = \App\Models\TahunAkademik::query()->create(['tahun_awal' => 2025, 'tahun_akhir' => 2026]);
    \App\Models\Periode::query()->create([
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

it('shows index for owner', function () {
    $skripsi = createNonSkripsiFor($this->mahasiswa);
    NonSkripsiRecord::query()->create([
        'skripsi_id' => $skripsi->id,
        'summary' => 'Judul NS',
        'abstract' => 'Abstrak',
        'final_score' => 88,
        'publication_url' => 'https://example.com/pub',
    ]);

    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.non-skripsi.index'))
        ->assertOk()
        ->assertSee('Judul NS');
});

it('shows create page for owner with non skripsi', function () {
    createNonSkripsiFor($this->mahasiswa);

    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.non-skripsi.create'))
        ->assertOk();
});

it('stores valid data', function () {
    $skripsi = createNonSkripsiFor($this->mahasiswa);

    $this->actingAs($this->mahasiswa)
        ->post(route('mahasiswa.non-skripsi.store'), [
            'title' => 'Judul NS',
            'abstract' => 'Abstrak panjang',
            'final_score' => 90,
            'link_publikasi' => 'https://example.com/publikasi',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('non_skripsi_records', [
        'skripsi_id' => $skripsi->id,
        'summary' => 'Judul NS',
        'abstract' => 'Abstrak panjang',
        'final_score' => 90.0,
        'publication_url' => 'https://example.com/publikasi',
    ]);
});

it('rejects invalid data', function () {
    createNonSkripsiFor($this->mahasiswa);

    $this->actingAs($this->mahasiswa)
        ->from(route('mahasiswa.non-skripsi.create'))
        ->post(route('mahasiswa.non-skripsi.store'), [
            'title' => '',
            'abstract' => '',
            'final_score' => 101,
            'link_publikasi' => 'not-url',
        ])
        ->assertRedirect(route('mahasiswa.non-skripsi.create'))
        ->assertSessionHasErrors(['title', 'abstract', 'final_score', 'link_publikasi']);
});

it('edits and updates own record', function () {
    $skripsi = createNonSkripsiFor($this->mahasiswa);
    $record = NonSkripsiRecord::query()->create([
        'skripsi_id' => $skripsi->id,
        'summary' => 'Lama',
        'abstract' => 'Abstrak',
    ]);

    $this->actingAs($this->mahasiswa)
        ->get(route('mahasiswa.non-skripsi.edit', $record))
        ->assertOk();

    $this->actingAs($this->mahasiswa)
        ->put(route('mahasiswa.non-skripsi.update', $record), [
            'title' => 'Baru',
            'abstract' => 'Abstrak baru',
            'final_score' => 77,
            'link_publikasi' => 'https://example.com/new',
        ])
        ->assertRedirect(route('mahasiswa.non-skripsi.show', $record));

    $this->assertDatabaseHas('non_skripsi_records', [
        'id' => $record->id,
        'summary' => 'Baru',
        'abstract' => 'Abstrak baru',
        'final_score' => 77.0,
        'publication_url' => 'https://example.com/new',
    ]);
});

it('soft deletes own record', function () {
    $skripsi = createNonSkripsiFor($this->mahasiswa);
    $record = NonSkripsiRecord::query()->create([
        'skripsi_id' => $skripsi->id,
        'summary' => 'Hapus',
        'abstract' => 'Abstrak',
    ]);

    $this->actingAs($this->mahasiswa)
        ->delete(route('mahasiswa.non-skripsi.destroy', $record))
        ->assertRedirect(route('mahasiswa.non-skripsi.index'));

    expect(NonSkripsiRecord::withTrashed()->find($record->id)?->deleted_at)->not->toBeNull();
});

it('blocks other mahasiswa from show edit update delete', function () {
    $skripsi = createNonSkripsiFor($this->mahasiswa);
    $record = NonSkripsiRecord::query()->create([
        'skripsi_id' => $skripsi->id,
        'summary' => 'Owner only',
        'abstract' => 'Abstrak',
    ]);

    $this->actingAs($this->otherMahasiswa)->get(route('mahasiswa.non-skripsi.show', $record))->assertForbidden();
    $this->actingAs($this->otherMahasiswa)->get(route('mahasiswa.non-skripsi.edit', $record))->assertForbidden();
    $this->actingAs($this->otherMahasiswa)->put(route('mahasiswa.non-skripsi.update', $record), [
        'title' => 'X', 'abstract' => 'Y'
    ])->assertForbidden();
    $this->actingAs($this->otherMahasiswa)->delete(route('mahasiswa.non-skripsi.destroy', $record))->assertForbidden();
});

it('blocks dosen from mahasiswa non skripsi routes', function () {
    $this->actingAs($this->dosen)
        ->get(route('mahasiswa.non-skripsi.index'))
        ->assertForbidden();
});
