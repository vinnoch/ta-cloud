<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SuperadminAccounts
{
    /** @param array<int, string> $roles */
    public function updateRoles(User $target, array $roles): void
    {
        DB::transaction(function () use ($target, $roles): void {
            $locked = User::query()->withTrashed()->lockForUpdate()->findOrFail($target->id);

            if ($locked->hasAssignedRole('superadmin') && ! in_array('superadmin', $roles, true)) {
                $this->protectLastSuperadmin($locked->id);
            }

            $levels = DB::table('users_level')->whereIn('users_level', $roles)->pluck('users_id', 'users_level');
            $locked->update(['role' => $roles[0]]);
            $locked->assignedRoles()->sync($levels->values()->all());
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

    public function permanentlyDelete(int $targetId): User
    {
        return DB::transaction(function () use ($targetId): User {
            $user = User::query()->withTrashed()->lockForUpdate()->findOrFail($targetId);

            if (! $user->trashed()) {
                throw ValidationException::withMessages(['user' => 'Akun aktif harus dinonaktifkan terlebih dahulu.']);
            }

            foreach ([
                ['tugas_akhirs', 'student_id'],
                ['reviewer_assignments', 'lecturer_id'],
                ['grades', 'reviewer_id'],
                ['bimbingans', 'reviewer_id'],
                ['document_versions', 'uploaded_by'],
                ['sidang_requests', 'lecturer_id'],
                ['sidang_requests', 'approved_by'],
            ] as [$table, $column]) {
                if (DB::table($table)->where($column, $user->id)->exists()) {
                    throw ValidationException::withMessages(['user' => 'Akun tidak dapat dihapus permanen karena masih memiliki data akademik terkait.']);
                }
            }

            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $user->id)
                ->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->forceDelete();

            return $user;
        });
    }

    private function protectLastSuperadmin(int $targetId): void
    {
        $otherExists = User::query()
            ->forRole('superadmin')
            ->whereKeyNot($targetId)
            ->lockForUpdate()
            ->exists();

        if (! $otherExists) {
            throw ValidationException::withMessages(['role' => 'Superadmin aktif terakhir tidak dapat dihapus atau diturunkan.']);
        }
    }
}
