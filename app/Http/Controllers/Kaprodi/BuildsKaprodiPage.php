<?php

namespace App\Http\Controllers\Kaprodi;

use App\Services\RoleNavigationService;

trait BuildsKaprodiPage
{
    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function kaprodiPage(string $heading, string $crumbs, array $extra = []): array
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
}
