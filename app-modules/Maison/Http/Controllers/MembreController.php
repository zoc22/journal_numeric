<?php

declare(strict_types=1);

namespace Modules\Maison\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Maison\Http\Requests\AddMembreRequest;
use Modules\Maison\Http\Resources\MembreResource;
use Modules\Maison\Models\MembreMaison;
use Modules\Maison\Services\MembreService;
use Spatie\Permission\Models\Role;

/**
 * Contrôleur pour la gestion des membres d'une maison d'édition.
 *
 * Ce contrôleur gère :
 * - L'ajout de membres à la maison
 * - La modification des rôles des membres
 * - La suppression (désactivation) des membres
 * - L'activation/réactivation des membres
 * - La liste des membres avec filtres
 *
 * @package Modules\Maison\Http\Controllers
 */
class MembreController extends Controller
{
    /**
     * Constructeur.
     *
     * @param MembreService $membreService Service de gestion des membres
     */
    public function __construct(
        protected MembreService $membreService
    ) {}

    /**
     * Liste les membres de la maison de l'utilisateur connecté.
     *
     * @route GET /api/maison/gerer/membres
     * @middleware auth:sanctum
     *
     * @param Request $request La requête HTTP
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|JsonResponse
     */
    public function index(Request $request): JsonResponse
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

        $maisonId = $membre->maison_id;

        // Vérifier les droits (Éditeur en Chef ou Admin)
        $userRole = $this->getUserRoleInMaison($user->id, $maisonId);
        $canViewAll = in_array($userRole, ['editeur_en_chef', 'directeur_collection', 'editeur_associe']);

        if (!$canViewAll) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas les droits pour voir la liste des membres.',
            ], 403);
        }

        $filtres = [
            'est_actif' => $request->input('est_actif'),
            'role_id' => $request->input('role_id'),
            'search' => $request->input('search'),
        ];

        $perPage = (int) $request->input('per_page', 15);
        $perPage = min($perPage, 50);

        $membres = $this->membreService->listByMaison($maisonId, $filtres, $perPage);

        return response()->json([
            'success' => true,
            'data' => $membres,
        ]);
    }

    /**
     * Ajoute un membre à la maison.
     *
     * @route POST /api/maison/gerer/membres
     * @middleware auth:sanctum
     *
     * @param AddMembreRequest $request La requête contenant les données du membre
     * @return MembreResource|JsonResponse Le membre ajouté
     */
    public function store(AddMembreRequest $request): JsonResponse
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
                'message' => 'Seul un Éditeur en Chef peut ajouter des membres.',
            ], 403);
        }

        $data = $request->validated();

        try {
            $nouveauMembre = $this->membreService->ajouterMembre(
                $membre->maison_id,
                $data['utilisateur_id'],
                $data['role_id']
            );

            return response()->json([
                'success' => true,
                'data' => $nouveauMembre,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Met à jour le rôle d'un membre.
     *
     * @route PUT /api/maison/gerer/membres/{membreId}
     * @middleware auth:sanctum
     *
     * @param Request $request La requête HTTP
     * @param string $membreId ID du membre à modifier
     * @return MembreResource|JsonResponse Le membre mis à jour
     */
    public function update(Request $request, string $membreId): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est bien Éditeur en Chef
        if (!$user->hasRole('editeur_en_chef')) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un Éditeur en Chef peut modifier les rôles des membres.',
            ], 403);
        }

        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        try {
            $membre = $this->membreService->modifierRole($membreId, $request->input('role_id'));

            return response()->json([
                'success' => true,
                'data' => $membre,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Supprime (désactive) un membre de la maison.
     *
     * @route DELETE /api/maison/gerer/membres/{membreId}
     * @middleware auth:sanctum
     *
     * @param Request $request La requête HTTP
     * @param string $membreId ID du membre à supprimer
     * @return JsonResponse Réponse de confirmation
     */
    public function destroy(Request $request, string $membreId): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est bien Éditeur en Chef
        if (!$user->hasRole('editeur_en_chef')) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un Éditeur en Chef peut retirer des membres.',
            ], 403);
        }

        try {
            $this->membreService->retirerMembre($membreId);

            return response()->json([
                'success' => true,
                'message' => 'Membre retiré avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Active un membre (réintègre la maison).
     *
     * @route POST /api/maison/gerer/membres/{membreId}/activer
     * @middleware auth:sanctum
     *
     * @param Request $request La requête HTTP
     * @param string $membreId ID du membre à activer
     * @return MembreResource|JsonResponse Le membre activé
     */
    public function activer(Request $request, string $membreId): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est bien Éditeur en Chef
        if (!$user->hasRole('editeur_en_chef')) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un Éditeur en Chef peut activer des membres.',
            ], 403);
        }

        try {
            $membre = $this->membreService->activerMembre($membreId);

            return response()->json([
                'success' => true,
                'data' => $membre,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Désactive un membre (quitte la maison).
     *
     * @route POST /api/maison/gerer/membres/{membreId}/desactiver
     * @middleware auth:sanctum
     *
     * @param Request $request La requête HTTP
     * @param string $membreId ID du membre à désactiver
     * @return MembreResource|JsonResponse Le membre désactivé
     */
    public function desactiver(Request $request, string $membreId): JsonResponse
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est bien Éditeur en Chef
        if (!$user->hasRole('editeur_en_chef')) {
            return response()->json([
                'success' => false,
                'message' => 'Seul un Éditeur en Chef peut désactiver des membres.',
            ], 403);
        }

        try {
            $membre = $this->membreService->desactiverMembre($membreId);

            return response()->json([
                'success' => true,
                'data' => $membre,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Récupère les rôles disponibles pour une maison.
     *
     * @route GET /api/maison/gerer/membres/roles
     * @middleware auth:sanctum
     *
     * @param Request $request La requête HTTP
     * @return JsonResponse Liste des rôles disponibles
     */
    public function rolesDisponibles(Request $request): JsonResponse
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

        // Seul l'Éditeur en Chef peut voir les rôles disponibles (pour les assigner)
        if (!$maison->aLeRole($user->id, 'editeur_en_chef')) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé.',
            ], 403);
        }

        // Récupérer les rôles disponibles dans le tenant
        $roles = Role::whereIn('name', [
            'editeur_en_chef',
            'directeur_collection',
            'editeur_associe',
            'reviewer',
            'journaliste',
            'lecteur',
        ])->get(['id', 'name', 'level']);

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    // =========================================================================
    // MÉTHODES PRIVÉES
    // =========================================================================

    /**
     * Récupère le rôle d'un utilisateur dans une maison.
     *
     * @param string $userId ID de l'utilisateur
     * @param string $maisonId ID de la maison
     * @return string|null Le nom du rôle ou null
     */
    private function getUserRoleInMaison(string $userId, string $maisonId): ?string
    {
        $role = $this->membreService->getRoleUtilisateur($maisonId, $userId);

        return $role['name'] ?? null;
    }
}
