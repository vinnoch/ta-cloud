<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ApplicationBranding
{
    public const CACHE_KEY = 'application.branding';

    public function get(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $settings = Schema::hasTable('application_settings') ? ApplicationSetting::query()->first() : null;
            $logoPath = $settings?->logo_path;

            return [
                'name' => $settings?->application_name ?: 'TA Cloud UKWK',
                'logo_url' => $logoPath && Storage::disk('public')->exists($logoPath)
                    ? Storage::disk('public')->url($logoPath)
                    : asset('images/ukwk-logo.png'),
            ];
        });
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
