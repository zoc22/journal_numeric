<?php
// app-modules/User/Services/RoleService.php

namespace Modules\User\Services;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Service Role – Gère la logique métier liée aux rôles.
 *
 * Ce service permet de :
 * - Récupérer les rôles avec leurs utilisateurs
 * - Synchroniser les permissions d'un rôle
 * - Créer un nouveau rôle avec ses permissions
 */
class RoleService
{
    /**
     * Récupère tous les rôles avec le nombre d'utilisateurs.
     *
     * @return Collection
     */
    public function getAllWithCounts(): Collection
    {
        return Role::where('guard_name', 'sanctum')
            ->withCount('users')
            ->orderBy('level', 'desc')
            ->get();
    }

    /**
     * Synchronise les permissions d'un rôle.
     *
     * @param Role $role
     * @param array $permissionNames
     * @return Role
     */
    public function syncPermissions(Role $role, array $permissionNames): Role
    {
        $permissions = Permission::whereIn('name', $permissionNames)
            ->where('guard_name', 'sanctum')
            ->get();

        $role->syncPermissions($permissions);

        return $role->fresh('permissions');
    }

    /**
     * Crée un nouveau rôle avec ses permissions.
     *
     * @param string $name Nom du rôle
     * @param string $displayName Nom d'affichage
     * @param int $level Niveau hiérarchique
     * @param array $permissionNames Permissions à assigner
     * @return Role
     */
    public function createRole(string $name, string $displayName, int $level, array $permissionNames = []): Role
    {
        $role = Role::create([
            'name' => $name,
            'display_name' => $displayName,
            'level' => $level,
            'guard_name' => 'sanctum',
        ]);

        if (!empty($permissionNames)) {
            $this->syncPermissions($role, $permissionNames);
        }

        return $role;
    }
}
