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
