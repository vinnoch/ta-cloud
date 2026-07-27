<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function routeUser(string $role, array $attributes = []): User
{
    return User::query()->create(array_merge([
        'name' => ucfirst($role).' User',
        'email' => $role.'.'.fake()->unique()->safeEmail(),
        'password' => 'password',
        'role' => $role,
    ], $attributes));
}

test('global routes stay registered after route split', function () {
    $kaprodi = routeUser('kaprodi');

    $this->actingAs($kaprodi)
        ->get(route('dashboard.index'))
        ->assertOk()
        ->assertSee('TA Cloud Frontend');

    $this->get(route('library.index'))
        ->assertOk()
        ->assertSee('Library Skripsi');

    $this->get(route('library.show', 'arsitektur-microservices-cloud'))
        ->assertNotFound();
});

test('workspace route files load for each role', function () {
    $kaprodi = routeUser('kaprodi');
    $dosen = routeUser('dosen');
    $mahasiswa = routeUser('mahasiswa', ['nim' => '2021004592']);

    $this->actingAs($kaprodi)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Admin');

    $this->actingAs($kaprodi)
        ->get(route('kaprodi.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Kaprodi');

    $this->actingAs($dosen)
        ->get(route('dosen.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Dosen');

    $this->actingAs($mahasiswa)
        ->get(route('mahasiswa.skripsi.index'))
        ->assertOk()
        ->assertSee('Skripsi Tidak Ditemukan');
});

test('named routes are unique for production route caching', function () {
    $duplicates = collect(Route::getRoutes()->getRoutes())
        ->pluck('action.as')
        ->filter()
        ->duplicates()
        ->values()
        ->all();

    expect($duplicates)->toBe([]);
    expect(route('kaprodi.document-templates.add-periode', 1, false))
        ->toBe('/kaprodi/dokumen-final/1/add-periode')
        ->and(route('kaprodi.document-templates.add-periode.legacy', 1, false))
        ->toBe('/kaprodi/document-templates/1/add-periode');
});
