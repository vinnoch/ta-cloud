<?php

use App\Http\Controllers\Superadmin\AuditLogController;
use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\ReauthenticationController;
use App\Http\Controllers\Superadmin\SettingController;
use App\Http\Controllers\Superadmin\SystemInformationController;
use App\Http\Controllers\Superadmin\UserController;
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
    Route::delete('/users/{user}/permanent', [UserController::class, 'permanentlyDestroy'])->middleware('fresh.superadmin')->name('users.permanently-destroy');
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->middleware('fresh.superadmin')->name('settings.update');
    Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('/audit/settings', [AuditLogController::class, 'settings'])->name('audit.settings');
    Route::put('/audit/retention', [AuditLogController::class, 'updateRetention'])->middleware('fresh.superadmin')->name('audit.retention.update');
    Route::delete('/audit', [AuditLogController::class, 'destroy'])->middleware('fresh.superadmin')->name('audit.destroy');
});
