<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Maison;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Maison\Models\Maison;
use Modules\Maison\Models\MembreMaison;
use Modules\Maison\Permissions\MaisonPermissions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests des permissions du module Maison.
 *
 * @package Tests\Feature\Modules\Maison
 */
class PermissionMaisonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.defaults.guard' => 'sanctum']);

        // Configuration des permissions
        MaisonPermissions::run();
    }

    /**
     * Crée un utilisateur avec un rôle.
     */
    protected function createUserWithRole(string $roleName, string $email): User
    {
        $user = User::create([
            'nom' => 'Test',
            'prenom' => 'User',
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $user->assignRole($roleName);

        return $user;
    }

    /**
     * Crée une maison et attache un utilisateur.
     */
    protected function createMaisonWithMember(User $user, string $roleName): Maison
    {
        $maison = Maison::create([
            'nom' => 'Maison Test Permissions',
            'slug' => 'maison-test-permissions',
            'email_contact' => 'permissions@test.com',
            'statut' => 'active',
        ]);

        $role = Role::where('name', $roleName)->first();

        MembreMaison::create([
            'maison_id' => $maison->id,
            'utilisateur_id' => $user->id,
            'role_id' => $role?->id,
            'est_actif' => true,
            'a_rejoint_le' => now(),
        ]);

        return $maison;
    }

    // =========================================================================
    // TESTS DE LISTAGE DES PERMISSIONS
    // =========================================================================

    /**
     * Test : La constante PERMISSIONS contient toutes les permissions.
     */
    public function test_permissions_constant_contains_all_permissions(): void
    {
        $expectedPermissions = [
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
            'maison.membres.activer',
            'maison.membres.desactiver',
            'maison.roles.voir',
            'maison.roles.assigner',
            'maison.categories.gerer',
            'maison.appels.creer',
            'maison.appels.modifier',
            'maison.appels.supprimer',
            'maison.appels.publier',
        ];

        $this->assertCount(count($expectedPermissions), MaisonPermissions::PERMISSIONS);

        foreach ($expectedPermissions as $permission) {
            $this->assertArrayHasKey($permission, MaisonPermissions::PERMISSIONS);
        }
    }

    // =========================================================================
    // TESTS DES PERMISSIONS PAR RÔLE
    // =========================================================================

    /**
     * Test : Le Super Admin a toutes les permissions.
     */
    public function test_super_admin_has_all_permissions(): void
    {
        $superAdmin = $this->createUserWithRole('super_admin', 'super@test.com');

        $this->assertTrue($superAdmin->hasPermissionTo('maison.creer'));
        $this->assertTrue($superAdmin->hasPermissionTo('maison.modifier'));
        $this->assertTrue($superAdmin->hasPermissionTo('maison.supprimer'));
        $this->assertTrue($superAdmin->hasPermissionTo('maison.valider'));
        $this->assertTrue($superAdmin->hasPermissionTo('maison.membres.ajouter'));
    }

    /**
     * Test : L'Admin Plateforme a les permissions admin.
     */
    public function test_admin_plateforme_has_admin_permissions(): void
    {
        $adminPlateforme = $this->createUserWithRole('admin_plateforme', 'admin@test.com');

        $this->assertTrue($adminPlateforme->hasPermissionTo('maison.creer'));
        $this->assertTrue($adminPlateforme->hasPermissionTo('maison.modifier'));
        $this->assertTrue($adminPlateforme->hasPermissionTo('maison.valider'));
        $this->assertTrue($adminPlateforme->hasPermissionTo('maison.suspendre'));

        // L'admin plateforme ne peut pas supprimer une maison
        $this->assertFalse($adminPlateforme->hasPermissionTo('maison.supprimer'));
    }

    /**
     * Test : L'Éditeur en Chef a les permissions de gestion de maison.
     */
    public function test_editeur_en_chef_has_house_management_permissions(): void
    {
        $editeurChef = $this->createUserWithRole('editeur_en_chef', 'editeur@test.com');
        $maison = $this->createMaisonWithMember($editeurChef, 'editeur_en_chef');

        $this->assertTrue($editeurChef->hasPermissionTo('maison.modifier'));
        $this->assertTrue($editeurChef->hasPermissionTo('maison.membres.ajouter'));
        $this->assertTrue($editeurChef->hasPermissionTo('maison.membres.modifier'));
        $this->assertTrue($editeurChef->hasPermissionTo('maison.appels.creer'));

        // L'Éditeur en Chef ne peut pas valider une maison
        $this->assertFalse($editeurChef->hasPermissionTo('maison.valider'));
    }

    /**
     * Test : Le Directeur de Collection a les permissions limitées.
     */
    public function test_directeur_collection_has_limited_permissions(): void
    {
        $directeur = $this->createUserWithRole('directeur_collection', 'directeur@test.com');

        $this->assertTrue($directeur->hasPermissionTo('maison.voir'));
        $this->assertTrue($directeur->hasPermissionTo('maison.categories.gerer'));

        $this->assertFalse($directeur->hasPermissionTo('maison.membres.ajouter'));
        $this->assertFalse($directeur->hasPermissionTo('maison.appels.creer'));
    }

    /**
     * Test : Le Journaliste a très peu de permissions.
     */
    public function test_journaliste_has_very_few_permissions(): void
    {
        $journaliste = $this->createUserWithRole('journaliste', 'journaliste@test.com');

        $this->assertTrue($journaliste->hasPermissionTo('maison.voir'));

        $this->assertFalse($journaliste->hasPermissionTo('maison.modifier'));
        $this->assertFalse($journaliste->hasPermissionTo('maison.membres.ajouter'));
    }

    /**
     * Test : Le Lecteur n'a aucune permission de gestion.
     */
    public function test_lecteur_has_no_management_permissions(): void
    {
        $lecteur = $this->createUserWithRole('lecteur', 'lecteur@test.com');

        $this->assertFalse($lecteur->hasPermissionTo('maison.modifier'));
        $this->assertFalse($lecteur->hasPermissionTo('maison.membres.ajouter'));
        $this->assertFalse($lecteur->hasPermissionTo('maison.appels.creer'));
    }

    // =========================================================================
    // TESTS DE SYNCHRONISATION DES PERMISSIONS
    // =========================================================================

    /**
     * Test : La synchronisation des permissions crée toutes les permissions.
     */
    public function test_sync_permissions_creates_all_permissions(): void
    {
        // Supprimer les permissions existantes
        Permission::where('guard_name', 'sanctum')->delete();

        // Recréer les permissions
        MaisonPermissions::syncPermissions();

        $permissionCount = Permission::where('guard_name', 'sanctum')->count();
        $this->assertGreaterThan(0, $permissionCount);
    }

    /**
     * Test : L'assignation des permissions aux rôles fonctionne.
     */
    public function test_assign_permissions_to_roles_works(): void
    {
        // Recréer les rôles et permissions
        MaisonPermissions::run();

        // Vérifier que le rôle super_admin a les permissions
        $superAdminRole = Role::where('name', 'super_admin')->first();
        $this->assertNotNull($superAdminRole);
        $this->assertGreaterThan(0, $superAdminRole->permissions->count());
    }
}
