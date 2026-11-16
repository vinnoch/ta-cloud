<?php

namespace App\Console\Commands;

use App\Services\DemoMode;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('demo:disable')]
#[Description('Hide demo login options and deactivate demo accounts')]
class DisableDemoMode extends Command
{
    public function handle(DemoMode $demoMode): int
    {
        try {
            $accountCount = $demoMode->disable();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Demo mode disabled. {$accountCount} demo accounts deactivated.");

        return self::SUCCESS;
    }
}
