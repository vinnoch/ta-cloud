<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
    $this->actingAs($superadmin)->get(route('superadmin.dashboard'))->assertOk();
    $this->actingAs($superadmin)->get(route('kaprodi.dashboard'))->assertForbidden();
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
        ])->assertRedirect();

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

it('redirects sensitive operations to google reauthentication when freshness is missing', function () {
    $actor = superadminUser();

    $this->actingAs($actor)
        ->put(route('superadmin.settings.update'), ['application_name' => 'Blocked'])
        ->assertRedirect(route('superadmin.reauth.redirect'));

    $this->assertDatabaseMissing('application_settings', ['application_name' => 'Blocked']);
});

it('accepts only the same google identity for reauthentication', function () {
    $actor = superadminUser(['google_id' => 'google-operator']);
    $google = new SocialiteUser;
    $google->map(['id' => 'google-attacker', 'email' => 'attacker@widyakarya.ac.id', 'name' => 'Attacker']);

    Socialite::shouldReceive('driver->redirectUrl->user')->once()->andReturn($google);

    $this->actingAs($actor)->get(route('superadmin.reauth.callback'))
        ->assertRedirect(route('superadmin.dashboard'))
        ->assertSessionHasErrors('reauth');

    $this->assertDatabaseHas('audit_logs', ['action' => 'superadmin.reauth_rejected']);
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
