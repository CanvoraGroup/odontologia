<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active' => App\Http\Middleware\EnsureActiveUser::class,
            'internal' => App\Http\Middleware\EnsureInternalUser::class,
            'administrator' => App\Http\Middleware\EnsureAdministrator::class,
            'permission' => App\Http\Middleware\EnsurePermission::class,
            'platform' => App\Http\Middleware\EnsurePlatformAdministrator::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
