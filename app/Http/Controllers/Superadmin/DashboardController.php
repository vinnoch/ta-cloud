<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('superadmin.dashboard', [
            'title' => 'Superadmin',
            'userCount' => User::query()->count(),
            'auditCount' => AuditLog::query()->count(),
            'databaseHealthy' => DB::select('select 1') !== [],
        ]);
    }
}
