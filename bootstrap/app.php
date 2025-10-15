<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',                 // load API routes
        commands: __DIR__.'/../routes/console.php',        // load console commands & schedule
        channels: __DIR__.'/../routes/channels.php',       // ok to keep even if unused
        health: '/up',                                     // health endpoint
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Route middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'role'  => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
