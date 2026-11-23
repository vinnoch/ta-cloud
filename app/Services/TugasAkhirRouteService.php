<?php

namespace App\Services;

use App\Models\TugasAkhir;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TugasAkhirRouteService
{
    public const SKRIPSI = 'skripsi';

    public const NON_SKRIPSI = 'non_skripsi';

    public function typeFromRequest(Request $request): string
    {
        return str_contains((string) $request->route()?->getName(), '.non-skripsi.')
            ? self::NON_SKRIPSI
            : self::SKRIPSI;
    }

    public function scope(Builder $query, Request|string $source): Builder
    {
        $type = $source instanceof Request ? $this->typeFromRequest($source) : $source;
        abort_unless(in_array($type, [self::SKRIPSI, self::NON_SKRIPSI], true), 404);

        return $query->where($query->qualifyColumn('type'), $type);
    }

    public function ensureMatches(Request $request, TugasAkhir $tugasAkhir): void
    {
        abort_unless($tugasAkhir->type === $this->typeFromRequest($request), 404);
    }

    public function routeName(string $role, TugasAkhir|string $source, string $action): string
    {
        $type = $source instanceof TugasAkhir ? $source->type : $source;

        if ($role === 'dosen' && $type === self::SKRIPSI && in_array($action, [
            'bimbingan.store',
            'sidang-request.store',
            'sidang-request.destroy',
        ], true)) {
            return "dosen.{$action}";
        }

        $namespace = $type === self::NON_SKRIPSI ? 'non-skripsi' : 'skripsi';

        return "{$role}.{$namespace}.{$action}";
    }

    public function route(string $role, TugasAkhir|string $source, string $action, mixed $parameters = [], bool $absolute = true): string
    {
        return route($this->routeName($role, $source, $action), $parameters, $absolute);
    }

    public function label(Request|string $source): string
    {
        $type = $source instanceof Request ? $this->typeFromRequest($source) : $source;

        return $type === self::NON_SKRIPSI ? 'Non-Skripsi' : 'Skripsi';
    }
}
