<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditLogMaintenance
{
    public const DEFAULT_RETENTION_DAYS = 90;

    private const DELETE_BATCH_SIZE = 500;

    public function retentionDays(): int
    {
        if (! Schema::hasTable('application_settings') || ! Schema::hasColumn('application_settings', 'log_retention_days')) {
            return self::DEFAULT_RETENTION_DAYS;
        }

        return $this->normalizeRetentionDays(
            DB::table('application_settings')->value('log_retention_days'),
        );
    }

    public function updateRetention(int $days, Request $request): int
    {
        $days = $this->normalizeRetentionDays($days);

        $deleted = DB::transaction(function () use ($days, $request): int {
            $settings = DB::table('application_settings')->lockForUpdate()->first();
            $previous = $this->normalizeRetentionDays($settings?->log_retention_days);

            DB::table('application_settings')->where('id', $settings->id)->update([
                'log_retention_days' => $days,
                'updated_at' => now(),
            ]);

            $deleted = $this->purgeExpiredLocked($days);

            $this->createMarker('audit.retention_updated', $request, [
                'previous_days' => $previous,
                'retention_days' => $days,
                'deleted_count' => $deleted,
            ]);

            return $deleted;
        });

        $request->attributes->set('audit_recorded', true);

        return $deleted;
    }

    public function purgeAll(Request $request): int
    {
        $deleted = DB::transaction(function () use ($request): int {
            DB::table('application_settings')->lockForUpdate()->value('id');
            $deleted = $this->deleteInBatches(AuditLog::query());

            $this->createMarker('audit.purged', $request, [
                'deleted_count' => $deleted,
                'reason' => 'manual',
            ]);

            return $deleted;
        });

        $request->attributes->set('audit_recorded', true);

        return $deleted;
    }

    public function purgeExpiredLocked(int $days): int
    {
        $cutoff = now()->subDays($this->normalizeRetentionDays($days));

        return $this->deleteInBatches(
            AuditLog::query()->where('created_at', '<', $cutoff),
        );
    }

    private function createMarker(string $action, Request $request, array $after): void
    {
        AuditLog::query()->create([
            'actor_id' => $request->user()?->getKey(),
            'actor_email' => $request->user()?->email,
            'action' => $action,
            'after' => PrivilegedAudit::sanitize($after),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private function deleteInBatches($query): int
    {
        $deleted = 0;

        do {
            $ids = (clone $query)->oldest('id')->limit(self::DELETE_BATCH_SIZE)->pluck('id');
            $batchDeleted = $ids->isEmpty() ? 0 : AuditLog::query()->whereKey($ids)->delete();
            $deleted += $batchDeleted;
        } while ($batchDeleted === self::DELETE_BATCH_SIZE);

        return $deleted;
    }

    private function normalizeRetentionDays(mixed $days): int
    {
        $days = (int) $days;

        return $days >= 1 && $days <= 65535 ? $days : self::DEFAULT_RETENTION_DAYS;
    }
}
