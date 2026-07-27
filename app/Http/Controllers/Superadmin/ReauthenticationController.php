<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\PrivilegedAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class ReauthenticationController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->redirectUrl(route('superadmin.reauth.callback'))
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(route('superadmin.reauth.callback'))
                ->user();
        } catch (\Throwable) {
            PrivilegedAudit::record('superadmin.reauth_failed', request: $request);

            return redirect()->route('superadmin.dashboard')->withErrors(['reauth' => 'Google re-authentication failed.']);
        }

        $user = $request->user();
        $emailMatches = hash_equals(Str::lower((string) $user->email), Str::lower((string) $googleUser->getEmail()));
        $idMatches = ! $user->google_id || hash_equals((string) $user->google_id, (string) $googleUser->getId());

        if (! $emailMatches || ! $idMatches) {
            PrivilegedAudit::record('superadmin.reauth_rejected', $user, [], ['reason' => 'identity_mismatch'], $request);

            return redirect()->route('superadmin.dashboard')->withErrors(['reauth' => 'Use the same institutional Google account.']);
        }

        $request->session()->put('superadmin_reauthenticated_at', now()->timestamp);
        PrivilegedAudit::record('superadmin.reauthenticated', $user, request: $request);

        $target = (string) $request->session()->pull('superadmin_reauth_return', '');
        $appHost = parse_url(URL::to('/'), PHP_URL_HOST);
        $targetHost = parse_url($target, PHP_URL_HOST);

        if ($target === '' || ($targetHost !== null && $targetHost !== $appHost)) {
            $target = route('superadmin.dashboard');
        }

        return redirect()->to($target);
    }
}
