<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Security\Http\Controllers\AdminAuditController;
use Modules\Security\Http\Controllers\AuditLogController;
use Modules\Security\Http\Controllers\SecurityController;

/*
|--------------------------------------------------------------------------
| Routes API du module Security
|--------------------------------------------------------------------------
*/

// Routes de sécurité pour l'utilisateur connecté
Route::middleware(['auth:sanctum', 'tenant'])->prefix('security')->group(function () {
    Route::get('/login-history', [SecurityController::class, 'loginHistory']);
    Route::get('/active-sessions', [SecurityController::class, 'activeSessions']);
    Route::post('/terminate-other-sessions', [SecurityController::class, 'terminateOtherSessions']);
    Route::delete('/session/{sessionId}', [SecurityController::class, 'terminateSession']);
    Route::get('/stats', [SecurityController::class, 'stats']);
});

// Routes d'audit
Route::middleware(['auth:sanctum', 'tenant'])->prefix('audit')->group(function () {
    Route::get('/logs', [AuditLogController::class, 'index']);
    Route::get('/logs/{auditLog}', [AuditLogController::class, 'show']);
    Route::get('/statistiques', [AuditLogController::class, 'statistiques']);
});

// Routes d'administration (Super Admin uniquement)
Route::middleware(['auth:sanctum', 'permission:admin.audit'])->prefix('admin/audit')->group(function () {
    Route::get('/logs', [AdminAuditController::class, 'index']);
    Route::get('/critical', [AdminAuditController::class, 'critical']);
    Route::delete('/clean', [AdminAuditController::class, 'clean']);
});
