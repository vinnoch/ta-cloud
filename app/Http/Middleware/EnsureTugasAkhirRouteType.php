<?php

namespace App\Http\Middleware;

use App\Models\TugasAkhir;
use App\Services\TugasAkhirRouteService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTugasAkhirRouteType
{
    public function __construct(private readonly TugasAkhirRouteService $routes) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tugasAkhir = $request->route('skripsi');

        if ($tugasAkhir instanceof TugasAkhir) {
            $this->routes->ensureMatches($request, $tugasAkhir);
        }

        return $next($request);
    }
}
