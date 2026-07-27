<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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

    $response->assertRedirect(route('mahasiswa.skripsi.index'));
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

test('login page does not expose shortcut credentials', function () {
    $this->get('/login')
        ->assertOk()
        ->assertDontSee('Akun Test')
        ->assertDontSee('login-shortcut-select', false)
        ->assertDontSee('data-password=', false)
        ->assertSee('data-password-toggle', false);
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
    $this->get('/mahasiswa/skripsi')
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
        ->get('/mahasiswa/skripsi')
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

test('invalid password keeps user logged out', function () {
    $user = User::query()->create([
        'name' => 'Invalid Password User',
        'email' => 'invalid-password@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
    ]);

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login error is translated and associated with the email field', function () {
    $this->withViewErrors([
        'email' => 'These credentials do not match our records.',
    ])->view('auth.login')
        ->assertSee('Email atau password tidak sesuai.')
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('aria-describedby="email-error"', false)
        ->assertSee('id="email-error" role="alert"', false);
});

test('logout invalidates authentication and rotates the session', function () {
    $user = User::query()->create([
        'name' => 'Logout User',
        'email' => 'logout@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
    ]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    $authenticatedSessionId = session()->getId();

    $this->post('/logout')->assertRedirect(route('login'));

    $this->assertGuest();
    expect(session()->getId())->not->toBe($authenticatedSessionId);
    $this->get('/mahasiswa/skripsi')->assertRedirect(route('login'));
});

test('repeated failed logins trigger rate limiting', function () {
    $email = 'throttled@example.test';
    $credentials = ['email' => $email, 'password' => 'wrong-password'];

    foreach (range(1, 5) as $_) {
        $this->post('/login', $credentials)->assertSessionHasErrors('email');
    }

    $key = Str::transliterate(Str::lower($email).'|127.0.0.1');
    expect(RateLimiter::tooManyAttempts($key, 5))->toBeTrue();

    $this->post('/login', $credentials)->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('soft deleted user cannot log in with password', function () {
    $user = User::query()->create([
        'name' => 'Disabled User',
        'email' => 'disabled@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
    ]);
    $user->delete();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('login errors do not reveal whether an account exists', function () {
    $user = User::query()->create([
        'name' => 'Enumeration User',
        'email' => 'enumeration@example.test',
        'password' => 'password',
        'role' => 'mahasiswa',
    ]);

    $this->post('/login', ['email' => 'unknown@example.test', 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');
    $unknownAccountError = session('errors')->first('email');

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toBe($unknownAccountError);
});
