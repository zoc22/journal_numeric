<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Workflow\Http\Requests\SubmitFeedbackRequest;
use Modules\Workflow\Http\Resources\ReviewFeedbackResource;
use Modules\Workflow\Models\ReviewAssignment;
use Modules\Workflow\Services\ReviewAssignmentService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Contrôleur de gestion des feedbacks de review
 */
class ReviewerFeedbackController extends Controller implements HasMiddleware
{
    public function __construct(
        protected ReviewAssignmentService $assignmentService
    ) {}

    /**
     * Configuration des middlewares
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware('permission:review.feedback', only: ['store']),
        ];
    }

    /**
     * Soumet un feedback
     */
    public function store(SubmitFeedbackRequest $request, ReviewAssignment $assignment): JsonResponse
    {
        $feedback = $this->assignmentService->soumettreFeedback(
            $assignment,
            $request->user(),
            $request->decision,
            $request->input('commentaire_global'),
            $request->input('annotations'),
            $request->input('corrections_demandees'),
            $request->input('score_qualite')
        );

        return response()->json([
            'success' => true,
            'message' => 'Feedback soumis avec succès',
            'data' => new ReviewFeedbackResource($feedback),
        ]);
    }

    /**
     * Liste des feedbacks d'une assignation
     */
    public function index(ReviewAssignment $assignment): JsonResponse
    {
        $feedbacks = $assignment->feedback()->with('reviewer')->get();

        return response()->json([
            'success' => true,
            'data' => ReviewFeedbackResource::collection($feedbacks),
        ]);
    }
}
