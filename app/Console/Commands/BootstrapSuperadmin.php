<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PrivilegedAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BootstrapSuperadmin extends Command
{
    protected $signature = 'superadmin:bootstrap {email} {--force : Skip interactive confirmation}';

    protected $description = 'Bootstrap the first superadmin using one exact institutional Google email';

    public function handle(): int
    {
        $email = Str::lower(trim((string) $this->argument('email')));
        $domain = Str::lower(trim((string) config('services.google.allowed_domain')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || $domain === '' || Str::afterLast($email, '@') !== $domain) {
            $this->error('Email must exactly match the configured institutional Google domain.');

            return self::FAILURE;
        }

        if (User::query()->where('role', 'superadmin')->exists()) {
            $this->error('An active superadmin already exists.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Grant superadmin to {$email}?")) {
            return self::FAILURE;
        }

        DB::transaction(function () use ($email): void {
            $user = User::query()->withTrashed()->whereRaw('LOWER(email) = ?', [$email])->lockForUpdate()->first();

            if ($user?->trashed()) {
                $user->restore();
            }

            $user ??= User::query()->create([
                'name' => Str::headline(Str::before($email, '@')),
                'email' => $email,
                'password' => Str::random(64),
                'role' => 'superadmin',
            ]);

            $user->update(['role' => 'superadmin']);
            PrivilegedAudit::record('superadmin.bootstrapped', $user, [], ['email' => $email, 'role' => 'superadmin']);
        });

        $this->info("Superadmin ready for Google sign-in: {$email}");

        return self::SUCCESS;
    }
}
