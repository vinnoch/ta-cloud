<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\GoogleRegistrationController;
use App\Http\Controllers\RegistrationApprovalController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
    Route::get('auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::get('register', [GoogleRegistrationController::class, 'create'])->name('register');
    Route::post('register', [GoogleRegistrationController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('registration/status', [GoogleRegistrationController::class, 'status'])->name('register.status');
    Route::post('registration/cancel', [GoogleRegistrationController::class, 'cancel'])->name('register.cancel');

    Route::post('demo/login/{role}', [AuthenticatedSessionController::class, 'demo'])
        ->whereIn('role', ['superadmin', 'kaprodi', 'dosen', 'mahasiswa'])
        ->middleware('throttle:10,1')
        ->name('demo.login');
});

Route::prefix('registrations')->name('registrations.')->middleware(['auth', 'role:superadmin,admin,kaprodi'])->group(function (): void {
    Route::get('/', [RegistrationApprovalController::class, 'index'])->name('index');
    Route::patch('/{registration}/approve', [RegistrationApprovalController::class, 'approve'])->middleware('fresh.superadmin')->name('approve');
    Route::patch('/{registration}/reject', [RegistrationApprovalController::class, 'reject'])->middleware('fresh.superadmin')->name('reject');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('logout', fn () => redirect()->route('home'))
    ->name('logout.redirect');
