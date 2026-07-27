<?php

use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\ReauthenticationController;
use App\Http\Controllers\Superadmin\SettingController;
use App\Http\Controllers\Superadmin\SystemInformationController;
use App\Http\Controllers\Superadmin\UserController;
use App\Models\AuditLog;
use App\Services\RoleNavigationService;
use Illuminate\Support\Facades\Route;

Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'role:superadmin'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/system-information', SystemInformationController::class)->name('system-information');
    Route::get('/reauth/google', [ReauthenticationController::class, 'redirect'])->name('reauth.redirect');
    Route::get('/reauth/google/callback', [ReauthenticationController::class, 'callback'])->name('reauth.callback');
    Route::get('/reauth/resume', [ReauthenticationController::class, 'resume'])->name('reauth.resume');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('fresh.superadmin')->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('fresh.superadmin')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('fresh.superadmin')->name('users.destroy');
    Route::post('/users/{user}/restore', [UserController::class, 'restore'])->middleware('fresh.superadmin')->name('users.restore');
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->middleware('fresh.superadmin')->name('settings.update');
    Route::get('/audit', function () {
        $navigation = app(RoleNavigationService::class);

        return view('superadmin.audit', [
            'title' => 'Log Sistem',
            'heading' => 'Log Sistem',
            'crumbs' => 'SUPERADMIN • LOG SISTEM',
            'navItems' => $navigation->superadminNavItems(),
            'navFooterItems' => $navigation->footerItems(),
            'navRole' => 'superadmin',
            'primaryCta' => null,
            'logs' => AuditLog::query()->with('actor')->latest()->paginate(50),
        ]);
    })->name('audit.index');
});
