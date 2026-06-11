<?php

declare(strict_types=1);

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Recruitment\Http\Requests\StoreApplicationRequest;
use Modules\Recruitment\Http\Requests\ReviewApplicationRequest;
use Modules\Recruitment\Http\Requests\UpdateApplicationRequest;
use Modules\Recruitment\Http\Resources\ApplicationResource;
use Modules\Recruitment\Models\Application;
use Modules\Recruitment\Models\CallForApplication;
use Modules\Recruitment\Services\ApplicationService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur de gestion des candidatures
 *
 * Gère la soumission, la modification et la revue des candidatures.
 */
class ApplicationController extends Controller implements HasMiddleware
{
    /**
     * Configuration des middlewares
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Constructeur
     */
    public function __construct(
        protected ApplicationService $applicationService
    ) {}

    /**
     * Liste des candidatures (pour un appel ou pour l'utilisateur)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = request()->user();
        $appelId = request()->input('appel_id');

        if ($appelId) {
            // Vérifie les droits d'accès aux candidatures de l'appel
            $call = CallForApplication::findOrFail($appelId);
            if (!$user->hasRole(['super_admin', 'admin_plateforme', 'editeur_chef', 'editeur_associe'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à voir ces candidatures.',
                ], Response::HTTP_FORBIDDEN);
            }

            $applications = $this->applicationService->getCandidaturesParAppel($call, request()->input('statut'));
        } else {
            // Candidatures de l'utilisateur connecté
            $applications = $this->applicationService->getCandidaturesParCandidat($user, request()->input('statut'));
        }

        return response()->json([
            'success' => true,
            'data' => ApplicationResource::collection($applications),
        ]);
    }

    /**
     * Soumet une nouvelle candidature
     *
     * @param StoreApplicationRequest $request
     * @param CallForApplication $call
     * @return JsonResponse
     */
    public function store(StoreApplicationRequest $request, CallForApplication $call): JsonResponse
    {
        try {
            $application = $this->applicationService->soumettre(
                $call,
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Candidature soumise avec succès',
                'data' => new ApplicationResource($application),
            ], Response::HTTP_CREATED);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Affiche une candidature
     *
     * @param Application $application
     * @return JsonResponse
     */
    public function show(Application $application): JsonResponse
    {
        $user = request()->user();

        // Vérification des droits d'accès
        if ($application->candidat_id !== $user->id &&
            !$user->hasRole(['super_admin', 'admin_plateforme', 'editeur_chef', 'editeur_associe'])) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à voir cette candidature.',
            ], Response::HTTP_FORBIDDEN);
        }

        $application->load(['candidat', 'appel', 'examinateur']);

        return response()->json([
            'success' => true,
            'data' => new ApplicationResource($application),
        ]);
    }

    /**
     * Met à jour une candidature
     *
     * @param UpdateApplicationRequest $request
     * @param Application $application
     * @return JsonResponse
     */
    public function update(UpdateApplicationRequest $request, Application $application): JsonResponse
    {
        $user = request()->user();

        // Seul le propriétaire peut modifier sa candidature
        if ($application->candidat_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cette candidature.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $application = $this->applicationService->mettreAJour($application, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Candidature mise à jour avec succès',
                'data' => new ApplicationResource($application),
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Revue d'une candidature (pour les éditeurs)
     *
     * @param ReviewApplicationRequest $request
     * @param Application $application
     * @return JsonResponse
     */
    public function review(ReviewApplicationRequest $request, Application $application): JsonResponse
    {
        $user = request()->user();

        // Vérification des droits d'examinateur
        if (!$user->hasRole(['editeur_chef', 'editeur_associe', 'admin_plateforme'])) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à examiner cette candidature.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            // Met d'abord en revue si nécessaire
            if ($application->statut === 'en_attente') {
                $this->applicationService->mettreEnRevue($application, $user);
            }

            // Applique la décision
            if ($request->decision === 'acceptee') {
                $application = $this->applicationService->accepter(
                    $application,
                    $user,
                    $request->input('commentaires_examen'),
                    $request->input('score')
                );
            } else {
                $application = $this->applicationService->rejeter(
                    $application,
                    $user,
                    $request->input('commentaires_examen')
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Candidature examinée avec succès',
                'data' => new ApplicationResource($application),
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
