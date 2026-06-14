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
        then: function () {
            if (str_contains(implode(' ', $_SERVER['argv'] ?? []), 'phpunit') || str_contains(implode(' ', $_SERVER['argv'] ?? []), 'artisan test')) {
                config(['database.default' => 'sqlite']);
                config(['app.env' => 'testing']);
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
        ]);
        $middleware->appendToGroup('web', [
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
        ]);
        $middleware->appendToGroup('api', [
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
