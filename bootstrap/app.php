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
    ->withMiddleware(function (Middleware $middleware) {
        // Laravel 11 has built-in CORS support, no need for fruitcake package
        // Just configure CORS in config/cors.php
        
        // Add your custom middleware here
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'account.setup' => \App\Http\Middleware\EnsureAccountSetup::class,
            'api.logging' => \App\Http\Middleware\ApiLogging::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();