<?php

use App\Http\Controllers\Superadmin\DashboardController;
use App\Http\Controllers\Superadmin\ReauthenticationController;
use App\Http\Controllers\Superadmin\SettingController;
use App\Http\Controllers\Superadmin\UserController;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Route;

Route::prefix('superadmin')->name('superadmin.')->middleware(['auth', 'role:superadmin'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/reauth/google', [ReauthenticationController::class, 'redirect'])->name('reauth.redirect');
    Route::get('/reauth/google/callback', [ReauthenticationController::class, 'callback'])->name('reauth.callback');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->middleware('fresh.superadmin')->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('fresh.superadmin')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('fresh.superadmin')->name('users.destroy');
    Route::post('/users/{user}/restore', [UserController::class, 'restore'])->middleware('fresh.superadmin')->name('users.restore');
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SettingController::class, 'update'])->middleware('fresh.superadmin')->name('settings.update');
    Route::get('/audit', fn () => view('superadmin.audit', [
        'title' => 'Privileged Audit',
        'logs' => AuditLog::query()->latest()->paginate(50),
    ]))->name('audit.index');
});
