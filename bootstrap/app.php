<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\AuditMutations;
use App\Http\Middleware\RequireFreshSuperadminAuthentication;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(AddSecurityHeaders::class);
        $middleware->append(AuditMutations::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'fresh.superadmin' => RequireFreshSuperadminAuthentication::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
