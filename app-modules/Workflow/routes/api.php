<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Workflow\Http\Controllers\ReviewAssignmentController;
use Modules\Workflow\Http\Controllers\ReviewerFeedbackController;
use Modules\Workflow\Http\Controllers\ValidationController;
use Modules\Workflow\Http\Controllers\WorkflowHistoryController;

/*
|--------------------------------------------------------------------------
| Routes API du module Workflow
|--------------------------------------------------------------------------
*/

Route::middleware(['api', 'auth:sanctum', 'tenant'])->prefix('api/workflow')->group(function () {

    // === ROUTES DE VALIDATION MULTI-NIVEAUX ===
    Route::prefix('validate')->group(function () {
        // Validation par Reviewer (niveau 3)
        Route::post('/reviewer/{article}', [ValidationController::class, 'validerParReviewer'])
            ->middleware('permission:workflow.valider_reviewer');

        // Validation par Éditeur Associé (niveau 4)
        Route::post('/editeur-associe/{article}', [ValidationController::class, 'validerParEditeurAssocie'])
            ->middleware('permission:workflow.valider_editeur');

        // Validation par Directeur de Collection (niveau 5)
        Route::post('/directeur/{article}', [ValidationController::class, 'validerParDirecteur'])
            ->middleware('permission:workflow.valider_directeur');

        // Validation finale par Éditeur en Chef (niveau 6)
        Route::post('/final/{article}', [ValidationController::class, 'validerFinal'])
            ->middleware('permission:workflow.valider_final');

        // Publication après validation
        Route::post('/publier/{article}', [ValidationController::class, 'publierApresValidation'])
            ->middleware('permission:workflow.publier');

        // Niveaux de validation disponibles
        Route::get('/disponibles/{article}', [ValidationController::class, 'niveauxValidationDisponibles']);
    });

    // === ROUTES DE TRANSITION ===
    Route::prefix('transition')->group(function () {
        Route::post('/{article}', [WorkflowHistoryController::class, 'transition'])
            ->middleware('permission:workflow.transition');
        Route::get('/{article}/possible', [WorkflowHistoryController::class, 'possibleTransitions']);
    });

    // === ROUTES D'ASSIGNATION DE REVIEW ===
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

    // === ROUTES DE FEEDBACK ===
    Route::prefix('feedback')->group(function () {
        Route::post('/assignment/{assignment}', [ReviewerFeedbackController::class, 'store'])
            ->middleware('permission:review.feedback');
        Route::get('/assignment/{assignment}', [ReviewerFeedbackController::class, 'index'])
            ->middleware('permission:review.voir');
    });

    // === ROUTES D'HISTORIQUE ===
    Route::prefix('history')->group(function () {
        Route::get('/article/{article}', [WorkflowHistoryController::class, 'articleHistory'])
            ->middleware('permission:article.voir_historique');
        Route::get('/article/{article}/reviews', [WorkflowHistoryController::class, 'reviewHistory'])
            ->middleware('permission:review.voir_historique');
    });
});
