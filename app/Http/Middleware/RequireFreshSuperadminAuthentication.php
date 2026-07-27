<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireFreshSuperadminAuthentication
{
    public const MAX_AGE_SECONDS = 600;

    public function handle(Request $request, Closure $next): Response
    {
        $authenticatedAt = (int) $request->session()->get('superadmin_reauthenticated_at', 0);

        if ($authenticatedAt < now()->subSeconds(self::MAX_AGE_SECONDS)->timestamp) {
            $request->session()->put('superadmin_reauth_return', url()->previous());

            return redirect()->route('superadmin.reauth.redirect');
        }

        return $next($request);
    }
}
