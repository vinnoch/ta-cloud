<?php

namespace App\Http\Middleware;

use App\Services\PrivilegedAudit;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditMutations
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $request->user()
            && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)
            && $response->getStatusCode() < 400
            && ! $request->attributes->get('audit_recorded', false)
        ) {
            $target = collect($request->route()?->parameters() ?? [])->first(fn ($value) => $value instanceof Model);

            PrivilegedAudit::record(
                (string) ($request->route()?->getName() ?: 'unnamed.mutation'),
                $target instanceof Model ? $target : null,
                after: ['method' => $request->method(), 'status' => $response->getStatusCode()],
                request: $request,
            );
        }

        return $response;
    }
}
