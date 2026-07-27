<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrivilegedAudit
{
    public const MAX_RECORDS = 500;

    public static function record(string $action, ?Model $target = null, array $before = [], array $after = [], ?Request $request = null, bool $markRequest = true): void
    {
        if (! Schema::hasTable('audit_logs') || ! Schema::hasTable('application_settings')) {
            return;
        }

        $actor = auth()->user();

        DB::transaction(function () use ($action, $target, $before, $after, $request, $actor): void {
            DB::table('application_settings')->lockForUpdate()->value('id');

            AuditLog::query()->create([
                'actor_id' => $actor?->id,
                'actor_email' => $actor?->email,
                'action' => $action,
                'target_type' => $target ? $target::class : null,
                'target_id' => $target?->getKey(),
                'before' => self::sanitize($before) ?: null,
                'after' => self::sanitize($after) ?: null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);

            $oldestRetainedId = AuditLog::query()
                ->latest('id')
                ->offset(self::MAX_RECORDS - 1)
                ->value('id');

            if ($oldestRetainedId) {
                AuditLog::query()->where('id', '<', $oldestRetainedId)->delete();
            }
        });

        if ($markRequest) {
            $request?->attributes->set('audit_recorded', true);
        }
    }

    public static function sanitize(array $metadata): array
    {
        return collect($metadata)
            ->reject(fn ($value, $key): bool => (bool) preg_match('/password|token|secret|content|payload|file|document/i', (string) $key))
            ->map(fn ($value) => is_array($value) ? self::sanitize($value) : $value)
            ->all();
    }
}
