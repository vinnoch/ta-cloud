<?php

use App\Models\FormatPenilaian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('treats SQL syntax in public search as data', function () {
    FormatPenilaian::query()->create([
        'nama' => 'Safe Format',
        'template_type' => 'sidang_skripsi',
    ]);

    $this->get(route('library.index', ['q' => "%' OR 1=1 --"]))
        ->assertOk();

    $this->assertDatabaseHas('format_penilaians', ['nama' => 'Safe Format']);
});

it('rejects query syntax supplied as a sort direction without a server error', function () {
    $kaprodi = User::factory()->kaprodi()->create();

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.formats.index', [
            'sort' => 'nama',
            'direction' => 'asc,(select sleep(5))',
        ]))
        ->assertOk();
});

it('rejects query syntax supplied as an assigned sort direction without a server error', function () {
    $kaprodi = User::factory()->kaprodi()->create();
    $format = FormatPenilaian::query()->create([
        'nama' => 'Safe Format',
        'template_type' => 'sidang_skripsi',
    ]);

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.formats.show', $format).'?assigned_direction=desc%2C%28select+sleep%285%29%29')
        ->assertOk();
});
