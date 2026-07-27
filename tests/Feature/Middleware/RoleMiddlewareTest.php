<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects unauthenticated user to login on mahasiswa route', function () {
    $this->get(route('mahasiswa.skripsi.index'))
        ->assertRedirect('/login');
});

it('redirects unauthenticated user to login on dosen route', function () {
    $this->get(route('dosen.dashboard'))
        ->assertRedirect('/login');
});

it('redirects unauthenticated user to login on kaprodi route', function () {
    $this->get(route('kaprodi.dashboard'))
        ->assertRedirect('/login');
});

it('blocks mahasiswa from dosen route', function () {
    $user = User::factory()->mahasiswa()->create();

    $this->actingAs($user)
        ->get(route('dosen.dashboard'))
        ->assertForbidden();
});

it('blocks mahasiswa from kaprodi route', function () {
    $user = User::factory()->mahasiswa()->create();

    $this->actingAs($user)
        ->get(route('kaprodi.dashboard'))
        ->assertForbidden();
});

it('blocks dosen from mahasiswa route', function () {
    $user = User::factory()->dosen()->create();

    $this->actingAs($user)
        ->get(route('mahasiswa.skripsi.index'))
        ->assertForbidden();
});

it('blocks dosen from kaprodi route', function () {
    $user = User::factory()->dosen()->create();

    $this->actingAs($user)
        ->get(route('kaprodi.dashboard'))
        ->assertForbidden();
});

it('blocks kaprodi from mahasiswa route', function () {
    $user = User::factory()->kaprodi()->create();

    $this->actingAs($user)
        ->get(route('mahasiswa.skripsi.index'))
        ->assertForbidden();
});

it('blocks kaprodi from dosen route', function () {
    $user = User::factory()->kaprodi()->create();

    $this->actingAs($user)
        ->get(route('dosen.dashboard'))
        ->assertForbidden();
});

it('allows mahasiswa on mahasiswa route', function () {
    $user = User::factory()->mahasiswa()->create();

    $this->actingAs($user)
        ->get(route('mahasiswa.non-skripsi.index'))
        ->assertOk();
});

it('allows dosen on dosen route', function () {
    $user = User::factory()->dosen()->create();

    $this->actingAs($user)
        ->get(route('dosen.penilaian.index'))
        ->assertOk();
});

it('allows kaprodi on kaprodi route', function () {
    $user = User::factory()->kaprodi()->create();

    $this->actingAs($user)
        ->get(route('kaprodi.dashboard'))
        ->assertOk();
});

it('blocks forged write requests across role boundaries', function (string $role, string $route) {
    $user = User::factory()->{$role}()->create();

    $response = $this->actingAs($user)
        ->post(route($route, ['skripsi' => 999999]));

    expect($response->getStatusCode())->toBeIn([403, 404]);
})->with([
    'mahasiswa to dosen action' => ['mahasiswa', 'dosen.penilaian.store'],
    'dosen to kaprodi action' => ['dosen', 'kaprodi.dosen.store'],
    'kaprodi to mahasiswa action' => ['kaprodi', 'mahasiswa.skripsi.store'],
]);

it('rechecks the current database role for an existing authenticated session', function () {
    $user = User::factory()->mahasiswa()->create();

    $this->actingAs($user);

    $user->update(['role' => 'dosen']);

    $this->get(route('mahasiswa.skripsi.index'))
        ->assertForbidden();

    $this->get(route('dosen.dashboard'))
        ->assertOk();
});
