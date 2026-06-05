<?php

declare(strict_types=1);

namespace Modules\Maison\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Maison\Http\Requests\UpdateMaisonRequest;
use Modules\Maison\Http\Resources\MaisonCollection;
use Modules\Maison\Http\Resources\MaisonResource;
use Modules\Maison\Models\Maison;
use Modules\Maison\Services\MembreService;
use Modules\Maison\Services\MaisonService;

/**
 * Contrôleur pour la gestion des maisons d'édition.
 *
 * Ce contrôleur gère :
 * - La consultation des informations de la maison (pour les membres)
 * - La mise à jour des informations de la maison (pour l'Éditeur en Chef)
 * - Les routes publiques (liste des maisons actives, détail public)
 *
 * @package Modules\Maison\Http\Controllers
 */
class MaisonController extends Controller
{
    /**
     * Constructeur.
     *
     * @param MaisonService $maisonService Service de gestion des maisons
     * @param MembreService $membreService Service de gestion des membres
     */
    public function __construct(
        protected MaisonService $maisonService,
        protected MembreService $membreService
    ) {}

    // =========================================================================
    // ROUTES POUR LES MEMBRES DE LA MAISON (authentifiées)
    // =========================================================================

    /**
     * Affiche les informations de la maison de l'utilisateur connecté.
     *
     * Récupère la maison à laquelle appartient l'utilisateur authentifié
     * et retourne ses informations détaillées.
     *
     * @route GET /api/maison/gerer
     * @middleware auth:sanctum
     *
     * @param Request $request La requête HTTP
     * @return MaisonResource|JsonResponse Les informations de la maison
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // Récupérer la maison de l'utilisateur via la relation membre_maison
        $membre = $user->membres()->with('maison')->where('est_actif', true)->first();

        if (!$membre || !$membre->maison) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'appartenez à aucune maison d\'édition.',
            ], 404);
        }

        $maison = $membre->maison;

        // Charger les statistiques
        $maison->loadCount(['membres' => function ($query) {
            $query->where('est_actif', true);
        }]);

        return response()->json([
            'success' => true,
            'data' => new MaisonResource($maison),
        ]);
    }

    /**
     * Met à jour les informations de la maison.
     *
     * Permet à l'Éditeur en Chef de modifier les informations de sa maison.
     *
     * @route PUT /api/maison/gerer
     * @middleware auth:sanctum
     *
     * @param UpdateMaisonRequest $request La requête contenant les données de mise à jour
     * @return MaisonResource|JsonResponse La maison mise à jour
     */
    public function update(UpdateMaisonRequest $request): JsonResponse
    {
        $user = $request->user();

        // Récupérer la maison de l'utilisateur
        $membre = $user->membres()->with('maison')->where('est_actif', true)->first();

        if (!$membre || !$membre->maison) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'appartenez à aucune maison d\'édition.',
            ], 404);
        }

        $maison = $membre->maison;

        // Vérifier que l'utilisateur est bien Éditeur en Chef de CETTE maison
        if (!$maison->aLeRole($user->id, 'editeur_en_chef')) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un Éditeur en Chef peut modifier les informations de la maison.',
            ], 403);
        }

        $data = $request->validated();

        $updatedMaison = $this->maisonService->update($maison->id, $data);

        return response()->json([
            'success' => true,
            'data' => $updatedMaison,
        ]);
    }

    // =========================================================================
    // ROUTES PUBLIQUES (sans authentification)
    // =========================================================================

    /**
     * Liste publique des maisons d'édition actives.
     *
     * Retourne une liste paginée des maisons actives avec leurs statistiques.
     * Accessible sans authentification pour le portail public.
     *
     * @route GET /api/maison/publiques
     * @public
     *
     * @param Request $request La requête HTTP
     * @return MaisonCollection Liste des maisons actives
     */
    public function indexPublic(Request $request): MaisonCollection
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = min($perPage, 50); // Limite à 50 éléments maximum

        $maisons = $this->maisonService->getActiveMaisons($perPage);

        return $maisons->additional(['success' => true]);
    }

    /**
     * Détail public d'une maison d'édition.
     *
     * Retourne les informations publiques d'une maison à partir de son slug.
     * Accessible sans authentification pour le portail public.
     *
     * @route GET /api/maison/publiques/{slug}
     * @public
     *
     * @param string $slug Le slug unique de la maison
     * @return MaisonResource|JsonResponse Les informations publiques de la maison
     */
    public function showPublic(string $slug): JsonResponse
    {
        try {
            $maison = Maison::where('slug', $slug)
                ->where('statut', 'active')
                ->withCount(['membres' => function ($query) {
                    $query->where('est_actif', true);
                }])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => new MaisonResource($maison),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Maison d\'édition non trouvée.',
            ], 404);
        }
    }

    // =========================================================================
    // ROUTES STATISTIQUES
    // =========================================================================

    /**
     * Récupère les statistiques de la maison de l'utilisateur.
     *
     * @route GET /api/maison/gerer/stats
     * @middleware auth:sanctum
     *
     * @param Request $request La requête HTTP
     * @return JsonResponse Les statistiques de la maison
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $membre = $user->membres()->with('maison')->where('est_actif', true)->first();

        if (!$membre || !$membre->maison) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'appartenez à aucune maison d\'édition.',
            ], 404);
        }

        $maison = $membre->maison;

        $stats = [
            'total_membres' => $maison->membres()->where('est_actif', true)->count(),
            'total_articles' => 0,
            'articles_publies' => 0,
            'articles_en_review' => 0,
            'articles_soumis' => 0,
            'articles_brouillon' => 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
