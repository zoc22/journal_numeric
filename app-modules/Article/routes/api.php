<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Article\Http\Controllers\ArticleController;
use Modules\Article\Http\Controllers\CategoryController;

/*
|--------------------------------------------------------------------------
| Routes API du module Article
|--------------------------------------------------------------------------
|
| Ces routes sont chargées dans le contexte du tenant.
| Toutes les routes sont préfixées par /api et protégées par le middleware
| d'authentification Sanctum selon les besoins.
|
*/

Route::middleware(['api', 'tenant'])->prefix('api')->group(function () {
    
    // Routes des articles
    Route::prefix('articles')->group(function () {
        // Routes publiques (certaines accessibles sans auth)
        Route::get('/', [ArticleController::class, 'index'])->name('articles.index');
        Route::get('/{article}', [ArticleController::class, 'show'])->name('articles.show');

        // Routes protégées par authentification
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/', [ArticleController::class, 'store'])->name('articles.store');
            Route::put('/{article}', [ArticleController::class, 'update'])->name('articles.update');
            Route::delete('/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');

            // Actions de workflow
            Route::post('/{article}/submit', [ArticleController::class, 'submit'])->name('articles.submit');

            // Versioning
            Route::get('/{article}/versions', [ArticleController::class, 'versions'])->name('articles.versions');
            Route::post('/{article}/versions/{versionId}/restore', [ArticleController::class, 'restaurerVersion'])->name('articles.versions.restore');

            // Statistiques
            Route::get('/{article}/statistiques', [ArticleController::class, 'statistiques'])->name('articles.statistiques');
        });
    });

    // Routes des catégories
    Route::prefix('categories')->group(function () {
        // Routes publiques
        Route::get('/', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/arborescence', [CategoryController::class, 'arborescence'])->name('categories.tree');
        Route::get('/{category}', [CategoryController::class, 'show'])->name('categories.show');

        // Routes protégées
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        });
    });
});
