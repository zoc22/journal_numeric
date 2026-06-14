<?php
// app-modules/User/Routes/api.php

/*
|--------------------------------------------------------------------------
| Routes API du module User
|--------------------------------------------------------------------------
|
| Toutes les routes sont préfixées par '/api' et protégées par
| le middleware 'auth:sanctum' et 'tenant'.
|
*/

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UserController;
use Modules\User\Http\Controllers\RoleController;
use Modules\User\Http\Controllers\PermissionController;

Route::middleware(['api', 'auth:sanctum', 'tenant'])->prefix('api')->group(function () {

    // ========== ROUTES PUBLIQUES (profil utilisateur) ==========
    Route::get('me', [UserController::class, 'me']);
    Route::put('profile', [UserController::class, 'updateProfile']);

    // ========== ROUTES ADMINISTRATION (permissions requises) ==========

    // Gestion des utilisateurs (permission: user.manage)
    Route::middleware(['permission:user.manage'])->group(function () {
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/assign-role', [UserController::class, 'assignRole']);
        Route::post('users/{user}/remove-role', [UserController::class, 'removeRole']);
        Route::post('users/{user}/activate', [UserController::class, 'activate']);
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate']);
    });

    // Gestion des rôles (permission: user.role.assign)
    Route::middleware(['permission:user.role.assign'])->group(function () {
        Route::get('roles', [RoleController::class, 'index']);
        Route::get('roles/{role}/permissions', [RoleController::class, 'permissions']);
    });

    // Gestion des permissions (permission: user.manage)
    Route::middleware(['permission:user.manage'])->group(function () {
        Route::get('permissions', [PermissionController::class, 'index']);
        Route::get('permissions/grouped', [PermissionController::class, 'grouped']);
    });
});
