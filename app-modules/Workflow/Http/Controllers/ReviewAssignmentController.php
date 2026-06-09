<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Modules\Article\Models\Article;
use Modules\User\Models\User;
use Modules\Workflow\Http\Requests\AssignReviewerRequest;
use Modules\Workflow\Http\Resources\ReviewAssignmentResource;
use Modules\Workflow\Models\ReviewAssignment;
use Modules\Workflow\Services\ReviewAssignmentService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;

/**
 * Contrôleur de gestion des assignations de review
 */
class ReviewAssignmentController extends Controller implements HasMiddleware
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
            new Middleware('permission:review.assigner', except: ['index', 'show', 'myAssignments', 'accept', 'reject']),
        ];
    }

    /**
     * Liste des assignations
     */
    public function index(Request $request): JsonResponse
    {
        $assignments = ReviewAssignment::with(['article', 'reviewer', 'assignePar'])
            ->when(!$request->user()->hasRole(['super_admin', 'admin_plateforme']), function ($q) {
                return $q->whereHas('article', fn($sq) => $sq->where('maison_id', tenant('id')));
            })
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => ReviewAssignmentResource::collection($assignments),
        ]);
    }

    /**
     * Assignations de l'utilisateur connecté
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $assignments = $this->assignmentService->getPendingForReviewer($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => ReviewAssignmentResource::collection($assignments),
        ]);
    }

    /**
     * Crée une assignation
     */
    public function store(AssignReviewerRequest $request): JsonResponse
    {
        $article = Article::findOrFail($request->article_id);
        $reviewer = User::findOrFail($request->reviewer_id);

        $assignment = $this->assignmentService->assigner(
            $article,
            $reviewer,
            $request->user(),
            $request->input('ordre_review')
        );

        return response()->json([
            'success' => true,
            'message' => 'Reviewer assigné avec succès',
            'data' => new ReviewAssignmentResource($assignment->load(['article', 'reviewer'])),
        ], Response::HTTP_CREATED);
    }

    /**
     * Affiche une assignation
     */
    public function show(ReviewAssignment $assignment): JsonResponse
    {
        $assignment->load(['article', 'reviewer', 'assignePar', 'feedback']);

        return response()->json([
            'success' => true,
            'data' => new ReviewAssignmentResource($assignment),
        ]);
    }

    /**
     * Accepte une assignation
     */
    public function accept(ReviewAssignment $assignment, Request $request): JsonResponse
    {
        $this->assignmentService->accepter($assignment, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Assignation acceptée',
        ]);
    }

    /**
     * Refuse une assignation
     */
    public function reject(ReviewAssignment $assignment, AssignReviewerRequest $request): JsonResponse
    {
        $this->assignmentService->refuser($assignment, $request->user(), $request->input('raison'));

        return response()->json([
            'success' => true,
            'message' => 'Assignation refusée',
        ]);
    }

    /**
     * Supprime une assignation
     */
    public function destroy(ReviewAssignment $assignment): JsonResponse
    {
        $this->assignmentService->supprimer($assignment);

        return response()->json([
            'success' => true,
            'message' => 'Assignation supprimée',
        ]);
    }
}
