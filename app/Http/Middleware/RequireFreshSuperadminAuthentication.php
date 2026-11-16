<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireFreshSuperadminAuthentication
{
    public const MAX_AGE_SECONDS = 600;

    private const SAFE_INPUTS = [
        'superadmin.users.store' => ['name', 'email', 'role'],
        'superadmin.users.update' => ['roles'],
        'superadmin.users.destroy' => [],
        'superadmin.users.restore' => [],
        'superadmin.users.permanently-destroy' => [],
        'superadmin.settings.update' => ['application_name'],
        'registrations.approve' => [],
        'registrations.reject' => ['rejection_reason'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role !== 'superadmin') {
            return $next($request);
        }

        $authenticatedAt = (int) $request->session()->get('superadmin_reauthenticated_at', 0);

        if ($authenticatedAt >= now()->subSeconds(self::MAX_AGE_SECONDS)->timestamp) {
            return $next($request);
        }

        $route = (string) $request->route()?->getName();
        abort_unless(array_key_exists($route, self::SAFE_INPUTS), 403);

        $request->session()->put('superadmin_reauth_pending', [
            'route' => $route,
            'parameters' => collect($request->route()?->parameters() ?? [])
                ->map(fn ($value) => $value instanceof Model ? $value->getRouteKey() : $value)
                ->all(),
            'method' => $request->method(),
            'input' => $request->only(self::SAFE_INPUTS[$route]),
        ]);

        return redirect()->route('superadmin.reauth.redirect');
    }
}
