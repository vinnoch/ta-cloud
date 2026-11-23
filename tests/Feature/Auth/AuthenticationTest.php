<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

test('login page exposes only google authentication', function () {
    $this->get('/login')
        ->assertOk()
        ->assertDontSee('Akun Test')
        ->assertDontSee('login-shortcut-select', false)
        ->assertDontSee('data-password=', false)
        ->assertDontSee('type="password"', false)
        ->assertSee(route('auth.google'), false)
        ->assertSee('Masuk dengan Google');

    $this->post('/login', [
        'email' => 'user@widyakarya.ac.id',
        'password' => 'password',
    ])->assertMethodNotAllowed();

    $this->assertGuest();
});

test('production seeding creates master data without user credentials', function () {
    $this->seed();

    $this->assertDatabaseCount('users', 0)
        ->assertDatabaseCount('users_level', 5)
        ->assertDatabaseCount('departments', 1)
        ->assertDatabaseCount('study_programs', 2);
});

test('role middleware aborts when user role is not allowed', function () {
    Route::middleware(['web', 'role:dosen'])->get('/_test-role-dosen', fn () => 'ok');
    $user = User::factory()->mahasiswa()->create();

    $this->actingAs($user)->get('/_test-role-dosen')->assertForbidden();
});

test('workspace routes require authentication', function () {
    $this->get('/mahasiswa/skripsi')->assertRedirect(route('login'));
});

test('role workspaces reject other roles', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();
    $dosen = User::factory()->dosen()->create();
    $kaprodi = User::factory()->kaprodi()->create();

    $this->actingAs($dosen)->get('/mahasiswa/skripsi')->assertForbidden();
    $this->actingAs($mahasiswa)->get('/dosen/dashboard')->assertForbidden();
    $this->actingAs($kaprodi)->get('/dosen/dashboard')->assertForbidden();
    $this->actingAs($dosen)->get('/overview')->assertForbidden();
});

test('root redirects authenticated users to their role dashboard', function () {
    $dosen = User::factory()->dosen()->create();

    $this->actingAs($dosen)->get('/')->assertRedirect(route('dosen.dashboard'));
});

test('logout invalidates authentication and rotates the session', function () {
    $user = User::factory()->mahasiswa()->create();
    $this->actingAs($user);
    $authenticatedSessionId = session()->getId();

    $this->post('/logout')->assertRedirect(route('login'));

    $this->assertGuest();
    expect(session()->getId())->not->toBe($authenticatedSessionId);
    $this->get('/mahasiswa/skripsi')->assertRedirect(route('login'));
});

test('get logout never changes authentication', function () {
    $this->get('/logout')->assertRedirect(route('home'));
    $this->assertGuest();

    $user = User::factory()->dosen()->create();
    $this->actingAs($user)->get('/logout')->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($user);
});
