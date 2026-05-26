<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends AuthenticatedSessionController
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $throwable) {
            return redirect()->route('login')->with('status', 'Google login gagal. Silakan coba lagi.');
        }

        $email = Str::lower((string) $googleUser->getEmail());
        $allowedDomain = Str::lower((string) config('services.google.allowed_domain'));

        if ($email === '' || ! Str::endsWith($email, '@' . $allowedDomain)) {
            return redirect()->route('login')->with('status', 'Login Google hanya untuk email @' . $allowedDomain . '.');
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            return redirect()->route('login')->with('status', 'Akun belum terdaftar di TACLOUD. Hubungi admin.');
        }

        if (method_exists($user, 'trashed') && $user->trashed()) {
            return redirect()->route('login')->with('status', 'Akun tidak aktif. Hubungi admin.');
        }

        $user->forceFill([
            'google_id' => $user->google_id ?: $googleUser->getId(),
            'google_avatar' => $googleUser->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(static::dashboardRouteForRole($user->role));
    }
}
