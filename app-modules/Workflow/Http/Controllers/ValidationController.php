<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Modules\Article\Models\Article;
use Modules\User\Models\User;
use Modules\Workflow\Enums\ArticleStatus;
use Modules\Workflow\Enums\RoleLevel;
use Modules\Workflow\Http\Requests\ValidationRequest;
use Modules\Workflow\Services\WorkflowService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Contrôleur pour les validations multi-niveaux
 *
 * Gère les endpoints de validation pour chaque niveau hiérarchique :
 * - Reviewer (niveau 3)
 * - Éditeur Associé (niveau 4)
 * - Directeur de Collection (niveau 5)
 * - Éditeur en Chef (niveau 6)
 *
 * Chaque endpoint vérifie que l'utilisateur a le rôle approprié
 * avant d'autoriser la validation.
 */
class ValidationController extends Controller implements HasMiddleware
{
    /**
     * Constructeur
     */
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

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
     * Validation par un Reviewer (niveau 3)
     *
     * @param ValidationRequest $request
     * @param Article $article
     * @return JsonResponse
     */
    public function validerParReviewer(ValidationRequest $request, Article $article): JsonResponse
    {
        $user = $request->user();

        // Vérifie que l'utilisateur a le rôle reviewer
        if (!$user->hasRole('reviewer')) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un reviewer peut effectuer cette validation',
            ], SymfonyResponse::HTTP_FORBIDDEN);
        }

        // Vérifie que l'article est au bon statut
        if ($article->statut !== ArticleStatus::EN_RELECTURE->value) {
            return response()->json([
                'success' => false,
                'message' => 'L\'article doit être en relecture pour être validé par un reviewer',
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $transition = $this->workflowService->approuverArticle(
                $article,
                $user,
                $request->input('commentaire')
            );

            return response()->json([
                'success' => true,
                'message' => 'Article validé avec succès par le reviewer',
                'data' => [
                    'article_id' => $article->id,
                    'nouveau_statut' => $article->fresh()->statut,
                    'transition' => $transition,
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Validation par un Éditeur Associé (niveau 4)
     *
     * @param ValidationRequest $request
     * @param Article $article
     * @return JsonResponse
     */
    public function validerParEditeurAssocie(ValidationRequest $request, Article $article): JsonResponse
    {
        $user = $request->user();

        // Vérifie que l'utilisateur a le rôle éditeur associé
        if (!$user->hasRole('editeur_associe')) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un éditeur associé peut effectuer cette validation',
            ], SymfonyResponse::HTTP_FORBIDDEN);
        }

        // Vérifie que l'article est au bon statut
        if ($article->statut !== ArticleStatus::VALIDE_REVIEWER->value) {
            return response()->json([
                'success' => false,
                'message' => 'L\'article doit être validé par les reviewers avant la validation éditeur',
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $transition = $this->workflowService->approuverArticle(
                $article,
                $user,
                $request->input('commentaire')
            );

            return response()->json([
                'success' => true,
                'message' => 'Article validé avec succès par l\'éditeur associé',
                'data' => [
                    'article_id' => $article->id,
                    'nouveau_statut' => $article->fresh()->statut,
                    'transition' => $transition,
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Validation par un Directeur de Collection (niveau 5)
     *
     * @param ValidationRequest $request
     * @param Article $article
     * @return JsonResponse
     */
    public function validerParDirecteur(ValidationRequest $request, Article $article): JsonResponse
    {
        $user = $request->user();

        // Vérifie que l'utilisateur a le rôle directeur de collection
        if (!$user->hasRole('directeur_collection')) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un directeur de collection peut effectuer cette validation',
            ], SymfonyResponse::HTTP_FORBIDDEN);
        }

        // Vérifie que l'article est au bon statut
        if ($article->statut !== ArticleStatus::VALIDE_EDITEUR->value) {
            return response()->json([
                'success' => false,
                'message' => 'L\'article doit être validé par l\'éditeur associé avant la validation du directeur',
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $transition = $this->workflowService->approuverArticle(
                $article,
                $user,
                $request->input('commentaire')
            );

            return response()->json([
                'success' => true,
                'message' => 'Article validé avec succès par le directeur de collection',
                'data' => [
                    'article_id' => $article->id,
                    'nouveau_statut' => $article->fresh()->statut,
                    'transition' => $transition,
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Validation finale par l'Éditeur en Chef (niveau 6)
     *
     * @param ValidationRequest $request
     * @param Article $article
     * @return JsonResponse
     */
    public function validerFinal(ValidationRequest $request, Article $article): JsonResponse
    {
        $user = $request->user();

        // Vérifie que l'utilisateur a le rôle éditeur en chef
        if (!$user->hasRole('editeur_chef')) {
            return response()->json([
                'success' => false,
                'message' => 'Seul l\'éditeur en chef peut effectuer cette validation finale',
            ], SymfonyResponse::HTTP_FORBIDDEN);
        }

        // Vérifie que l'article est au bon statut
        if ($article->statut !== ArticleStatus::VALIDE_DIRECTEUR->value) {
            return response()->json([
                'success' => false,
                'message' => 'L\'article doit être validé par le directeur avant la validation finale',
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $transition = $this->workflowService->approuverArticle(
                $article,
                $user,
                $request->input('commentaire')
            );

            return response()->json([
                'success' => true,
                'message' => 'Validation finale effectuée avec succès',
                'data' => [
                    'article_id' => $article->id,
                    'nouveau_statut' => $article->fresh()->statut,
                    'transition' => $transition,
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Publication après validation finale
     *
     * @param ValidationRequest $request
     * @param Article $article
     * @return JsonResponse
     */
    public function publierApresValidation(ValidationRequest $request, Article $article): JsonResponse
    {
        $user = $request->user();

        // Vérifie que l'utilisateur a le rôle éditeur en chef
        if (!$user->hasRole('editeur_chef')) {
            return response()->json([
                'success' => false,
                'message' => 'Seul l\'éditeur en chef peut publier un article',
            ], SymfonyResponse::HTTP_FORBIDDEN);
        }

        // Vérifie que l'article est en validation finale
        if ($article->statut !== ArticleStatus::VALIDE_FINAL->value) {
            return response()->json([
                'success' => false,
                'message' => 'L\'article doit avoir la validation finale avant publication',
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $transition = $this->workflowService->publierArticle(
                $article,
                $user,
                $request->input('commentaire')
            );

            return response()->json([
                'success' => true,
                'message' => 'Article publié avec succès',
                'data' => [
                    'article_id' => $article->id,
                    'publie_le' => $article->fresh()->publie_le,
                    'transition' => $transition,
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], SymfonyResponse::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Vérifie si l'utilisateur peut valider à un niveau donné
     *
     * @param Article $article
     * @return JsonResponse
     */
    public function niveauxValidationDisponibles(Article $article): JsonResponse
    {
        $user = request()->user();

        if (!$user) {
            return response()->json([
                'success' => true,
                'data' => [
                    'niveaux_disponibles' => [],
                    'message' => 'Authentification requise',
                ],
            ]);
        }

        $niveauxDisponibles = [];
        $statutActuel = ArticleStatus::fromString($article->statut);

        // Vérifie chaque niveau de validation
        if ($statutActuel === ArticleStatus::EN_RELECTURE && $user->hasRole('reviewer')) {
            $niveauxDisponibles[] = [
                'niveau' => 'reviewer',
                'label' => 'Validation Reviewer',
                'statut_cible' => ArticleStatus::VALIDE_REVIEWER->value,
            ];
        }

        if ($statutActuel === ArticleStatus::VALIDE_REVIEWER && $user->hasRole('editeur_associe')) {
            $niveauxDisponibles[] = [
                'niveau' => 'editeur_associe',
                'label' => 'Validation Éditeur Associé',
                'statut_cible' => ArticleStatus::VALIDE_EDITEUR->value,
            ];
        }

        if ($statutActuel === ArticleStatus::VALIDE_EDITEUR && $user->hasRole('directeur_collection')) {
            $niveauxDisponibles[] = [
                'niveau' => 'directeur_collection',
                'label' => 'Validation Directeur de Collection',
                'statut_cible' => ArticleStatus::VALIDE_DIRECTEUR->value,
            ];
        }

        if ($statutActuel === ArticleStatus::VALIDE_DIRECTEUR && $user->hasRole('editeur_chef')) {
            $niveauxDisponibles[] = [
                'niveau' => 'editeur_chef',
                'label' => 'Validation Finale',
                'statut_cible' => ArticleStatus::VALIDE_FINAL->value,
            ];
        }

        if ($statutActuel === ArticleStatus::VALIDE_FINAL && $user->hasRole('editeur_chef')) {
            $niveauxDisponibles[] = [
                'niveau' => 'publication',
                'label' => 'Publication',
                'statut_cible' => ArticleStatus::PUBLIE->value,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'statut_actuel' => $article->statut,
                'statut_actuel_label' => $statutActuel->label(),
                'niveaux_disponibles' => $niveauxDisponibles,
            ],
        ]);
    }
}
