<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\RoleNavigationService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $navigation = app(RoleNavigationService::class);

        return view('superadmin.dashboard', [
            'title' => 'Superadmin',
            'heading' => 'Dashboard Superadmin',
            'crumbs' => 'SUPERADMIN • DASHBOARD',
            'navItems' => $navigation->superadminNavItems(),
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'superadmin',
            'primaryCta' => null,
            'userCount' => User::query()->count(),
            'activeUserCount' => User::query()->count(),
            'inactiveUserCount' => User::onlyTrashed()->count(),
            'auditCount' => AuditLog::query()->count(),
            'databaseHealthy' => DB::select('select 1') !== [],
            'recentLogs' => AuditLog::query()->with('actor')->latest()->limit(6)->get(),
        ]);
    }
}
