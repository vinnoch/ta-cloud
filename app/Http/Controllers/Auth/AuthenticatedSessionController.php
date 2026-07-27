<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'title' => 'Masuk TA Cloud',
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(static::dashboardRouteForRole($request->user()->role));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public static function dashboardRouteForRole(string $role): string
    {
        return match ($role) {
            'superadmin' => route('superadmin.dashboard'),
            'mahasiswa' => route('mahasiswa.skripsi.index'),
            'dosen' => route('dosen.dashboard'),
            'kaprodi' => route('kaprodi.dashboard'),
            default => route('dashboard.index'),
        };
    }
}
