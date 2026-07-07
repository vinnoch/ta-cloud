<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    config()->set('database.default', 'sqlite');
    config()->set('database.connections.sqlite.database', ':memory:');

    DB::purge('sqlite');
    DB::reconnect('sqlite');

    Schema::create('users_level', function ($table) {
        $table->id('users_id');
        $table->string('users_level')->unique();
        $table->timestamps();
    });

    Schema::create('users', function ($table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->rememberToken()->nullable();
        $table->string('google_id')->nullable()->unique();
        $table->string('google_avatar')->nullable();
        $table->unsignedBigInteger('users_id')->nullable();
        $table->unsignedBigInteger('study_program_id')->nullable();
        $table->string('role')->nullable();
        $table->string('nim')->nullable();
        $table->string('nidn_nip')->nullable();
        $table->softDeletes();
        $table->timestamps();
    });

    DB::table('users_level')->insert([
        ['users_id' => 1, 'users_level' => 'kaprodi', 'created_at' => now(), 'updated_at' => now()],
        ['users_id' => 2, 'users_level' => 'dosen', 'created_at' => now(), 'updated_at' => now()],
        ['users_id' => 3, 'users_level' => 'mahasiswa', 'created_at' => now(), 'updated_at' => now()],
    ]);
});

afterEach(function () {
    \Mockery::close();
});

function makeGoogleUser(string $email, string $id = 'google-123', ?string $avatar = 'https://avatar.example.test/me.png'): SocialiteUser
{
    $user = new SocialiteUser();
    $user->map([
        'id' => $id,
        'email' => $email,
        'avatar' => $avatar,
    ]);

    return $user;
}

function stubGoogleUser(SocialiteUser $googleUser): void
{
    Socialite::shouldReceive('driver->user')
        ->once()
        ->andReturn($googleUser);
}

function createUserForGoogle(string $role = 'kaprodi', array $overrides = []): User
{
    return User::query()->create(array_merge([
        'name' => 'Google Auth User',
        'email' => 'user@widyakarya.ac.id',
        'password' => 'password',
        'role' => $role,
        'nim' => $role === 'mahasiswa' ? '2021004592' : null,
    ], $overrides));
}

test('google redirect route returns provider redirect response', function () {
    $redirect = new RedirectResponse('https://accounts.google.com/o/oauth2/auth');

    Socialite::shouldReceive('driver->redirect')
        ->once()
        ->andReturn($redirect);

    $this->get(route('auth.google'))
        ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
});

test('google callback handles provider exception gracefully', function () {
    Socialite::shouldReceive('driver->user')
        ->once()
        ->andThrow(new RuntimeException('provider boom'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Google login gagal. Silakan coba lagi.');
});

test('google callback rejects empty email', function () {
    stubGoogleUser(makeGoogleUser(''));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Login Google hanya untuk email @widyakarya.ac.id.');

    $this->assertGuest();
});

it('rejects non allowed google domain', function (string $email) {
    stubGoogleUser(makeGoogleUser($email));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Login Google hanya untuk email @widyakarya.ac.id.');

    $this->assertGuest();
})->with([
    'plain foreign domain' => ['attacker@example.com'],
    'spoof suffix domain' => ['attacker@widyakarya.ac.id.attacker.com'],
    'subdomain domain' => ['attacker@sub.widyakarya.ac.id'],
]);

test('google callback accepts uppercase valid domain and links existing user by normalized email', function () {
    $user = createUserForGoogle('kaprodi', [
        'name' => 'Upper User',
        'email' => 'upper@widyakarya.ac.id',
        'google_id' => null,
        'google_avatar' => null,
        'email_verified_at' => null,
    ]);

    $before = Carbon::now()->subSecond();
    stubGoogleUser(makeGoogleUser('UPPER@WIDYAKARYA.AC.ID', 'google-upper', 'https://avatar.example.test/upper.png'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('kaprodi.dashboard'));

    $user->refresh();

    expect($user->google_id)->toBe('google-upper')
        ->and($user->google_avatar)->toBe('https://avatar.example.test/upper.png')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->email_verified_at->greaterThanOrEqualTo($before))->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

test('google callback rejects unknown allowed-domain email', function () {
    stubGoogleUser(makeGoogleUser('unknown@widyakarya.ac.id'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Akun belum terdaftar di TACLOUD. Hubungi admin.');

    $this->assertGuest();
});

test('google callback rejects soft deleted user', function () {
    $user = createUserForGoogle('kaprodi', [
        'email' => 'inactive@widyakarya.ac.id',
    ]);
    $user->delete();

    stubGoogleUser(makeGoogleUser('inactive@widyakarya.ac.id'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Akun tidak aktif. Hubungi admin.');

    $this->assertGuest();
});

test('google callback preserves existing google id for known user', function () {
    $user = createUserForGoogle('kaprodi', [
        'email' => 'known@widyakarya.ac.id',
        'email_verified_at' => now()->subDay(),
    ]);

    $user->forceFill([
        'google_id' => 'existing-google-id',
        'google_avatar' => 'https://avatar.example.test/original.png',
    ])->save();

    stubGoogleUser(makeGoogleUser('known@widyakarya.ac.id', 'new-google-id', 'https://avatar.example.test/new.png'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('kaprodi.dashboard'));

    $user->refresh();

    expect($user->google_id)->toBe('existing-google-id')
        ->and($user->google_avatar)->toBe('https://avatar.example.test/new.png');

    $this->assertAuthenticatedAs($user);
});

test('google callback regenerates session after successful login', function () {
    $user = createUserForGoogle('kaprodi', [
        'email' => 'session@widyakarya.ac.id',
    ]);

    stubGoogleUser(makeGoogleUser('session@widyakarya.ac.id', 'google-session'));

    $session = app('session')->driver();
    $session->start();
    $oldId = $session->getId();

    $this->withSession(['_token' => csrf_token()])
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('kaprodi.dashboard'));

    expect(app('session')->driver()->getId())->not->toBe($oldId);
    $this->assertAuthenticatedAs($user);
});

test('google callback redirects kaprodi to kaprodi dashboard', function () {
    $user = createUserForGoogle('kaprodi', [
        'email' => 'kaprodi@widyakarya.ac.id',
    ]);

    stubGoogleUser(makeGoogleUser('kaprodi@widyakarya.ac.id', 'google-kaprodi'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('kaprodi.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('google callback redirects dosen to dosen dashboard', function () {
    $user = createUserForGoogle('dosen', [
        'email' => 'dosen@widyakarya.ac.id',
    ]);

    stubGoogleUser(makeGoogleUser('dosen@widyakarya.ac.id', 'google-dosen'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('dosen.dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('google callback redirects mahasiswa to mahasiswa dashboard route', function () {
    $user = createUserForGoogle('mahasiswa', [
        'email' => 'mahasiswa@widyakarya.ac.id',
    ]);

    stubGoogleUser(makeGoogleUser('mahasiswa@widyakarya.ac.id', 'google-mahasiswa'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(AuthenticatedSessionController::dashboardRouteForRole('mahasiswa'));

    $this->assertAuthenticatedAs($user);
});

test('authenticated user cannot access google redirect route because it is guest only', function () {
    $user = createUserForGoogle('kaprodi', [
        'email' => 'auth-redirect@widyakarya.ac.id',
    ]);

    $this->actingAs($user)
        ->get(route('auth.google'))
        ->assertRedirect(route('home'));
});

test('authenticated user cannot access google callback route because it is guest only', function () {
    $user = createUserForGoogle('kaprodi', [
        'email' => 'auth-callback@widyakarya.ac.id',
    ]);

    $this->actingAs($user)
        ->get(route('auth.google.callback'))
        ->assertRedirect(route('home'));
});

test('google callback should not allow external redirect via intended url', function () {
    $user = createUserForGoogle('kaprodi', [
        'email' => 'openredirect@widyakarya.ac.id',
    ]);

    stubGoogleUser(makeGoogleUser('openredirect@widyakarya.ac.id', 'google-open-redirect'));

    $response = $this->withSession(['url.intended' => 'https://evil.com'])
        ->get(route('auth.google.callback'));

    expect($response->headers->get('Location'))->not->toStartWith('https://evil.com');
    $this->assertAuthenticatedAs($user);
});
