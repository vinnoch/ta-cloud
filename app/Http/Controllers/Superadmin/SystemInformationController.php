<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\RoleNavigationService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PDO;
use Throwable;

class SystemInformationController extends Controller
{
    public function __invoke(): View
    {
        try {
            $connection = DB::connection();
            $connection->select('select 1');
            $database = [
                'status' => 'Terkoneksi',
                'driver' => $connection->getDriverName(),
                'name' => $connection->getDatabaseName(),
                'version' => (string) $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION),
            ];
        } catch (Throwable) {
            $database = ['status' => 'Gangguan', 'driver' => '-', 'name' => '-', 'version' => '-'];
        }

        $navigation = app(RoleNavigationService::class);

        return view('superadmin.system-information', [
            'title' => 'Database & Server',
            'heading' => 'Database & Server',
            'crumbs' => 'SUPERADMIN • DATABASE & SERVER',
            'navItems' => $navigation->superadminNavItems(),
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'superadmin',
            'primaryCta' => null,
            'database' => $database,
            'server' => [
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'environment' => app()->environment(),
                'timezone' => (string) config('app.timezone'),
            ],
            'google' => [
                'login' => route('auth.google'),
                'callback' => route('auth.google.callback'),
                'domain' => (string) config('services.google.allowed_domain'),
            ],
        ]);
    }
}
