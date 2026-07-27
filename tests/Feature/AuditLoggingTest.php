<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Services\PrivilegedAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('captures named mutations and changed targets for every application role', function (string $role) {
    Route::middleware(['web', 'auth'])->put("/_audit-test/{$role}", function () {
        request()->user()->update(['name' => 'Audited Actor']);

        return response()->noContent();
    })->name("audit-test.{$role}");

    $actor = User::query()->create([
        'name' => ucfirst($role),
        'email' => "{$role}@example.test",
        'password' => 'password',
        'role' => $role,
    ]);

    $this->actingAs($actor)->put("/_audit-test/{$role}")->assertNoContent();

    $this->assertDatabaseHas('audit_logs', [
        'actor_id' => $actor->id,
        'action' => "audit-test.{$role}",
    ])->assertDatabaseHas('audit_logs', [
        'actor_id' => $actor->id,
        'action' => 'model.updated',
        'target_type' => User::class,
        'target_id' => $actor->id,
    ]);
})->with(['superadmin', 'admin', 'kaprodi', 'dosen', 'mahasiswa']);

it('removes sensitive metadata recursively', function () {
    $actor = User::factory()->kaprodi()->create();

    $this->actingAs($actor);
    PrivilegedAudit::record('security.metadata_test', $actor, after: [
        'status' => 'approved',
        'password' => 'never-store',
        'nested' => ['access_token' => 'never-store', 'note' => 'safe'],
        'document_path' => '/private/document.pdf',
    ]);

    $metadata = AuditLog::query()->where('action', 'security.metadata_test')->firstOrFail()->after;

    expect($metadata)->toBe(['status' => 'approved', 'nested' => ['note' => 'safe']]);
});

it('retains exactly the newest 500 records', function () {
    foreach (range(1, 505) as $sequence) {
        PrivilegedAudit::record("retention.{$sequence}");
    }

    expect(AuditLog::query()->count())->toBe(500)
        ->and(AuditLog::query()->oldest('id')->value('action'))->toBe('retention.6')
        ->and(AuditLog::query()->latest('id')->value('action'))->toBe('retention.505');
});
