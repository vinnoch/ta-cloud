<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\PrivilegedAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;

class ReauthenticationController extends Controller
{
    private const RESUMABLE_ROUTES = [
        'superadmin.users.store',
        'superadmin.users.update',
        'superadmin.users.destroy',
        'superadmin.users.restore',
        'superadmin.settings.update',
    ];

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->redirectUrl($this->callbackUrl())
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->redirectUrl($this->callbackUrl())->user();
        } catch (\Throwable) {
            PrivilegedAudit::record('superadmin.reauth_failed', request: $request);

            return redirect()->route('superadmin.dashboard')->withErrors(['reauth' => 'Google re-authentication failed.']);
        }

        $user = $request->user();
        $emailMatches = hash_equals(strtolower((string) $user->email), strtolower((string) $googleUser->getEmail()));
        $idMatches = ! $user->google_id || hash_equals((string) $user->google_id, (string) $googleUser->getId());

        if (! $emailMatches || ! $idMatches) {
            $request->session()->forget('superadmin_reauth_pending');
            PrivilegedAudit::record('superadmin.reauth_rejected', $user, [], ['reason' => 'identity_mismatch'], $request);

            return redirect()->route('superadmin.dashboard')->withErrors(['reauth' => 'Use the same institutional Google account.']);
        }

        $request->session()->put('superadmin_reauthenticated_at', now()->timestamp);
        PrivilegedAudit::record('superadmin.reauthenticated', $user, request: $request);

        return $request->session()->has('superadmin_reauth_pending')
            ? redirect()->route('superadmin.reauth.resume')
            : redirect()->route('superadmin.dashboard');
    }

    public function resume(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->pull('superadmin_reauth_pending');

        if (! is_array($pending)
            || ! isset($pending['route'], $pending['method'], $pending['parameters'], $pending['input'])
            || ! in_array($pending['route'], self::RESUMABLE_ROUTES, true)) {
            return redirect()->route('superadmin.dashboard');
        }

        return view('superadmin.reauth-resume', [
            'action' => route($pending['route'], $pending['parameters']),
            'method' => $pending['method'],
            'input' => $pending['input'],
        ]);
    }

    private function callbackUrl(): string
    {
        $configured = (string) config('services.google.redirect');
        $scheme = parse_url($configured, PHP_URL_SCHEME);
        $host = parse_url($configured, PHP_URL_HOST);
        $port = parse_url($configured, PHP_URL_PORT);
        abort_unless($scheme && $host, 500, 'Google redirect URI is not configured.');

        return $scheme.'://'.$host.($port ? ':'.$port : '').route('superadmin.reauth.callback', absolute: false);
    }
}
