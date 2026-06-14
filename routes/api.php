<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TenantController;
use Modules\User\Http\Controllers\GoogleAuthController;

// Route pour créer un tenant (accessible sans auth pour le moment)
Route::post('/tenants', [TenantController::class, 'store']);

// Routes Google Auth - sans tenancy
Route::middleware('api')->prefix('auth')->group(function () {
    Route::get('google', [GoogleAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [GoogleAuthController::class, 'handleGoogleCallback']);
});

// Note: Les routes /api/login et /api/register sont dans le module Security
// Elles sont accessibles via le tenant uniquement
