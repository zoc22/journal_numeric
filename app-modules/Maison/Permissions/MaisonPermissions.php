<?php

declare(strict_types=1);

namespace Modules\Maison\Permissions;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Gestionnaire des permissions du module Maison.
 * Centralise la création et la synchronisation des permissions.
 */
class MaisonPermissions
{
    /**
     * Liste des permissions du module Maison.
     *
     * @var array<string, string>
     */
    public const PERMISSIONS = [
        // Gestion des maisons (admin plateforme)
        'maison.creer' => 'Créer une maison d\'édition',
        'maison.modifier' => 'Modifier une maison d\'édition',
        'maison.supprimer' => 'Supprimer une maison d\'édition',
        'maison.lister' => 'Lister les maisons d\'édition',
        'maison.voir' => 'Voir les détails d\'une maison',

        // Validation des maisons (admin plateforme)
        'maison.valider' => 'Valider une maison d\'édition',
        'maison.rejeter' => 'Rejeter une maison d\'édition',
        'maison.suspendre' => 'Suspendre une maison d\'édition',
        'maison.activer' => 'Activer une maison d\'édition',

        // Gestion des membres (Éditeur en Chef)
        'maison.membres.voir' => 'Voir les membres de la maison',
        'maison.membres.ajouter' => 'Ajouter un membre à la maison',
        'maison.membres.modifier' => 'Modifier un membre de la maison',
        'maison.membres.supprimer' => 'Supprimer un membre de la maison',
        'maison.membres.activer' => 'Activer un membre de la maison',
        'maison.membres.desactiver' => 'Désactiver un membre de la maison',

        // Gestion des rôles (Éditeur en Chef)
        'maison.roles.voir' => 'Voir les rôles de la maison',
        'maison.roles.assigner' => 'Assigner un rôle à un membre',

        // Gestion des catégories (Éditeur en Chef)
        'maison.categories.gerer' => 'Gérer les catégories de la maison',

        // Gestion des appels à candidatures (Éditeur en Chef)
        'maison.appels.creer' => 'Créer un appel à candidatures',
        'maison.appels.modifier' => 'Modifier un appel à candidatures',
        'maison.appels.supprimer' => 'Supprimer un appel à candidatures',
        'maison.appels.publier' => 'Publier un appel à candidatures',
    ];

    /**
     * Mapping des permissions par rôle.
     *
     * @var array<string, array<int, string>>
     */
    public const ROLE_PERMISSIONS = [
        'super_admin' => [
            'maison.creer',
            'maison.modifier',
            'maison.supprimer',
            'maison.lister',
            'maison.voir',
            'maison.valider',
            'maison.rejeter',
            'maison.suspendre',
            'maison.activer',
            'maison.membres.voir',
            'maison.membres.ajouter',
            'maison.membres.modifier',
            'maison.membres.supprimer',
            'maison.roles.voir',
            'maison.roles.assigner',
            'maison.categories.gerer',
            'maison.appels.creer',
            'maison.appels.modifier',
            'maison.appels.supprimer',
            'maison.appels.publier',
        ],
        'admin_plateforme' => [
            'maison.creer',
            'maison.modifier',
            'maison.lister',
            'maison.voir',
            'maison.valider',
            'maison.rejeter',
            'maison.suspendre',
            'maison.activer',
            'maison.membres.voir',
        ],
        'editeur_en_chef' => [
            'maison.modifier',
            'maison.voir',
            'maison.membres.voir',
            'maison.membres.ajouter',
            'maison.membres.modifier',
            'maison.membres.supprimer',
            'maison.membres.activer',
            'maison.membres.desactiver',
            'maison.roles.voir',
            'maison.roles.assigner',
            'maison.categories.gerer',
            'maison.appels.creer',
            'maison.appels.modifier',
            'maison.appels.supprimer',
            'maison.appels.publier',
        ],
        'directeur_collection' => [
            'maison.voir',
            'maison.membres.voir',
            'maison.categories.gerer',
        ],
        'editeur_associe' => [
            'maison.voir',
            'maison.membres.voir',
        ],
        'reviewer' => [
            'maison.voir',
        ],
        'journaliste' => [
            'maison.voir',
        ],
        'lecteur' => [
            'maison.voir',
        ],
    ];

    /**
     * Crée ou synchronise toutes les permissions du module.
     *
     * @param string $guardName Le garde à utiliser (par défaut: 'sanctum')
     * @return void
     */
    public static function syncPermissions(string $guardName = 'sanctum'): void
    {
        // Création des rôles par défaut si nécessaire
        foreach (array_keys(self::ROLE_PERMISSIONS) as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guardName,
            ]);
        }

        foreach (self::PERMISSIONS as $permissionName => $description) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guardName,
            ], [
                'description' => $description,
            ]);
        }
    }

    /**
     * Assigne les permissions aux rôles.
     *
     * @param string $guardName Le garde à utiliser
     * @return void
     */
    public static function assignPermissionsToRoles(string $guardName = 'sanctum'): void
    {
        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            /** @var Role|null $role */
            $role = Role::where('name', $roleName)
                ->where('guard_name', $guardName)
                ->first();

            if ($role) {
                foreach ($permissions as $permission) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }

    /**
     * Exécute la synchronisation complète des permissions.
     *
     * @return void
     */
    public static function run(): void
    {
        self::syncPermissions();
        self::assignPermissionsToRoles();
    }
}
