<?php

namespace App\Console\Commands;

use App\Services\DemoMode;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('demo:enable')]
#[Description('Create demo accounts and show demo role login options')]
class EnableDemoMode extends Command
{
    public function handle(DemoMode $demoMode): int
    {
        try {
            $demoMode->enable();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Demo mode enabled with multi-role and new-member scenarios.');

        return self::SUCCESS;
    }
}
