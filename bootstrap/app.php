<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/');
        $middleware->alias([
            'role_redirect'=> \App\Http\Middleware\RoleMiddleware::class,
            'access_control'=> \App\Http\Middleware\AccessMiddleware::class,
            'two_factor'=> \App\Http\Middleware\EnsureTwoFactorAuthenticated::class,
            'rate.limit' => \App\Http\Middleware\RateLimitMiddleware::class,
            'etag' => \App\Http\Middleware\ETagMiddleware::class,
            'transform.response' => \App\Http\Middleware\TransformResponse::class,
            'filter.fields' => \App\Http\Middleware\FilterFields::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
