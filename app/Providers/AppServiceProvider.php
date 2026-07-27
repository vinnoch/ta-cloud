<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Services\ApplicationBranding;
use App\Services\CardPresenter;
use App\Services\PrivilegedAudit;
use App\Services\RoleNavigationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
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
        foreach (['created', 'updated', 'deleted', 'restored'] as $event) {
            Event::listen("eloquent.{$event}: *", function (string $eventName, array $models) use ($event): void {
                $model = $models[0] ?? null;

                if (! Auth::check() || ! $model instanceof Model || $model instanceof AuditLog) {
                    return;
                }

                PrivilegedAudit::record(
                    "model.{$event}",
                    $model,
                    before: $event === 'deleted' ? $model->getOriginal() : [],
                    after: in_array($event, ['created', 'updated', 'restored'], true) ? $model->getChanges() : [],
                    request: request(),
                    markRequest: false,
                );
            });
        }

        View::composer('*', function ($view): void {
            $data = $view->getData();
            $user = Auth::user();
            $user?->loadMissing('studyProgram');
            $role = $user?->role ?? 'global';
            $navigation = app(RoleNavigationService::class);
            $branding = app(ApplicationBranding::class)->get();
            $navSubtitle = $user?->studyProgram?->name ?? ($role === 'global' ? 'Sistem Manajemen Tugas Akhir' : strtoupper($role).' Workspace');

            if (empty($data['navItems'])) {
                $footerItems = $navigation->footerItems();

                $navItems = match ($role) {
                    'mahasiswa' => $navigation->mahasiswaNavItems($user?->id),
                    'dosen' => $navigation->dosenNavItems(),
                    'kaprodi' => $navigation->kaprodiNavItems(),
                    default => [
                        ['label' => 'Overview', 'href' => route('dashboard.index'), 'active' => 'dashboard.*', 'icon' => 'partials.icons.dashboard-monitor'],
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

            $view->with('branding', $branding);
        });
    }
}
