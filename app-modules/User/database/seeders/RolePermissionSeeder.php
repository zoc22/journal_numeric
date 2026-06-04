<?php
// app-modules/User/database/seeders/RolePermissionSeeder.php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Seeder RolePermissionSeeder – Crée les rôles et permissions par défaut.
 *
 * Ce seeder est exécuté lors du remplissage initial de la base de données
 * pour créer une hiérarchie de rôles avec leurs permissions associées.
 *
 * Hiérarchie des rôles (du plus au moins privilegié) :
 * 1. Super Admin (niveau 8) - Accès complet
 * 2. Admin Plateforme (niveau 7) - Gestion de la plateforme
 * 3. Éditeur en Chef (niveau 6) - Gestion éditoriale complète
 * 4. Directeur de Collection (niveau 5) - Gestion de collection
 * 5. Éditeur Associé (niveau 4) - Édition associée
 * 6. Relecteur (niveau 3) - Review et validation
 * 7. Journaliste (niveau 2) - Création de contenu
 * 8. Lecteur (niveau 1) - Accès en lecture seule
 *
 * La configuration des rôles et permissions est stockée dans
 * app-modules/User/Config/config.php pour faciliter la maintenance.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Remplit la base tenant avec les rôles et permissions par défaut.
     *
     * @return void
     */
    public function run(): void
    {
        // Récupère la configuration des rôles et permissions depuis le fichier config
        $config = config('user');

        // Validation : s'assurer que la configuration existe
        if (!$config) {
            throw new \RuntimeException('Configuration "user" non trouvée. Vérifiez que le UserServiceProvider est bien enregistré.');
        }

        // ========================================
        // 1. Créer les permissions
        // ========================================
        // Les permissions sont les actions granulaires que les utilisateurs peuvent effectuer.
        // Exemple : article.create, user.manage, house.validate, etc.
        $defaultPermissions = $config['default_permissions'] ?? [];

        foreach ($defaultPermissions as $permissionName) {
            // Extrait le module et l'action du nom de la permission (ex: "article.create")
            $parts = explode('.', $permissionName);
            $module = $parts[0] ?? null;
            $action = $parts[1] ?? null;

            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'sanctum',
            ], [
                'module' => $module,
                'action' => $action,
            ]);
        }

        // ========================================
        // 2. Créer les rôles avec leurs niveaux hiérarchiques
        // ========================================
        // Les rôles constituent une hiérarchie avec des niveaux (level).
        // Plus le niveau est élevé, plus les permissions sont importantes.
        $rolesConfig = [
            'super_admin' => ['level' => 8, 'display_name' => 'Super Administrateur'],
            'admin_plateforme' => ['level' => 7, 'display_name' => 'Administrateur Plateforme'],
            'editeur_chef' => ['level' => 6, 'display_name' => 'Éditeur en Chef'],
            'directeur_collection' => ['level' => 5, 'display_name' => 'Directeur de Collection'],
            'editeur_associe' => ['level' => 4, 'display_name' => 'Éditeur Associé'],
            'reviewer' => ['level' => 3, 'display_name' => 'Relecteur'],
            'journaliste' => ['level' => 2, 'display_name' => 'Journaliste'],
            'lecteur' => ['level' => 1, 'display_name' => 'Lecteur'],
        ];

        foreach ($rolesConfig as $roleName => $roleData) {
            // Crée ou retrouve le rôle
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'sanctum',
            ], [
                'display_name' => $roleData['display_name'],
                'level' => $roleData['level'],
                'description' => "Rôle {$roleData['display_name']}",
            ]);

            // ========================================
            // 3. Attribuer les permissions selon la configuration
            // ========================================
            // Récupère les permissions pour ce rôle depuis la config
            $permissions = $config['role_permissions'][$roleName] ?? [];

            if ($permissions === ['*']) {
                // Super admin : obtient TOUTES les permissions
                $role->syncPermissions(Permission::all());
            } elseif (!empty($permissions)) {
                // Les autres rôles obtiennent les permissions spécifiques
                $role->syncPermissions($permissions);
            } else {
                // Pas de permissions = aucune permission supplémentaire
                $role->syncPermissions([]);
            }
        }
    }
}
