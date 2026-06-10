<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Media\Http\Controllers\ArticleMediaController;
use Modules\Media\Http\Controllers\MediaController;

/*
|--------------------------------------------------------------------------
| Routes API du module Media
|--------------------------------------------------------------------------
*/

Route::middleware(['api', 'auth:sanctum', 'tenant'])->prefix('api/workflow')->group(function () {

    // Routes de gestion des médias
    Route::prefix('media')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('media.index');
        Route::post('/', [MediaController::class, 'store'])->name('media.upload'); // Changed from /upload to / to match test
        Route::get('/statistiques', [MediaController::class, 'statistiques'])->name('media.statistiques');
        Route::get('/{medium}', [MediaController::class, 'show'])->name('media.show');
        Route::put('/{medium}', [MediaController::class, 'update'])->name('media.update');
        Route::delete('/{medium}', [MediaController::class, 'destroy'])->name('media.destroy');
    });

    // Routes d'association article-média
    Route::prefix('article-media')->group(function () {
        Route::get('/{article}', [ArticleMediaController::class, 'index'])->name('article-media.index');
        Route::post('/{article}/{medium}', [ArticleMediaController::class, 'attach'])->name('article-media.attach');
        Route::delete('/{article}/{medium}', [ArticleMediaController::class, 'detach'])->name('article-media.detach');
        Route::post('/{article}/{medium}/cover', [ArticleMediaController::class, 'setCover'])->name('article-media.set-cover');
        Route::put('/{article}/order', [ArticleMediaController::class, 'updateOrder'])->name('article-media.update-order');
        Route::put('/{article}/{medium}/active', [ArticleMediaController::class, 'setActive'])->name('article-media.set-active');
    });
});
