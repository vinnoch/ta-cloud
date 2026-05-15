<?php

namespace App\Providers;

use App\Services\CardPresenter;
use App\Services\RoleNavigationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $data = $view->getData();
            $user = Auth::user();
            $role = $user?->role ?? 'global';
            $navigation = app(RoleNavigationService::class);

            if (empty($data['navItems'])) {
                $footerItems = $navigation->footerItems();

                $navItems = match ($role) {
                    'mahasiswa' => $navigation->mahasiswaNavItems($user?->id),
                    'dosen' => $navigation->dosenNavItems(),
                    'kaprodi' => $navigation->kaprodiNavItems(),
                    default => [
                        ['label' => 'Overview', 'href' => route('dashboard.index'), 'active' => 'dashboard.*', 'icon' => 'partials.icons.grid'],
                        ['label' => 'Library', 'href' => route('library.index'), 'active' => 'library.*', 'icon' => 'partials.icons.folder'],
                    ],
                };

                $view->with([
                    'navRole' => $role,
                    'navItems' => $navItems,
                    'navFooterItems' => $footerItems,
                    'primaryCta' => null,
                ]);
            }

            if (empty($data['roleCards'])) {
                $view->with('roleCards', app(CardPresenter::class)->forUser($user));
            }
        });
    }
}
