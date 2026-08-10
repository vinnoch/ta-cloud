<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
    $inactive = User::factory()->dosen()->create();
    $inactive->delete();
    $superadmin->forceFill(['last_login_at' => now()])->saveQuietly();

    $this->actingAs($superadmin)->get(route('superadmin.users.index'))
        ->assertOk()
        ->assertSeeInOrder(['Akun', 'Peran', 'Status', 'Login Terakhir'])
        ->assertDontSee('<span>Tindakan</span>', false)
        ->assertSee('data-account-edit-open', false)
        ->assertSee('data-account-edit-modal', false)
        ->assertSee(route('superadmin.users.permanently-destroy', $inactive->id), false)
        ->assertDontSee(route('superadmin.users.permanently-destroy', $superadmin->id), false)
        ->assertSee('Hapus Permanen')
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

    $this->actingAs($superadmin)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->delete(route('superadmin.users.permanently-destroy', $superadmin->id))
        ->assertSessionHasErrors('user');

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

it('permanently deletes only an unused inactive account and clears disposable records', function () {
    $actor = superadminUser();
    $target = User::factory()->dosen()->create();
    $target->delete();

    DB::table('sessions')->insert([
        'id' => 'target-session',
        'user_id' => $target->id,
        'payload' => 'unused',
        'last_activity' => now()->timestamp,
    ]);
    DB::table('notifications')->insert([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'notifiable_type' => User::class,
        'notifiable_id' => $target->id,
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($actor)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->delete(route('superadmin.users.permanently-destroy', $target->id))
        ->assertRedirect(route('superadmin.users.index'))
        ->assertSessionHas('status', 'Akun berhasil dihapus permanen.');

    $this->assertDatabaseMissing('users', ['id' => $target->id])
        ->assertDatabaseMissing('sessions', ['user_id' => $target->id])
        ->assertDatabaseMissing('notifications', ['notifiable_type' => User::class, 'notifiable_id' => $target->id])
        ->assertDatabaseHas('audit_logs', [
            'action' => 'user.permanently_deleted',
            'target_id' => $target->id,
        ]);
});

it('rejects permanent deletion of an active account', function () {
    $actor = superadminUser();
    $target = User::factory()->dosen()->create();

    $this->actingAs($actor)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->delete(route('superadmin.users.permanently-destroy', $target->id))
        ->assertRedirect(route('superadmin.users.index'))
        ->assertSessionHasErrors('user');

    $this->assertDatabaseHas('users', ['id' => $target->id, 'deleted_at' => null]);
});

it('blocks permanent deletion for every academic user reference without deleting related data', function (string $table, string $column) {
    $actor = superadminUser();
    $target = User::factory()->dosen()->create();
    $student = User::factory()->mahasiswa()->create();
    $tahunAkademikId = DB::table('tahun_akademiks')->insertGetId([
        'tahun_awal' => 2026,
        'tahun_akhir' => 2027,
    ]);
    $periodeId = DB::table('periodes')->insertGetId([
        'tahun_akademik_id' => $tahunAkademikId,
        'kode_periode' => '2026-1',
        'semester' => 1,
        'tgl_mulai' => '2026-08-01',
        'tgl_selesai' => '2026-12-31',
    ]);
    $skripsiId = DB::table('skripsis')->insertGetId([
        'student_id' => $table === 'skripsis' ? $target->id : $student->id,
        'periode_id' => $periodeId,
        'title' => 'Deletion guard',
        'type' => 'skripsi',
        'current_phase' => 'proposal',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    match ([$table, $column]) {
        ['skripsis', 'student_id'] => null,
        ['reviewer_assignments', 'lecturer_id'] => DB::table($table)->insert([
            'skripsi_id' => $skripsiId, $column => $target->id, 'role_type' => 'pembimbing_1',
        ]),
        ['grades', 'reviewer_id'] => DB::table($table)->insert([
            'skripsi_id' => $skripsiId,
            'format_penilaian_id' => DB::table('format_penilaians')->insertGetId(['nama' => 'Guard']),
            $column => $target->id,
            'role_type' => 'penguji_1',
            'grade_event' => 'sidang_skripsi',
        ]),
        ['bimbingans', 'reviewer_id'] => DB::table($table)->insert([
            'skripsi_id' => $skripsiId,
            $column => $target->id,
            'phase' => 'proposal',
            'meeting_date' => now()->toDateString(),
        ]),
        ['document_versions', 'uploaded_by'] => DB::table($table)->insert([
            'skripsi_id' => $skripsiId,
            'phase' => 'proposal',
            'version_number' => 1,
            'file_path' => 'guard.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1,
            $column => $target->id,
        ]),
        ['sidang_requests', 'lecturer_id'] => DB::table($table)->insert([
            'skripsi_id' => $skripsiId,
            $column => $target->id,
            'role_type' => 'pembimbing_1',
        ]),
        ['sidang_requests', 'approved_by'] => DB::table($table)->insert([
            'skripsi_id' => $skripsiId,
            'lecturer_id' => User::factory()->dosen()->create()->id,
            'approved_by' => $target->id,
            'role_type' => 'pembimbing_1',
        ]),
    };
    $target->delete();

    $this->actingAs($actor)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->delete(route('superadmin.users.permanently-destroy', $target->id))
        ->assertRedirect(route('superadmin.users.index'))
        ->assertSessionHasErrors('user');

    $this->assertSoftDeleted($target);
    expect(DB::table($table)->where($column, $target->id)->exists())->toBeTrue();
})->with([
    ['skripsis', 'student_id'],
    ['reviewer_assignments', 'lecturer_id'],
    ['grades', 'reviewer_id'],
    ['bimbingans', 'reviewer_id'],
    ['document_versions', 'uploaded_by'],
    ['sidang_requests', 'lecturer_id'],
    ['sidang_requests', 'approved_by'],
]);

it('forbids non-superadmins from permanently deleting an account', function () {
    $target = User::factory()->dosen()->create();
    $target->delete();

    $this->delete(route('superadmin.users.permanently-destroy', $target->id))
        ->assertRedirect(route('login'));

    $this->actingAs(User::factory()->kaprodi()->create())
        ->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->delete(route('superadmin.users.permanently-destroy', $target->id))
        ->assertForbidden();

    $this->assertSoftDeleted($target);
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
        'superadmin.users.permanently-destroy',
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
