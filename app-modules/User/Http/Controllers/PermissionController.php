<?php
// app-modules/User/Http/Controllers/PermissionController.php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

/**
 * Contrôleur Permission – Gère l'affichage des permissions.
 *
 * Ce contrôleur permet de :
 * - Lister toutes les permissions disponibles
 *
 * Utile pour l'interface d'administration où les super admins
 * peuvent visualiser toutes les permissions du système.
 */
class PermissionController extends Controller
{
    /**
     * Récupère la liste de toutes les permissions.
     *
     * GET /api/permissions
     *
     * Les permissions sont triées par module puis par action pour faciliter
     * la navigation et l'organisation dans l'interface d'administration.
     *
     * @return JsonResponse JSON contenant toutes les permissions
     */
    public function index(): JsonResponse
    {
        // Récupère toutes les permissions du guard 'sanctum'
        $permissions = Permission::where('guard_name', 'sanctum')
            // Trie d'abord par module (ex: article, user, house)
            ->orderBy('module')
            // Puis par action (ex: create, edit, delete)
            ->orderBy('action')
            ->get();

        // Formate les permissions pour l'API
        return response()->json([
            'success' => true,
            'data' => $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'module' => $permission->module,
                    'action' => $permission->action,
                    'created_at' => $permission->created_at,
                ];
            }),
        ]);
    }

    /**
     * Récupère les permissions groupées par module.
     *
     * GET /api/permissions/grouped
     *
     * Utile pour les interfaces d'administration où on veut afficher
     * les permissions organisées par module plutôt qu'en liste plate.
     *
     * Exemple de réponse :
     * {
     *   "article": [...],
     *   "user": [...],
     *   "house": [...]
     * }
     *
     * @return JsonResponse JSON contenant les permissions groupées par module
     */
    public function grouped(): JsonResponse
    {
        // Récupère toutes les permissions
        $permissions = Permission::where('guard_name', 'sanctum')
            // Trie par module
            ->orderBy('module')
            ->get();

        // Groupe les permissions par module
        $grouped = $permissions->groupBy('module');

        // Retourne les permissions groupées
        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }
}
