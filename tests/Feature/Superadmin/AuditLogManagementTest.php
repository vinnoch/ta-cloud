<?php

use App\Models\ApplicationSetting;
use App\Models\AuditLog;
use App\Models\Grade;
use App\Models\Skripsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function auditSuperadmin(): User
{
    return User::query()->create([
        'name' => 'Audit Operator',
        'email' => 'audit-operator@widyakarya.ac.id',
        'password' => 'unused-password',
        'role' => 'superadmin',
    ]);
}

it('updates retention and immediately removes expired logs', function () {
    $this->travelTo('2026-11-16 12:00:00');
    $actor = auditSuperadmin();
    AuditLog::query()->create(['action' => 'expired.entry', 'created_at' => now()->subDays(45)->subSecond()]);
    AuditLog::query()->create(['action' => 'retained.entry', 'created_at' => now()->subDays(44)]);

    $this->actingAs($actor)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->put(route('superadmin.audit.retention.update'), ['log_retention_days' => 45])
        ->assertRedirect(route('superadmin.audit.settings'))
        ->assertSessionHas('status', 'Penyimpanan log diperbarui. 1 log kedaluwarsa dihapus.');

    expect(ApplicationSetting::current()->log_retention_days)->toBe(45)
        ->and(AuditLog::query()->where('action', 'expired.entry')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'retained.entry')->exists())->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'action' => 'audit.retention_updated',
        'actor_id' => $actor->id,
    ]);

    expect(AuditLog::query()->where('action', 'audit.retention_updated')->firstOrFail()->after)
        ->toMatchArray(['previous_days' => 90, 'retention_days' => 45]);
});

it('accepts only valid retention days', function (mixed $days) {
    $actor = auditSuperadmin();

    $this->actingAs($actor)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->put(route('superadmin.audit.retention.update'), ['log_retention_days' => $days])
        ->assertSessionHasErrors('log_retention_days');

    expect(ApplicationSetting::current()->log_retention_days)->toBe(90);
})->with([0, 65536, null]);

it('deletes all existing logs and preserves one accountable purge marker', function () {
    $actor = auditSuperadmin();
    AuditLog::query()->insert(collect(range(1, 501))->map(fn (int $sequence): array => [
        'action' => "batch.entry.{$sequence}",
        'created_at' => now(),
        'updated_at' => now(),
    ])->all());

    $this->actingAs($actor)->withSession(['superadmin_reauthenticated_at' => now()->timestamp])
        ->delete(route('superadmin.audit.destroy'))
        ->assertRedirect(route('superadmin.audit.settings'))
        ->assertSessionHas('status', '501 log sistem dihapus. Catatan penghapusan disimpan.');

    expect(AuditLog::query()->count())->toBe(1)
        ->and(AuditLog::query()->firstOrFail()->action)->toBe('audit.purged')
        ->and(AuditLog::query()->firstOrFail()->actor_id)->toBe($actor->id)
        ->and(AuditLog::query()->firstOrFail()->after)->toBe([
            'deleted_count' => 501,
            'reason' => 'manual',
        ]);
});

it('protects retention changes and log deletion with fresh superadmin authentication', function () {
    $actor = auditSuperadmin();

    $this->actingAs($actor)
        ->put(route('superadmin.audit.retention.update'), ['log_retention_days' => 30])
        ->assertRedirect(route('superadmin.reauth.redirect'));

    $this->delete(route('superadmin.audit.destroy'))
        ->assertRedirect(route('superadmin.reauth.redirect'));

    expect(ApplicationSetting::current()->log_retention_days)->toBe(90);
});

it('resumes log deletion after fresh superadmin authentication', function () {
    $actor = auditSuperadmin();

    $this->actingAs($actor)->withSession(['superadmin_reauth_pending' => [
        'route' => 'superadmin.audit.destroy',
        'parameters' => [],
        'method' => 'DELETE',
        'input' => [],
    ]])->get(route('superadmin.reauth.resume'))
        ->assertOk()
        ->assertSee('action="'.route('superadmin.audit.destroy').'"', false)
        ->assertSee('name="_method" value="DELETE"', false);
});

it('renders readable audit activity details without management controls', function () {
    $actor = auditSuperadmin();
    AuditLog::query()->create([
        'actor_id' => $actor->id,
        'actor_email' => $actor->email,
        'action' => 'audit.purged',
        'after' => ['deleted_count' => 5, 'reason' => 'manual'],
        'ip_address' => '192.168.1.24',
    ]);

    $this->actingAs($actor)->get(route('superadmin.audit.index'))
        ->assertOk()
        ->assertSee('Riwayat aktivitas penting di dalam sistem.')
        ->assertSee('Pengaturan Log')
        ->assertSee('Cari aktivitas atau pengguna')
        ->assertSee('Semua Topik')
        ->assertSee('data-audit-filter-form', false)
        ->assertSee('data-audit-filter-reset hidden', false)
        ->assertSee('data-audit-results', false)
        ->assertDontSee('>Terapkan</button>', false)
        ->assertSee('<select name="topic"', false)
        ->assertDontSee('<select name="action"', false)
        ->assertSee('acss-topic-chip', false)
        ->assertDontSee('Log sistem dihapus')
        ->assertSee('Audit Operator menghapus 5 log sistem.')
        ->assertSee('Sumber')
        ->assertSee('data-audit-row-toggle', false)
        ->assertDontSee('Lihat detail')
        ->assertSee('192.168.1.24')
        ->assertDontSee('method="POST" action="'.route('superadmin.audit.retention.update').'"', false)
        ->assertDontSee('method="POST" action="'.route('superadmin.audit.destroy').'"', false);
});

it('serves filtered audit results to ajax requests', function () {
    $actor = auditSuperadmin();
    AuditLog::query()->create(['actor_id' => $actor->id, 'action' => 'demo.login']);
    AuditLog::query()->create(['actor_id' => $actor->id, 'action' => 'settings.updated']);

    $this->actingAs($actor)
        ->withHeader('X-Requested-With', 'XMLHttpRequest')
        ->get(route('superadmin.audit.index', ['topic' => 'Keamanan']))
        ->assertOk()
        ->assertSee('data-audit-results', false)
        ->assertSee('Demo Login')
        ->assertDontSee('memperbarui nama aplikasi atau logo');
});

it('filters audit logs by topic', function () {
    $actor = auditSuperadmin();
    AuditLog::query()->create(['actor_id' => $actor->id, 'action' => 'demo.login']);
    AuditLog::query()->create(['actor_id' => $actor->id, 'action' => 'settings.updated']);
    AuditLog::query()->create(['actor_id' => $actor->id, 'action' => 'model.created', 'target_type' => Skripsi::class, 'target_id' => 91]);
    AuditLog::query()->create(['actor_id' => $actor->id, 'action' => 'model.updated', 'target_type' => Grade::class, 'target_id' => 92]);

    $this->actingAs($actor)->get(route('superadmin.audit.index', ['topic' => 'Keamanan']))
        ->assertOk()
        ->assertSee('Demo Login')
        ->assertDontSee('memperbarui nama aplikasi atau logo')
        ->assertDontSee('Skripsi disimpan')
        ->assertDontSee('Penilaian diubah');

    $this->actingAs($actor)->get(route('superadmin.audit.index', ['topic' => 'Skripsi']))
        ->assertOk()
        ->assertSee('Skripsi disimpan')
        ->assertDontSee('Penilaian diubah');

    $this->actingAs($actor)->get(route('superadmin.audit.index', ['topic' => 'Penilaian']))
        ->assertOk()
        ->assertSee('Penilaian diubah')
        ->assertDontSee('Skripsi disimpan');
});

it('filters audit logs by actor text without loading a user dropdown', function () {
    $actor = auditSuperadmin();
    $other = User::factory()->dosen()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.test']);
    AuditLog::query()->create(['actor_id' => $actor->id, 'actor_email' => $actor->email, 'action' => 'actor.operator']);
    AuditLog::query()->create(['actor_id' => $other->id, 'actor_email' => $other->email, 'action' => 'actor.budi']);

    $this->actingAs($actor)->get(route('superadmin.audit.index', ['actor' => 'Budi']))
        ->assertOk()
        ->assertSee('Cari pengguna')
        ->assertSee('Actor Budi')
        ->assertDontSee('Audit Operator: Actor Operator.')
        ->assertDontSee('<select name="actor">', false);
});

it('provides log retention controls on a separate setting page', function () {
    $actor = auditSuperadmin();
    ApplicationSetting::current()->update(['log_retention_days' => 60]);

    $this->actingAs($actor)->get(route('superadmin.audit.settings'))
        ->assertOk()
        ->assertViewHas('retentionDays', 60)
        ->assertSee('Simpan Log untuk')
        ->assertSee('Simpan Log untuk (hari)')
        ->assertSee('type="number" name="log_retention_days"', false)
        ->assertDontSee('<select name="log_retention_days"', false)
        ->assertSee('Hapus Log Sistem')
        ->assertSee(route('superadmin.audit.retention.update'), false)
        ->assertSee(route('superadmin.audit.destroy'), false);
});
