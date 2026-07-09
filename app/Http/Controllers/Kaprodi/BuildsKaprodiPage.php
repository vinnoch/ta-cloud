<?php

namespace App\Http\Controllers\Kaprodi;

use App\Models\Skripsi;
use App\Services\RoleNavigationService;

trait BuildsKaprodiPage
{
    /**
    * @param  array<string, mixed>  $extra
    * @return array<string, mixed>
     */
    protected function kaprodiPage(string $heading, string|array $crumbs, array $extra = []): array
    {
        $navigation = app(RoleNavigationService::class);

        return array_merge([
            'title' => $heading,
            'heading' => $heading,
            'crumbs' => $crumbs,
            'navItems' => $navigation->kaprodiNavItems(),
            'primaryCta' => null,
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'kaprodi',
        ], $extra);
    }

    protected function skripsiBreadcrumbs(Skripsi $skripsi, string $contextLabel): array
    {
        $nim = (string) ($skripsi->student?->nim ?? '-');

        return match ($contextLabel) {
            'Proposal' => ['Kaprodi', 'Proposal', $nim],
            'Bimbingan' => ['Kaprodi', 'Histori Bimbingan', $nim],
            default => ['Kaprodi', 'Detail Skripsi', $nim],
        };
    }
}
