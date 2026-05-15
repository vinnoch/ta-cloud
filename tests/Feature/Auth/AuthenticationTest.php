<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('login redirects users to their role dashboard', function () {
    $user = User::query()->create([
        'name' => 'Adrian Sterling',
        'email' => 'adrian@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
        'nim' => '2021004592',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('mahasiswa.dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('login page shows seeded test account shortcuts', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Akun Test')
        ->assertSee('kaprodi@tacloud.test')
        ->assertSee('sarah.wijaya@tacloud.test')
        ->assertSee('adrian.sterling@tacloud.test')
        ->assertSee('data-password-toggle', false);
});

test('role middleware aborts when user role is not allowed', function () {
    Route::middleware(['web', 'role:dosen'])->get('/_test-role-dosen', fn() => 'ok');

    $user = User::query()->create([
        'name' => 'Adrian Sterling',
        'email' => 'adrian@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
        'nim' => '2021004592',
    ]);

    $this->actingAs($user)
        ->get('/_test-role-dosen')
        ->assertForbidden();
});

test('workspace routes require authentication', function () {
    $this->get('/mahasiswa/dashboard')
        ->assertRedirect(route('login'));
});

test('dosen cannot access mahasiswa workspace by direct url', function () {
    $dosen = User::query()->create([
        'name' => 'Dr. Sarah Wijaya',
        'email' => 'sarah@example.test',
        'password' => 'password',
        'role' => 'dosen',
    ]);

    $this->actingAs($dosen)
        ->get('/mahasiswa/dashboard')
        ->assertForbidden();
});

test('mahasiswa cannot access dosen workspace by direct url', function () {
    $mahasiswa = User::query()->create([
        'name' => 'Adrian Sterling',
        'email' => 'adrian2@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
        'nim' => '2021004592',
    ]);

    $this->actingAs($mahasiswa)
        ->get('/dosen/dashboard')
        ->assertForbidden();
});

test('kaprodi cannot access dosen workspace by direct url', function () {
    $kaprodi = User::query()->create([
        'name' => 'Kaprodi Sistem Informasi',
        'email' => 'kaprodi.direct@example.test',
        'password' => 'password',
        'role' => 'kaprodi',
    ]);

    $this->actingAs($kaprodi)
        ->get('/dosen/dashboard')
        ->assertForbidden();
});

test('root redirects authenticated users to their role dashboard', function () {
    $dosen = User::query()->create([
        'name' => 'Dr. Sarah Wijaya',
        'email' => 'sarah.redirect@example.test',
        'password' => 'password',
        'role' => 'dosen',
    ]);

    $this->actingAs($dosen)
        ->get('/')
        ->assertRedirect(route('dosen.dashboard'));
});

test('global overview is not available to non kaprodi users', function () {
    $dosen = User::query()->create([
        'name' => 'Dr. Sarah Wijaya',
        'email' => 'sarah.overview@example.test',
        'password' => 'password',
        'role' => 'dosen',
    ]);

    $this->actingAs($dosen)
        ->get('/overview')
        ->assertForbidden();
});
