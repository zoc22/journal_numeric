<?php

declare(strict_types=1);

namespace Modules\Workflow\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Article\Models\Article;
use Modules\Workflow\Enums\ArticleStatus;
use Modules\Workflow\Services\ReviewAssignmentService;
use Modules\Workflow\Services\WorkflowService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Contrôleur pour l'historique du workflow
 */
class WorkflowHistoryController extends Controller implements HasMiddleware
{
    public function __construct(
        protected WorkflowService $workflowService,
        protected ReviewAssignmentService $reviewAssignmentService
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
     * Exécute une transition de workflow
     */
    public function transition(\Illuminate\Http\Request $request, Article $article): JsonResponse
    {
        $request->validate([
            'statut_cible' => 'required|string',
            'commentaire' => 'nullable|string',
            'raison' => 'nullable|string',
        ]);

        try {
            if ($request->statut_cible === ArticleStatus::REJETE->value) {
                $transition = $this->workflowService->rejeterArticle(
                    $article,
                    $request->user(),
                    $request->input('raison', 'Rejeté via API')
                );
            } else {
                $transition = $this->workflowService->transition(
                    $article,
                    $request->statut_cible,
                    $request->user(),
                    $request->input('commentaire')
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Transition effectuée avec succès',
                'data' => [
                    'article' => [
                        'id' => $article->id,
                        'statut' => $article->fresh()->statut,
                    ],
                    'transition' => [
                        'id' => $transition->id,
                        'statut_cible' => $transition->statut_cible,
                    ],
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], \Symfony\Component\HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            // Pour le cas où le journaliste n'a pas les droits pour cette transition ou autre erreur
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], \Symfony\Component\HttpFoundation\Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Historique des transitions d'un article
     */
    public function articleHistory(Article $article): JsonResponse
    {
        $transitions = $this->workflowService->obtenirHistorique($article);

        return response()->json([
            'success' => true,
            'data' => [
                'article_id' => $article->id,
                'titre' => $article->titre,
                'statut_actuel' => $article->statut,
                'statut_actuel_label' => ArticleStatus::fromString($article->statut)->label(),
                'transitions' => $transitions->map(function ($transition) {
                    return [
                        'id' => $transition->id,
                        'statut_origine' => $transition->statut_origine,
                        'statut_origine_label' => ArticleStatus::fromString($transition->statut_origine)->label(),
                        'statut_cible' => $transition->statut_cible,
                        'statut_cible_label' => ArticleStatus::fromString($transition->statut_cible)->label(),
                        'utilisateur' => [
                            'id' => $transition->utilisateur?->id,
                            'nom' => $transition->utilisateur?->nom,
                        ],
                        'commentaires' => $transition->commentaires,
                        'metadonnees' => $transition->metadonnees,
                        'created_at' => $transition->created_at->toISOString(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Historique des reviews d'un article
     */
    public function reviewHistory(Article $article): JsonResponse
    {
        $assignments = $this->reviewAssignmentService->getByArticle($article->id);

        return response()->json([
            'success' => true,
            'data' => [
                'article_id' => $article->id,
                'titre' => $article->titre,
                'score_qualite_moyen' => $this->reviewAssignmentService->getScoreQualiteMoyen($article),
                'assignations' => $assignments->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'reviewer' => [
                            'id' => $assignment->reviewer?->id,
                            'nom' => $assignment->reviewer?->nom,
                        ],
                        'statut' => $assignment->statut,
                        'statut_label' => $assignment->statut_label,
                        'ordre_review' => $assignment->ordre_review,
                        'assigne_par' => $assignment->assignePar?->nom,
                        'assigne_le' => $assignment->assigne_le?->toISOString(),
                        'deadline' => $assignment->deadline?->toISOString(),
                        'feedback' => $assignment->feedback->map(function ($feedback) {
                            return [
                                'id' => $feedback->id,
                                'decision' => $feedback->decision,
                                'commentaire_global' => $feedback->commentaire_global,
                                'score_qualite' => $feedback->score_qualite,
                                'numero_iteration' => $feedback->numero_iteration,
                                'retour_le' => $feedback->retour_le->toISOString(),
                            ];
                        }),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Transitions possibles pour un article
     */
    public function possibleTransitions(Article $article): JsonResponse
    {
        $user = request()->user();

        if (!$user) {
            return response()->json([
                'success' => true,
                'data' => [
                    'transitions_possibles' => [],
                    'message' => 'Authentification requise pour voir les transitions',
                ],
            ]);
        }

        $tousStatuts = ArticleStatus::cases();
        $transitionsPossibles = [];

        foreach ($tousStatuts as $statut) {
            if ($this->workflowService->transitionPossible($article, $statut->value, $user)) {
                $transitionsPossibles[] = [
                    'statut' => $statut->value,
                    'label' => $statut->label(),
                    'couleur' => $statut->couleur(),
                    'icone' => $statut->icone(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'statut_actuel' => $article->statut,
                'statut_actuel_label' => ArticleStatus::fromString($article->statut)->label(),
                'transitions_possibles' => $transitionsPossibles,
            ],
        ]);
    }
}
