<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Recruitment\Http\Controllers\ApplicationController;
use Modules\Recruitment\Http\Controllers\CallForApplicationController;

/*
|--------------------------------------------------------------------------
| Routes API du module Recruitment
|--------------------------------------------------------------------------
*/

Route::middleware(['api'])->prefix('api/recruitment')->group(function () {
    // Routes publiques pour les appels à candidatures
    Route::prefix('calls')->group(function () {
        Route::get('/', [CallForApplicationController::class, 'index']);
        Route::get('/{call}', [CallForApplicationController::class, 'show']);

        // Routes protégées pour les appels
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/', [CallForApplicationController::class, 'store']);
            Route::put('/{call}', [CallForApplicationController::class, 'update']);
            Route::delete('/{call}', [CallForApplicationController::class, 'destroy']);
            Route::post('/{call}/publish', [CallForApplicationController::class, 'publish']);
            Route::post('/{call}/close', [CallForApplicationController::class, 'close']);
        });
    });

    // Routes pour les candidatures
    Route::middleware(['auth:sanctum'])->prefix('applications')->group(function () {
        Route::get('/', [ApplicationController::class, 'index']);
        Route::post('/call/{call}', [ApplicationController::class, 'store']);
        Route::get('/{application}', [ApplicationController::class, 'show']);
        Route::put('/{application}', [ApplicationController::class, 'update']);
        Route::post('/{application}/review', [ApplicationController::class, 'review']);
    });
});
