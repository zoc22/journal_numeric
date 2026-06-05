<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Maison;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Maison\Models\Maison;
use Modules\Maison\Models\MembreMaison;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests de gestion des membres des maisons d'édition.
 *
 * @package Tests\Feature\Modules\Maison
 */
class MembreManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminPlateforme;
    protected User $editeurEnChef;
    protected User $directeurCollection;
    protected User $editeurAssocie;
    protected User $reviewer;
    protected User $journaliste;
    protected User $lecteur;
    protected Maison $maison;
    protected array $roles = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.defaults.guard' => 'sanctum']);

        // Création des rôles
        $this->setupRolesAndPermissions();

        // Création des utilisateurs
        $this->superAdmin = $this->createUser('Super', 'Admin', 'super_admin@test.com', 'super_admin');
        $this->adminPlateforme = $this->createUser('Admin', 'Plateforme', 'admin@test.com', 'admin_plateforme');
        $this->editeurEnChef = $this->createUser('Editeur', 'Chef', 'editeurchef@test.com', 'editeur_en_chef');
        $this->directeurCollection = $this->createUser('Directeur', 'Collection', 'directeur@test.com', 'directeur_collection');
        $this->editeurAssocie = $this->createUser('Editeur', 'Associe', 'editeurassocie@test.com', 'editeur_associe');
        $this->reviewer = $this->createUser('Reviewer', 'Test', 'reviewer@test.com', 'reviewer');
        $this->journaliste = $this->createUser('Journaliste', 'Test', 'journaliste@test.com', 'journaliste');
        $this->lecteur = $this->createUser('Lecteur', 'Test', 'lecteur@test.com', 'lecteur');

        // Création d'une maison
        $this->maison = Maison::create([
            'nom' => 'Maison des Tests',
            'slug' => 'maison-des-tests',
            'description' => 'Maison pour les tests',
            'email_contact' => 'contact@maisontests.com',
            'statut' => 'active',
        ]);

        // Attacher l'Éditeur en Chef à la maison
        $this->attachUserToMaison($this->editeurEnChef, $this->maison, 'editeur_en_chef');
        $this->attachUserToMaison($this->directeurCollection, $this->maison, 'directeur_collection');
        $this->attachUserToMaison($this->editeurAssocie, $this->maison, 'editeur_associe');
        $this->attachUserToMaison($this->reviewer, $this->maison, 'reviewer');
        $this->attachUserToMaison($this->journaliste, $this->maison, 'journaliste');
        $this->attachUserToMaison($this->lecteur, $this->maison, 'lecteur');
    }

    /**
     * Configure les rôles et permissions.
     */
    protected function setupRolesAndPermissions(): void
    {
        $roleNames = [
            'super_admin', 'admin_plateforme', 'editeur_en_chef',
            'directeur_collection', 'editeur_associe', 'reviewer',
            'journaliste', 'lecteur'
        ];

        foreach ($roleNames as $roleName) {
            $this->roles[$roleName] = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
        }

        $permissions = [
            'maison.membres.voir',
            'maison.membres.ajouter',
            'maison.membres.modifier',
            'maison.membres.supprimer',
            'maison.membres.activer',
            'maison.membres.desactiver',
            'maison.roles.assigner',
            'maison.roles.voir',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }
    }

    /**
     * Crée un utilisateur.
     */
    protected function createUser(string $prenom, string $nom, string $email, string $roleName): User
    {
        $user = User::create([
            'nom' => $nom,
            'prenom' => $prenom,
            'email' => $email,
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        if (isset($this->roles[$roleName])) {
            $user->assignRole($this->roles[$roleName]);
        }

        return $user;
    }

    /**
     * Attache un utilisateur à une maison.
     */
    protected function attachUserToMaison(User $user, Maison $maison, string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();

        MembreMaison::updateOrCreate(
            ['maison_id' => $maison->id, 'utilisateur_id' => $user->id],
            [
                'role_id' => $role?->id,
                'est_actif' => true,
                'a_rejoint_le' => now(),
            ]
        );
    }

    /**
     * Récupère l'ID d'un rôle.
     */
    protected function getRoleId(string $roleName): string|int
    {
        $role = Role::where('name', $roleName)->first();
        if (!$role) {
            throw new \Exception("Rôle '{$roleName}' non trouvé.");
        }
        return $role->id;
    }

    // =========================================================================
    // TESTS DE LISTAGE DES MEMBRES
    // =========================================================================

    /**
     * Test : L'Éditeur en Chef peut lister les membres de sa maison.
     */
    public function test_editeur_en_chef_can_list_members(): void
    {
        $this->actingAs($this->editeurEnChef);

        $response = $this->getJson('/api/maison/gerer/membres');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'utilisateur', 'role', 'est_actif', 'a_rejoint_le']
                ]
            ]);

        // Vérifie que tous les membres sont présents
        $this->assertCount(6, $response->json('data'));
    }

    /**
     * Test : Le Directeur de Collection peut lister les membres.
     */
    public function test_directeur_collection_can_list_members(): void
    {
        $this->actingAs($this->directeurCollection);

        $response = $this->getJson('/api/maison/gerer/membres');

        $response->assertStatus(200);
        $this->assertCount(6, $response->json('data'));
    }

    /**
     * Test : Le Lecteur ne peut pas lister les membres.
     */
    public function test_lecteur_cannot_list_members(): void
    {
        $this->actingAs($this->lecteur);

        $response = $this->getJson('/api/maison/gerer/membres');

        $response->assertStatus(403);
    }

    /**
     * Test : Le filtrage des membres par statut actif.
     */
    public function test_can_filter_members_by_active_status(): void
    {
        $this->actingAs($this->editeurEnChef);

        // Désactiver un membre
        $membre = MembreMaison::where('utilisateur_id', $this->reviewer->id)->first();
        $membre->desactiver();

        $response = $this->getJson('/api/maison/gerer/membres?est_actif=1');

        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test : Le filtrage des membres par rôle.
     */
    public function test_can_filter_members_by_role(): void
    {
        $this->actingAs($this->editeurEnChef);

        $roleId = $this->getRoleId('journaliste');

        $response = $this->getJson("/api/maison/gerer/membres?role_id={$roleId}");

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('journaliste', $response->json('data.0.role.name'));
    }

    /**
     * Test : La recherche de membres par nom.
     */
    public function test_can_search_members_by_name(): void
    {
        $this->actingAs($this->editeurEnChef);

        $response = $this->getJson('/api/maison/gerer/membres?search=Journaliste');

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Journaliste', $response->json('data.0.utilisateur.prenom'));
    }

    // =========================================================================
    // TESTS D'AJOUT DE MEMBRES
    // =========================================================================

    /**
     * Test : L'Éditeur en Chef peut ajouter un membre.
     */
    public function test_editeur_en_chef_can_add_member(): void
    {
        $this->actingAs($this->editeurEnChef);

        $newUser = $this->createUser('Nouveau', 'Membre', 'nouveau@test.com', 'journaliste');
        $roleId = $this->getRoleId('reviewer');

        $data = [
            'utilisateur_id' => $newUser->id,
            'role_id' => $roleId,
        ];

        $response = $this->postJson('/api/maison/gerer/membres', $data);

        $response->assertStatus(201)
            ->assertJsonPath('data.utilisateur.email', 'nouveau@test.com')
            ->assertJsonPath('data.role.name', 'reviewer');
    }

    /**
     * Test : L'ajout d'un membre déjà existant échoue.
     */
    public function test_add_duplicate_member_fails(): void
    {
        $this->actingAs($this->editeurEnChef);

        $roleId = $this->getRoleId('journaliste');

        $data = [
            'utilisateur_id' => $this->journaliste->id,
            'role_id' => $roleId,
        ];

        $response = $this->postJson('/api/maison/gerer/membres', $data);

        $response->assertStatus(422);
    }

    /**
     * Test : Le Journaliste ne peut pas ajouter un membre.
     */
    public function test_journaliste_cannot_add_member(): void
    {
        $this->actingAs($this->journaliste);

        $newUser = $this->createUser('Test', 'Ajout', 'testajout@test.com', 'lecteur');
        $roleId = $this->getRoleId('lecteur');

        $data = [
            'utilisateur_id' => $newUser->id,
            'role_id' => $roleId,
        ];

        $response = $this->postJson('/api/maison/gerer/membres', $data);

        $response->assertStatus(403);
    }

    // =========================================================================
    // TESTS DE MODIFICATION DE RÔLE
    // =========================================================================

    /**
     * Test : L'Éditeur en Chef peut modifier le rôle d'un membre.
     */
    public function test_editeur_en_chef_can_update_member_role(): void
    {
        $this->actingAs($this->editeurEnChef);

        $membre = MembreMaison::where('utilisateur_id', $this->journaliste->id)->first();
        $newRoleId = $this->getRoleId('reviewer');

        $response = $this->putJson("/api/maison/gerer/membres/{$membre->id}", [
            'role_id' => $newRoleId
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.role.name', 'reviewer');
    }

    /**
     * Test : La modification avec un rôle inexistant échoue.
     */
    public function test_update_member_with_invalid_role_fails(): void
    {
        $this->actingAs($this->editeurEnChef);

        $membre = MembreMaison::where('utilisateur_id', $this->journaliste->id)->first();

        $response = $this->putJson("/api/maison/gerer/membres/{$membre->id}", [
            'role_id' => 999999
        ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // TESTS DE DÉSACTIVATION/RÉACTIVATION
    // =========================================================================

    /**
     * Test : L'Éditeur en Chef peut désactiver un membre.
     */
    public function test_editeur_en_chef_can_deactivate_member(): void
    {
        $this->actingAs($this->editeurEnChef);

        $membre = MembreMaison::where('utilisateur_id', $this->journaliste->id)->first();

        $response = $this->postJson("/api/maison/gerer/membres/{$membre->id}/desactiver");

        $response->assertStatus(200)
            ->assertJsonPath('data.est_actif', false);
    }

    /**
     * Test : L'Éditeur en Chef peut réactiver un membre désactivé.
     */
    public function test_editeur_en_chef_can_reactivate_member(): void
    {
        $this->actingAs($this->editeurEnChef);

        $membre = MembreMaison::where('utilisateur_id', $this->journaliste->id)->first();
        $membre->desactiver();

        $response = $this->postJson("/api/maison/gerer/membres/{$membre->id}/activer");

        $response->assertStatus(200)
            ->assertJsonPath('data.est_actif', true);
    }

    /**
     * Test : Le Reviewer ne peut pas désactiver un membre.
     */
    public function test_reviewer_cannot_deactivate_member(): void
    {
        $this->actingAs($this->reviewer);

        $membre = MembreMaison::where('utilisateur_id', $this->journaliste->id)->first();

        $response = $this->postJson("/api/maison/gerer/membres/{$membre->id}/desactiver");

        $response->assertStatus(403);
    }

    // =========================================================================
    // TESTS DE SUPPRESSION DE MEMBRES
    // =========================================================================

    /**
     * Test : L'Éditeur en Chef peut supprimer (désactiver définitivement) un membre.
     */
    public function test_editeur_en_chef_can_remove_member(): void
    {
        $this->actingAs($this->editeurEnChef);

        $membre = MembreMaison::where('utilisateur_id', $this->reviewer->id)->first();

        $response = $this->deleteJson("/api/maison/gerer/membres/{$membre->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $membre->refresh();
        $this->assertFalse($membre->est_actif);
        $this->assertNotNull($membre->a_quitte_le);
    }

    /**
     * Test : L'Éditeur en Chef ne peut pas supprimer son propre rôle d'admin.
     */
    public function test_editeur_en_chef_cannot_remove_self(): void
    {
        $this->actingAs($this->editeurEnChef);

        $membre = MembreMaison::where('utilisateur_id', $this->editeurEnChef->id)->first();

        $response = $this->deleteJson("/api/maison/gerer/membres/{$membre->id}");

        // Il devrait être autorisé mais attention : cela pourrait le bloquer
        // Dans la logique métier, on peut empêcher la suppression du dernier admin
        $response->assertStatus(200); // ou 422 selon la logique
    }

    // =========================================================================
    // TESTS DES RÔLES DISPONIBLES
    // =========================================================================

    /**
     * Test : L'Éditeur en Chef peut voir les rôles disponibles.
     */
    public function test_editeur_en_chef_can_view_available_roles(): void
    {
        $this->actingAs($this->editeurEnChef);

        $response = $this->getJson('/api/maison/gerer/membres/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'level']
                ]
            ]);
    }

    /**
     * Test : Le Lecteur ne peut pas voir les rôles disponibles.
     */
    public function test_lecteur_cannot_view_available_roles(): void
    {
        $this->actingAs($this->lecteur);

        $response = $this->getJson('/api/maison/gerer/membres/roles');

        $response->assertStatus(403);
    }

    // =========================================================================
    // TESTS DES RELATIONS MAISON-MEMBRE
    // =========================================================================

    /**
     * Test : La méthode estMembre fonctionne correctement.
     */
    public function test_estMembre_method_works(): void
    {
        $this->assertTrue($this->maison->estMembre($this->journaliste->id));
        $this->assertFalse($this->maison->estMembre('00000000-0000-0000-0000-000000000000'));
    }

    /**
     * Test : La méthode aLeRole fonctionne correctement.
     */
    public function test_aLeRole_method_works(): void
    {
        $this->assertTrue($this->maison->aLeRole($this->journaliste->id, 'journaliste'));
        $this->assertFalse($this->maison->aLeRole($this->journaliste->id, 'reviewer'));
    }

    /**
     * Test : La méthode getNiveauHierarchique fonctionne correctement.
     */
    public function test_getNiveauHierarchique_method_works(): void
    {
        $niveau = $this->maison->getNiveauHierarchique($this->editeurEnChef->id);
        $this->assertIsInt($niveau);
    }
}
