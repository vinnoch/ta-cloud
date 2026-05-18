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
            $user?->loadMissing('studyProgram');
            $role = $user?->role ?? 'global';
            $navigation = app(RoleNavigationService::class);
            $navSubtitle = $user?->studyProgram?->name ?? ($role === 'global' ? 'Sistem Manajemen Tugas Akhir' : strtoupper($role) . ' Workspace');

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

            if (empty($data['navSubtitle'])) {
                $view->with('navSubtitle', $navSubtitle);
            }

            if (empty($data['roleCards'])) {
                $view->with('roleCards', app(CardPresenter::class)->forUser($user));
            }
        });
    }
}
