<?php

namespace App\Services;

use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class DemoMode
{
    public const ACCOUNTS = [
        'superadmin' => ['name' => 'Demo Superadmin', 'email' => 'superadmin@demo.tacloud.test'],
        'kaprodi' => ['name' => 'Demo Kaprodi', 'email' => 'kaprodi@demo.tacloud.test'],
        'dosen' => ['name' => 'Demo Dosen', 'email' => 'dosen@demo.tacloud.test'],
        'mahasiswa' => ['name' => 'Demo Mahasiswa', 'email' => 'mahasiswa@demo.tacloud.test'],
    ];

    public function enabled(): bool
    {
        return app()->environment(['local', 'testing']) && File::exists($this->markerPath());
    }

    public function enable(): void
    {
        $this->ensureAllowedEnvironment();

        DB::transaction(function (): void {
            $studyProgramId = StudyProgram::query()->value('id') ?? $this->createDemoStudyProgram();

            foreach (self::ACCOUNTS as $role => $fixture) {
                $user = User::query()->withTrashed()->firstOrNew(['email' => $fixture['email']]);
                $user->forceFill([
                    'name' => $fixture['name'],
                    'role' => $role,
                    'study_program_id' => $role === 'superadmin' ? null : $studyProgramId,
                    'nim' => $role === 'mahasiswa' ? 'DEMO0001' : null,
                    'nidn_nip' => in_array($role, ['kaprodi', 'dosen'], true) ? 'DEMO-'.$role : null,
                    'email_verified_at' => now(),
                    'password' => Str::random(64),
                ])->save();

                if ($user->trashed()) {
                    $user->restore();
                }
            }
        });

        File::ensureDirectoryExists(dirname($this->markerPath()));

        if (File::put($this->markerPath(), now()->toIso8601String()) === false) {
            throw new RuntimeException('Demo mode marker could not be written.');
        }
    }

    public function disable(): int
    {
        $this->ensureAllowedEnvironment();
        File::delete($this->markerPath());

        return DB::transaction(function (): int {
            $users = User::query()
                ->withTrashed()
                ->whereIn('email', collect(self::ACCOUNTS)->pluck('email'))
                ->lockForUpdate()
                ->get();

            DB::table('sessions')->whereIn('user_id', $users->modelKeys())->delete();

            foreach ($users as $user) {
                $user->forceFill(['remember_token' => null])->saveQuietly();

                if (! $user->trashed()) {
                    $user->delete();
                }
            }

            return $users->count();
        });
    }

    private function createDemoStudyProgram(): int
    {
        $departmentId = DB::table('departments')->insertGetId([
            'code' => 'DEMO',
            'name' => 'Demo Faculty',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return StudyProgram::query()->create([
            'department_id' => $departmentId,
            'code' => 'DEMO',
            'name' => 'Demo Study Program',
            'degree_level' => 'S1',
            'is_active' => true,
        ])->getKey();
    }

    public function account(string $role): ?User
    {
        $fixture = self::ACCOUNTS[$role] ?? null;

        if (! $this->enabled() || ! $fixture) {
            return null;
        }

        return User::query()->where('email', $fixture['email'])->first();
    }

    private function markerPath(): string
    {
        return storage_path('app/demo-mode.enabled');
    }

    private function ensureAllowedEnvironment(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo mode is only available in local or testing environments.');
        }
    }
}
