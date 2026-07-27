<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SuperadminAccounts
{
    public function updateRole(User $target, string $role): void
    {
        DB::transaction(function () use ($target, $role): void {
            $locked = User::query()->withTrashed()->lockForUpdate()->findOrFail($target->id);

            if ($locked->role === 'superadmin' && $role !== 'superadmin') {
                $this->protectLastSuperadmin($locked->id);
            }

            $locked->update(['role' => $role]);
        });
    }

    public function deactivate(User $target): void
    {
        DB::transaction(function () use ($target): void {
            $locked = User::query()->lockForUpdate()->findOrFail($target->id);

            if ($locked->role === 'superadmin') {
                $this->protectLastSuperadmin($locked->id);
            }

            DB::table('sessions')->where('user_id', $locked->id)->delete();
            $locked->forceFill(['remember_token' => null])->save();
            $locked->delete();
        });
    }

    public function reactivate(int $targetId): User
    {
        return DB::transaction(function () use ($targetId): User {
            $user = User::query()->withTrashed()->lockForUpdate()->findOrFail($targetId);

            if (! $user->trashed()) {
                throw ValidationException::withMessages(['user' => 'Account is already active.']);
            }

            $user->restore();

            return $user;
        });
    }

    private function protectLastSuperadmin(int $targetId): void
    {
        $otherExists = User::query()
            ->where('role', 'superadmin')
            ->whereKeyNot($targetId)
            ->lockForUpdate()
            ->exists();

        if (! $otherExists) {
            throw ValidationException::withMessages(['role' => 'Superadmin aktif terakhir tidak dapat dihapus atau diturunkan.']);
        }
    }
}
