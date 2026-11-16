<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\DemoMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(DemoMode $demoMode): View
    {
        return view('auth.login', [
            'title' => 'Masuk TA Cloud',
            'demoRoles' => $demoMode->enabled() ? array_keys(DemoMode::ACCOUNTS) : [],
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function demo(Request $request, string $role, DemoMode $demoMode): RedirectResponse
    {
        $user = $demoMode->account($role);
        abort_unless($user, 404);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return redirect()->to(static::dashboardRouteForRole($user->role));
    }

    public static function dashboardRouteForRole(string $role): string
    {
        return match ($role) {
            'superadmin' => route('superadmin.dashboard'),
            'admin' => route('admin.dashboard'),
            'mahasiswa' => route('mahasiswa.skripsi.index'),
            'dosen' => route('dosen.dashboard'),
            'kaprodi' => route('kaprodi.dashboard'),
            default => route('dashboard.index'),
        };
    }
}
