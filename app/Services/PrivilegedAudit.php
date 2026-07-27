<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class PrivilegedAudit
{
    public static function record(string $action, ?User $target = null, array $before = [], array $after = [], ?Request $request = null): void
    {
        $actor = auth()->user();

        AuditLog::query()->create([
            'actor_id' => $actor?->id,
            'actor_email' => $actor?->email,
            'action' => $action,
            'target_type' => $target ? User::class : null,
            'target_id' => $target?->id,
            'before' => $before ?: null,
            'after' => $after ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }
}
