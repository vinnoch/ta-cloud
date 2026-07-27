<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

uses(RefreshDatabase::class);

function superadminUser(array $attributes = []): User
{
    return User::query()->create(array_merge([
        'name' => 'System Operator',
        'email' => 'operator@widyakarya.ac.id',
        'password' => 'unused-password',
        'role' => 'superadmin',
    ], $attributes));
}

it('seeds superadmin role without credentials', function () {
    $this->seed();

    $this->assertDatabaseHas('users_level', ['users_level' => 'superadmin'])
        ->assertDatabaseCount('users', 0);
});

it('bootstraps one exact institutional account', function () {
    config()->set('services.google.allowed_domain', 'widyakarya.ac.id');

    $this->artisan('superadmin:bootstrap', ['email' => 'OWNER@WIDYAKARYA.AC.ID', '--force' => true])
        ->assertSuccessful();

    $this->assertDatabaseHas('users', ['email' => 'owner@widyakarya.ac.id', 'role' => 'superadmin'])
        ->assertDatabaseHas('audit_logs', ['action' => 'superadmin.bootstrapped']);

    $this->artisan('superadmin:bootstrap', ['email' => 'second@widyakarya.ac.id', '--force' => true])
        ->assertFailed();
});

it('rejects non-exact institutional bootstrap domains', function (string $email) {
    config()->set('services.google.allowed_domain', 'widyakarya.ac.id');

    $this->artisan('superadmin:bootstrap', ['email' => $email, '--force' => true])->assertFailed();
})->with(['outside@example.com', 'spoof@widyakarya.ac.id.attacker.test', 'sub@sub.widyakarya.ac.id']);

it('isolates superadmin routes from other roles', function () {
    $kaprodi = User::factory()->kaprodi()->create();
    $superadmin = superadminUser();

    $this->actingAs($kaprodi)->get(route('superadmin.dashboard'))->assertForbidden();
    $this->actingAs($superadmin)->get(route('superadmin.dashboard'))
        ->assertOk()
        ->assertSee('Database terkoneksi')
        ->assertSee('acss-dashboard-metric__status-icon', false);
    $this->actingAs($superadmin)->get(route('kaprodi.dashboard'))->assertForbidden();
});

it('renders account actions in the user cell and the last login time', function () {
    $superadmin = superadminUser();
    $superadmin->forceFill(['last_login_at' => now()])->saveQuietly();

    $this->actingAs($superadmin)->get(route('superadmin.users.index'))
        ->assertOk()
        ->assertSeeInOrder(['Akun', 'Peran', 'Status', 'Login Terakhir'])
        ->assertDontSee('Tindakan')
        ->assertSee('data-account-edit-open', false)
        ->assertSee('data-account-edit-modal', false)
        ->assertSee($superadmin->last_login_at->translatedFormat('d M Y'));
});

it('shows safe database server and google information only to superadmins', function () {
    config()->set('services.google.client_secret', 'never-render-this-client-secret');
    $superadmin = superadminUser();
    $kaprodi = User::factory()->kaprodi()->create();

    $this->actingAs($kaprodi)->get(route('superadmin.system-information'))->assertForbidden();
    $this->actingAs($superadmin)->get(route('superadmin.system-information'))
        ->assertOk()
        ->assertSeeInOrder(['Informasi Database', 'Informasi Server', 'Google Authentication'])
        ->assertSee(route('auth.google'))
        ->assertSee(route('auth.google.callback'))
        ->assertDontSee('never-render-this-client-secret');
});

it('protects the last active superadmin', function () {
    $superadmin = superadminUser();

    $this->actingAs($superadmin)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->delete(route('superadmin.users.destroy', $superadmin))
        ->assertSessionHasErrors('role');

    expect($superadmin->fresh())->not->toBeNull();
});

it('allows deactivation when another active superadmin remains and audits it', function () {
    $actor = superadminUser();
    $target = superadminUser(['email' => 'second@widyakarya.ac.id']);

    $this->actingAs($actor)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->delete(route('superadmin.users.destroy', $target))->assertRedirect();

    $this->assertSoftDeleted($target)
        ->assertDatabaseHas('audit_logs', ['action' => 'user.deactivated', 'target_id' => $target->id]);
});

it('validates and audits minimal branding settings', function () {
    Storage::fake('public');
    $actor = superadminUser();

    $this->actingAs($actor)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->put(route('superadmin.settings.update'), [
            'application_name' => 'TA Cloud UKWK',
            'logo' => UploadedFile::fake()->image('logo.png', 400, 400),
        ])->assertRedirect(route('superadmin.settings.edit'))
        ->assertSessionHas('status', 'Pengaturan berhasil diperbarui.');

    $this->assertDatabaseHas('application_settings', ['application_name' => 'TA Cloud UKWK'])
        ->assertDatabaseHas('audit_logs', ['action' => 'settings.updated']);
});

it('rejects svg branding assets', function () {
    Storage::fake('public');
    $actor = superadminUser();

    $this->actingAs($actor)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->put(route('superadmin.settings.update'), [
            'application_name' => 'TA Cloud UKWK',
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('logo');
});

it('reactivates an account without changing its role', function () {
    $actor = superadminUser();
    $target = User::factory()->dosen()->create();
    $target->delete();

    $this->actingAs($actor)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->post(route('superadmin.users.restore', $target->id))
        ->assertRedirect();

    expect($target->fresh()->role)->toBe('dosen');
    $this->assertDatabaseHas('audit_logs', ['action' => 'user.reactivated', 'target_id' => $target->id]);
});

it('renders saved branding and escapes its title', function () {
    $actor = superadminUser();

    $this->actingAs($actor)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->put(route('superadmin.settings.update'), ['application_name' => '<script>alert(1)</script>'])
        ->assertRedirect();

    $this->get(route('superadmin.dashboard'))
        ->assertOk()
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>', false);
});

it('redirects stale sensitive requests and retains only allow-listed role data', function () {
    $actor = superadminUser();
    $target = User::factory()->dosen()->create();

    $this->actingAs($actor)
        ->put(route('superadmin.users.update', $target), ['role' => 'kaprodi', 'unsafe' => 'discard-me'])
        ->assertRedirect(route('superadmin.reauth.redirect'))
        ->assertSessionHas('superadmin_reauth_pending', fn (array $pending) => $pending['route'] === 'superadmin.users.update'
            && $pending['method'] === 'PUT'
            && $pending['input'] === ['role' => 'kaprodi']);

    expect($target->fresh()->role)->toBe('dosen');
});

it('rejects a different google identity during reauthentication', function () {
    config()->set('services.google.redirect', 'https://example.test/auth/google/callback');
    $actor = superadminUser(['google_id' => 'google-operator']);
    $google = new SocialiteUser;
    $google->map(['id' => 'google-attacker', 'email' => 'attacker@widyakarya.ac.id']);

    Socialite::shouldReceive('driver->redirectUrl->user')->once()->andReturn($google);

    $this->actingAs($actor)->get(route('superadmin.reauth.callback'))
        ->assertRedirect(route('superadmin.dashboard'))
        ->assertSessionHasErrors('reauth');

    $this->assertDatabaseHas('audit_logs', ['action' => 'superadmin.reauth_rejected']);
});

it('audits google provider failures and rejects non-application resume targets', function () {
    config()->set('services.google.redirect', 'https://example.test/auth/google/callback');
    $actor = superadminUser();

    Socialite::shouldReceive('driver->redirectUrl->user')->once()->andThrow(new RuntimeException('provider failed'));

    $this->actingAs($actor)->get(route('superadmin.reauth.callback'))
        ->assertRedirect(route('superadmin.dashboard'));
    $this->assertDatabaseHas('audit_logs', ['action' => 'superadmin.reauth_failed']);

    $this->withSession(['superadmin_reauth_pending' => [
        'route' => 'https://attacker.test',
        'parameters' => [],
        'method' => 'POST',
        'input' => [],
    ]])->get(route('superadmin.reauth.resume'))
        ->assertRedirect(route('superadmin.dashboard'))
        ->assertDontSee('attacker.test');
});

it('resumes a role update after same-user google reauthentication and keeps legacy role fields synchronized', function () {
    config()->set('services.google.redirect', 'https://example.test/auth/google/callback');
    $actor = superadminUser(['google_id' => 'google-operator']);
    $target = User::factory()->dosen()->create();
    $google = new SocialiteUser;
    $google->map(['id' => 'google-operator', 'email' => $actor->email]);

    Socialite::shouldReceive('driver->redirectUrl->user')->once()->andReturn($google);

    $this->actingAs($actor)
        ->withSession(['superadmin_reauth_pending' => [
            'route' => 'superadmin.users.update',
            'parameters' => ['user' => $target->id],
            'method' => 'PUT',
            'input' => ['role' => 'kaprodi'],
        ]])
        ->get(route('superadmin.reauth.callback'))
        ->assertRedirect(route('superadmin.reauth.resume'))
        ->assertSessionHas('superadmin_reauthenticated_at');

    $resume = $this->get(route('superadmin.reauth.resume'))
        ->assertOk()
        ->assertSee(route('superadmin.users.update', $target), false)
        ->assertSee('name="role" value="kaprodi"', false)
        ->assertDontSee('http://attacker.test', false);

    $this->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->put(route('superadmin.users.update', $target), ['role' => 'kaprodi'])
        ->assertRedirect(route('superadmin.users.index'))
        ->assertSessionHas('status', 'User berhasil diperbarui.');

    $target->refresh();
    expect($target->role)->toBe('kaprodi')
        ->and($target->level->users_level)->toBe('kaprodi')
        ->and($target->users_id)->toBe($target->level->users_id);
    $this->assertDatabaseHas('audit_logs', ['action' => 'superadmin.reauthenticated'])
        ->assertDatabaseHas('audit_logs', ['action' => 'user.role_changed', 'target_id' => $target->id]);
});

it('keeps every sensitive superadmin mutation behind fresh authentication', function () {
    foreach ([
        'superadmin.users.store',
        'superadmin.users.update',
        'superadmin.users.destroy',
        'superadmin.users.restore',
        'superadmin.settings.update',
    ] as $name) {
        expect(Route::getRoutes()->getByName($name)?->gatherMiddleware())->toContain('fresh.superadmin');
    }
});

it('renders the superadmin workspace navigation without audit payloads', function () {
    $actor = superadminUser();
    AuditLog::query()->create([
        'actor_id' => $actor->id,
        'actor_email' => $actor->email,
        'action' => 'settings.updated',
        'after' => ['secret' => 'never-render-this'],
    ]);
    AuditLog::query()->create(['action' => 'superadmin.bootstrapped']);

    $this->actingAs($actor)->get(route('superadmin.audit.index'))
        ->assertOk()
        ->assertSeeInOrder(['Dashboard', 'Users', 'Setting', 'Log Sistem'])
        ->assertSeeInOrder(['Waktu', 'Aktivitas', 'User'])
        ->assertDontSee('Pelaku')
        ->assertDontSee('Target')
        ->assertSee('Settings Updated')
        ->assertSee($actor->name)
        ->assertSee('Terminal')
        ->assertDontSee('never-render-this');
});
