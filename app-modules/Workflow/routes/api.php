<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Workflow\Http\Controllers\ReviewAssignmentController;
use Modules\Workflow\Http\Controllers\ReviewerFeedbackController;
use Modules\Workflow\Http\Controllers\WorkflowHistoryController;

/*
|--------------------------------------------------------------------------
| Routes API du module Workflow
|--------------------------------------------------------------------------
*/

Route::middleware(['api', 'auth:sanctum'])->prefix('api/workflow')->group(function () {

    // Routes pour les transitions
    Route::post('/transition/{article}', [WorkflowHistoryController::class, 'transition'])
        ->middleware('permission:workflow.transition');
    
    // Transitions possibles pour un article
    Route::get('/article/{article}/possible-transitions', [WorkflowHistoryController::class, 'possibleTransitions'])
        ->middleware('permission:workflow.voir');

    // Routes pour les assignations de review
    Route::prefix('review-assignments')->group(function () {
        Route::get('/', [ReviewAssignmentController::class, 'index'])
            ->middleware('permission:review.voir');
        Route::get('/my', [ReviewAssignmentController::class, 'myAssignments']);
        Route::post('/', [ReviewAssignmentController::class, 'store'])
            ->middleware('permission:review.assigner');
        Route::get('/{assignment}', [ReviewAssignmentController::class, 'show'])
            ->middleware('permission:review.voir');
        Route::post('/{assignment}/accept', [ReviewAssignmentController::class, 'accept']);
        Route::post('/{assignment}/reject', [ReviewAssignmentController::class, 'reject']);
        Route::delete('/{assignment}', [ReviewAssignmentController::class, 'destroy'])
            ->middleware('permission:review.supprimer');
    });

    // Routes pour les feedbacks
    Route::prefix('feedback')->group(function () {
        Route::post('/assignment/{assignment}', [ReviewerFeedbackController::class, 'store'])
            ->middleware('permission:review.feedback');
        Route::get('/assignment/{assignment}', [ReviewerFeedbackController::class, 'index'])
            ->middleware('permission:review.voir');
    });

    // Routes pour l'historique
    Route::prefix('history')->group(function () {
        Route::get('/article/{article}', [WorkflowHistoryController::class, 'articleHistory'])
            ->middleware('permission:article.voir_historique');
        Route::get('/article/{article}/reviews', [WorkflowHistoryController::class, 'reviewHistory'])
            ->middleware('permission:review.voir_historique');
    });
});
