<?php

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
        Route::get('/', function () {
            return 'Tenant ID: ' . tenant('id');
        });

        Route::get('/tenant', function () {
            return response()->json([
                'tenant' => tenant('id'),
                'message' => 'Tenant fonctionnel'
            ]);
        });
    });
