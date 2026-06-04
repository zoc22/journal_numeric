<?php
// app-modules/User/Http/Controllers/RoleController.php

namespace Modules\User\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Modules\User\Http\Resources\RoleResource;

/**
 * Contrôleur Role – Gère l'affichage des rôles et permissions.
 *
 * Ce contrôleur permet de :
 * - Lister tous les rôles disponibles
 * - Voir les permissions associées à un rôle
 *
 * Ces informations sont utiles pour l'administration
 * et pour l'affichage des dashboards.
 */
class RoleController extends Controller
{
    /**
     * Récupère la liste de tous les rôles.
     *
     * GET /api/roles
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $roles = Role::where('guard_name', 'sanctum')
            ->orderBy('level', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => RoleResource::collection($roles),
        ]);
    }

    /**
     * Récupère la liste des permissions d'un rôle spécifique.
     *
     * GET /api/roles/{role}/permissions
     *
     * @param Role $role
     * @return JsonResponse
     */
    public function permissions(Role $role): JsonResponse
    {
        $permissions = $role->permissions()->get();

        return response()->json([
            'success' => true,
            'data' => $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'module' => $permission->module,
                    'action' => $permission->action,
                ];
            }),
        ]);
    }
}
