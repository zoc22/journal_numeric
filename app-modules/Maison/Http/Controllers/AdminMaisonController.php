<?php

declare(strict_types=1);

namespace Modules\Maison\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Maison\Http\Requests\CreateMaisonRequest;
use Modules\Maison\Http\Resources\MaisonCollection;
use Modules\Maison\Http\Resources\MaisonResource;
use Modules\Maison\Models\Maison;
use Modules\Maison\Services\MaisonService;
use Modules\Maison\Permissions\MaisonPermissions;

/**
 * Contrôleur pour l'administration des maisons d'édition.
 *
 * Ce contrôleur est accessible uniquement aux administrateurs plateforme
 * (rôles: admin_plateforme, super_admin).
 *
 * Il gère :
 * - La création de nouvelles maisons
 * - La validation des maisons en attente
 * - Le rejet des demandes
 * - La suspension/réactivation des maisons actives
 * - La liste complète des maisons avec filtres
 *
 * @package Modules\Maison\Http\Controllers
 */
class AdminMaisonController extends Controller
{
    /**
     * Constructeur.
     *
     * @param MaisonService $maisonService Service de gestion des maisons
     */
    public function __construct(
        protected MaisonService $maisonService
    ) {}

    /**
     * Liste toutes les maisons d'édition (administration).
     *
     * @route GET /api/maison/admin/maisons
     * @middleware auth:sanctum, role:admin_plateforme|super_admin
     *
     * @param Request $request La requête HTTP
     * @return MaisonCollection Liste des maisons
     */
    public function index(Request $request): MaisonCollection
    {
        $filtres = [
            'statut' => $request->input('statut'),
            'search' => $request->input('search'),
            'order_by' => $request->input('order_by', 'created_at'),
            'order_dir' => $request->input('order_dir', 'desc'),
        ];

        $perPage = (int) $request->input('per_page', 15);
        $perPage = min($perPage, 100); // Limite à 100 éléments maximum

        return $this->maisonService->paginate($filtres, $perPage)
            ->additional(['success' => true]);
    }

    /**
     * Liste des maisons en attente de validation.
     *
     * @route GET /api/maison/admin/maisons/en-attente
     * @middleware auth:sanctum, role:admin_plateforme|super_admin
     *
     * @param Request $request La requête HTTP
     * @return MaisonCollection Liste des maisons en attente
     */
    public function enAttente(Request $request): MaisonCollection
    {
        $filtres = [
            'statut' => 'en_attente',
            'search' => $request->input('search'),
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ];

        $perPage = (int) $request->input('per_page', 15);
        $perPage = min($perPage, 50);

        return $this->maisonService->paginate($filtres, $perPage)
            ->additional(['success' => true]);
    }

    /**
     * Crée une nouvelle maison d'édition.
     *
     * @route POST /api/maison/admin/maisons
     * @middleware auth:sanctum, role:super_admin
     *
     * @param CreateMaisonRequest $request La requête contenant les données de la maison
     * @return MaisonResource La maison créée
     */
    public function store(CreateMaisonRequest $request): JsonResponse
    {
        $data = $request->validated();

        $maison = $this->maisonService->create($data);

        return response()->json([
            'success' => true,
            'data' => $maison,
        ], 201);
    }

    /**
     * Met à jour une maison d'édition (administration).
     *
     * @route PUT /api/maison/admin/maisons/{maisonId}
     * @middleware auth:sanctum, role:admin_plateforme|super_admin
     *
     * @param Request $request La requête HTTP
     * @param string $maisonId ID de la maison
     * @return MaisonResource|JsonResponse La maison mise à jour
     */
    public function update(Request $request, string $maisonId): JsonResponse
    {
        $request->validate([
            'nom' => [
                'sometimes',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique(Maison::class, 'nom')->ignore($maisonId),
            ],
            'description' => 'nullable|string|max:5000',
            'email_contact' => 'sometimes|string|email|max:255',
        ]);

        try {
            $maison = $this->maisonService->update($maisonId, $request->all());

            return response()->json([
                'success' => true,
                'data' => $maison,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Affiche les détails d'une maison spécifique.
     *
     * @route GET /api/maison/admin/maisons/{maisonId}
     * @middleware auth:sanctum, role:admin_plateforme|super_admin
     *
     * @param string $maisonId ID de la maison
     * @return MaisonResource|JsonResponse Les détails de la maison
     */
    public function show(string $maisonId): JsonResponse
    {
        try {
            $maison = $this->maisonService->findById($maisonId, ['validateur', 'membres.utilisateur']);

            return response()->json([
                'success' => true,
                'data' => $maison,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Maison d\'édition non trouvée.',
            ], 404);
        }
    }

    /**
     * Valide une maison en attente.
     *
     * @route POST /api/maison/admin/maisons/{maisonId}/valider
     * @middleware auth:sanctum, role:admin_plateforme|super_admin
     *
     * @param Request $request La requête HTTP
     * @param string $maisonId ID de la maison à valider
     * @return MaisonResource|JsonResponse La maison validée
     */
    public function valider(Request $request, string $maisonId): JsonResponse
    {
        $user = $request->user();

        try {
            $maison = $this->maisonService->valider($maisonId, $user->id);

            // Optionnel : envoyer une notification à l'Éditeur en Chef de la maison
            // event(new MaisonValidee($maison));

            return response()->json([
                'success' => true,
                'data' => $maison,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Rejette une demande de création de maison.
     *
     * @route POST /api/maison/admin/maisons/{maisonId}/rejeter
     * @middleware auth:sanctum, role:admin_plateforme|super_admin
     *
     * @param Request $request La requête HTTP
     * @param string $maisonId ID de la maison à rejeter
     * @return MaisonResource|JsonResponse La maison rejetée
     */
    public function rejeter(Request $request, string $maisonId): JsonResponse
    {
        $user = $request->user();

        try {
            $maison = $this->maisonService->rejeter($maisonId, $user->id);

            return response()->json([
                'success' => true,
                'data' => $maison,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Suspend une maison active.
     *
     * @route POST /api/maison/admin/maisons/{maisonId}/suspendre
     * @middleware auth:sanctum, role:admin_plateforme|super_admin
     *
     * @param string $maisonId ID de la maison à suspendre
     * @return MaisonResource|JsonResponse La maison suspendue
     */
    public function suspendre(string $maisonId): JsonResponse
    {
        try {
            $maison = $this->maisonService->suspendre($maisonId);

            return response()->json([
                'success' => true,
                'data' => $maison,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Active une maison suspendue.
     *
     * @route POST /api/maison/admin/maisons/{maisonId}/activer
     * @middleware auth:sanctum, role:admin_plateforme|super_admin
     *
     * @param Request $request La requête HTTP
     * @param string $maisonId ID de la maison à activer
     * @return MaisonResource|JsonResponse La maison activée
     */
    public function activer(Request $request, string $maisonId): JsonResponse
    {
        $user = $request->user();

        try {
            $maison = $this->maisonService->activer($maisonId, $user->id);

            return response()->json([
                'success' => true,
                'data' => $maison,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Supprime une maison d'édition.
     *
     * @route DELETE /api/maison/admin/maisons/{maisonId}
     * @middleware auth:sanctum, role:super_admin
     *
     * @param string $maisonId ID de la maison à supprimer
     * @return JsonResponse Réponse de confirmation
     */
    public function destroy(string $maisonId): JsonResponse
    {
        try {
            $this->maisonService->delete($maisonId);

            return response()->json([
                'success' => true,
                'message' => 'Maison d\'édition supprimée avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Récupère les statistiques globales des maisons.
     *
     * @route GET /api/maison/admin/stats
     * @middleware auth:sanctum, role:admin_plateforme|super_admin
     *
     * @return JsonResponse Statistiques des maisons
     */
    public function stats(): JsonResponse
    {
        $stats = $this->maisonService->countByStatut();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Synchronise les permissions du module Maison.
     *
     * Cette route est utilisée lors de l'installation/mise à jour du module.
     *
     * @route POST /api/maison/admin/sync-permissions
     * @middleware auth:sanctum, role:super_admin
     *
     * @return JsonResponse Réponse de confirmation
     */
    public function syncPermissions(): JsonResponse
    {
        MaisonPermissions::run();

        return response()->json([
            'success' => true,
            'message' => 'Permissions du module Maison synchronisées avec succès.',
        ]);
    }
}
