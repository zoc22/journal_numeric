<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Maison\Http\Controllers\AdminMaisonController;
use Modules\Maison\Http\Controllers\MaisonController;
use Modules\Maison\Http\Controllers\MembreController;

/*
|--------------------------------------------------------------------------
| Routes du module Maison
|--------------------------------------------------------------------------
|
| Ces routes sont chargées dans le contexte du tenant (maison).
| L'utilisateur doit être authentifié via Sanctum.
|
*/

// =====================================================================
// Routes authentifiées
// =====================================================================
Route::middleware(['api', 'auth:sanctum'])->prefix('api/maison')->group(function () {

    // =================================================================
    // Routes pour le gestionnaire de la maison (Éditeur en Chef)
    // =================================================================
    Route::prefix('gerer')->group(function () {

        // Informations de la maison
        Route::get('/', [MaisonController::class, 'show'])->name('maison.show');
        Route::put('/', [MaisonController::class, 'update'])->name('maison.update');

        // Gestion des membres
        Route::get('/membres', [MembreController::class, 'index'])->name('maison.membres.index');
        Route::post('/membres', [MembreController::class, 'store'])->name('maison.membres.store');
        Route::put('/membres/{membreId}', [MembreController::class, 'update'])->name('maison.membres.update');
        Route::delete('/membres/{membreId}', [MembreController::class, 'destroy'])->name('maison.membres.destroy');

        // Activation/Désactivation des membres
        Route::post('/membres/{membreId}/activer', [MembreController::class, 'activer'])->name('maison.membres.activer');
        Route::post('/membres/{membreId}/desactiver', [MembreController::class, 'desactiver'])->name('maison.membres.desactiver');

        // Récupération des rôles disponibles
        Route::get('/membres/roles', [MembreController::class, 'rolesDisponibles'])->name('maison.membres.roles');

        // Statistiques de la maison
        Route::get('/stats', [MaisonController::class, 'stats'])->name('maison.stats');
    });

    // =================================================================
    // Routes pour l'admin plateforme
    // =================================================================
    Route::middleware(['role:admin_plateforme|super_admin'])
         ->prefix('admin')
         ->group(function () {

        // Statistiques globales
        Route::get('/stats', [AdminMaisonController::class, 'stats'])->name('admin.maisons.stats');

        // Liste des maisons à valider
        Route::get('/maisons/en-attente', [AdminMaisonController::class, 'enAttente'])
            ->name('admin.maisons.en-attente');

        // Liste de toutes les maisons
        Route::get('/maisons', [AdminMaisonController::class, 'index'])
            ->name('admin.maisons.index');

        // Création d'une maison (Admin Plateforme et Super Admin)
        Route::post('/maisons', [AdminMaisonController::class, 'store'])
            ->name('admin.maisons.store')
            ->middleware(['role:super_admin|admin_plateforme']);

        // Mise à jour d'une maison (Admin Plateforme et Super Admin)
        Route::put('/maisons/{maisonId}', [AdminMaisonController::class, 'update'])
            ->name('admin.maisons.update')
            ->middleware(['role:super_admin|admin_plateforme']);

        // Détail d'une maison
        Route::get('/maisons/{maisonId}', [AdminMaisonController::class, 'show'])
            ->name('admin.maisons.show');

        // Validation d'une maison
        Route::post('/maisons/{maisonId}/valider', [AdminMaisonController::class, 'valider'])
            ->name('admin.maisons.valider');

        // Rejet d'une maison
        Route::post('/maisons/{maisonId}/rejeter', [AdminMaisonController::class, 'rejeter'])
            ->name('admin.maisons.rejeter');

        // Suspension d'une maison active
        Route::post('/maisons/{maisonId}/suspendre', [AdminMaisonController::class, 'suspendre'])
            ->name('admin.maisons.suspendre');

        // Activation d'une maison suspendue
        Route::post('/maisons/{maisonId}/activer', [AdminMaisonController::class, 'activer'])
            ->name('admin.maisons.activer');

        // Suppression d'une maison (Super Admin uniquement)
        Route::delete('/maisons/{maisonId}', [AdminMaisonController::class, 'destroy'])
            ->name('admin.maisons.destroy')
            ->middleware(['role:super_admin']);

        // Synchronisation des permissions (Super Admin uniquement)
        Route::post('/sync-permissions', [AdminMaisonController::class, 'syncPermissions'])
            ->name('admin.maisons.sync-permissions')
            ->middleware(['role:super_admin']);
    });
});

// =====================================================================
// Routes publiques (sans authentification)
// =====================================================================
Route::prefix('api/maison')->group(function () {

    // Liste publique des maisons actives
    Route::get('/publiques', [MaisonController::class, 'indexPublic'])
        ->name('maison.public.index');

    // Détail public d'une maison
    Route::get('/publiques/{slug}', [MaisonController::class, 'showPublic'])
        ->name('maison.public.show');
});
