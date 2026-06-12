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
use Illuminate\Support\Str;

/**
 * Tests de gestion des maisons d'édition.
 *
 * @package Tests\Feature\Modules\Maison
 */
class MaisonManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminPlateforme;
    protected User $editeurEnChef;
    protected Maison $maison;

    protected function setUp(): void
    {
        parent::setUp();

        // Configuration du guard pour les tests
        config(['auth.defaults.guard' => 'sanctum']);

        // Création des permissions nécessaires
        $this->setupPermissions();

        // Création des utilisateurs
        $this->superAdmin = $this->createUserWithRole('super_admin', 'Super', 'Admin');
        $this->adminPlateforme = $this->createUserWithRole('admin_plateforme', 'Admin', 'Plateforme');
        $this->editeurEnChef = $this->createUserWithRole('editeur_en_chef', 'Editeur', 'Chef');

        // Création d'une maison pour les tests
        $this->maison = $this->createMaison();
    }

    /**
     * Configure les permissions nécessaires pour les tests.
     */
    protected function setupPermissions(): void
    {
        Permission::firstOrCreate(['name' => 'maison.creer', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'maison.modifier', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'maison.voir', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'maison.lister', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'maison.valider', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'maison.suspendre', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'maison.activer', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'maison.rejeter', 'guard_name' => 'sanctum']);
        Permission::firstOrCreate(['name' => 'maison.supprimer', 'guard_name' => 'sanctum']);

        // Créer le rôle lecteur qui est utilisé par UserObserver
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'sanctum']);

        // Assigner les permissions aux rôles
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo(Permission::all());
        }

        $adminRole = Role::where('name', 'admin_plateforme')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'maison.creer',
                'maison.modifier',
                'maison.voir',
                'maison.lister',
                'maison.valider',
                'maison.suspendre',
                'maison.activer',
                'maison.rejeter',
            ]);
        }
    }

    /**
     * Crée un utilisateur avec un rôle spécifique.
     */
    protected function createUserWithRole(string $roleName, string $prenom, string $nom): User
    {
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);

        $user = User::firstOrCreate(
            ['email' => "{$roleName}@example.com"],
            [
                'nom' => $nom,
                'prenom' => $prenom,
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );

        $user->assignRole($role);

        return $user;
    }

    /**
     * Crée une maison pour les tests.
     */
    protected function createMaison(array $attributes = []): Maison
    {
        $defaults = [
            'nom' => 'Maison Test ' . Str::random(5),
            'description' => 'Description de la maison test',
            'email_contact' => 'contact' . Str::random(5) . '@maisontest.com',
            'statut' => 'en_attente',
        ];

        return Maison::create(array_merge($defaults, $attributes));
    }

    /**
     * Lie un utilisateur à une maison avec un rôle.
     */
    protected function attachUserToMaison(User $user, Maison $maison, string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();

        MembreMaison::create([
            'maison_id' => $maison->id,
            'utilisateur_id' => $user->id,
            'role_id' => $role?->id,
            'est_actif' => true,
            'a_rejoint_le' => now(),
        ]);
    }

    // =========================================================================
    // TESTS DE CRÉATION DE MAISON
    // =========================================================================

    /**
     * Test : Le Super Admin peut créer une maison.
     */
    public function test_super_admin_can_create_maison(): void
    {
        $this->actingAs($this->superAdmin);

        $data = [
            'nom' => 'Nouvelle Maison',
            'description' => 'Description de la nouvelle maison',
            'email_contact' => 'contact@nouvellemaison.com',
        ];

        $response = $this->postJson('/api/maison/admin/maisons', $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nom', 'Nouvelle Maison')
            ->assertJsonPath('data.email_contact', 'contact@nouvellemaison.com')
            ->assertJsonPath('data.statut', 'en_attente');

        $this->assertDatabaseHas('maisons', [
            'nom' => 'Nouvelle Maison',
            'email_contact' => 'contact@nouvellemaison.com',
        ]);
    }

    /**
     * Test : L'Admin Plateforme peut créer une maison.
     */
    public function test_admin_plateforme_can_create_maison(): void
    {
        $this->actingAs($this->adminPlateforme);

        $data = [
            'nom' => 'Maison Admin',
            'description' => 'Créée par admin plateforme',
            'email_contact' => 'admin@maison.com',
        ];

        $response = $this->postJson('/api/maison/admin/maisons', $data);

        $response->assertStatus(201);
    }

    /**
     * Test : Un utilisateur non autorisé ne peut pas créer une maison.
     */
    public function test_unauthorized_user_cannot_create_maison(): void
    {
        $this->actingAs($this->editeurEnChef);

        $data = [
            'nom' => 'Maison Non Autorisée',
            'email_contact' => 'nonautorise@maison.com',
        ];

        $response = $this->postJson('/api/maison/admin/maisons', $data);

        $response->assertStatus(403);
    }

    /**
     * Test : La création d'une maison avec un nom existant échoue.
     */
    public function test_create_maison_with_duplicate_name_fails(): void
    {
        $this->actingAs($this->superAdmin);

        $data = [
            'nom' => $this->maison->nom,
            'email_contact' => 'duplicate@test.com',
        ];

        $response = $this->postJson('/api/maison/admin/maisons', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nom']);
    }

    /**
     * Test : La création d'une maison avec email invalide échoue.
     */
    public function test_create_maison_with_invalid_email_fails(): void
    {
        $this->actingAs($this->superAdmin);

        $data = [
            'nom' => 'Maison Email Invalide',
            'email_contact' => 'email-invalide',
        ];

        $response = $this->postJson('/api/maison/admin/maisons', $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email_contact']);
    }

    /**
     * Test : La création d'une maison génère automatiquement un slug.
     */
    public function test_create_maison_generates_slug_automatically(): void
    {
        $this->actingAs($this->superAdmin);

        $data = [
            'nom' => 'Une Maison Avec Espaces',
            'email_contact' => 'slug@test.com',
        ];

        $response = $this->postJson('/api/maison/admin/maisons', $data);

        $expectedSlug = 'une-maison-avec-espaces';
        $response->assertStatus(201)
            ->assertJsonPath('data.slug', $expectedSlug);
    }

    // =========================================================================
    // TESTS DE LECTURE/LISTAGE DE MAISON
    // =========================================================================

    /**
     * Test : L'Admin Plateforme peut lister toutes les maisons.
     */
    public function test_admin_plateforme_can_list_all_maisons(): void
    {
        $this->actingAs($this->adminPlateforme);

        // Créer plusieurs maisons
        $this->createMaison(['nom' => 'Maison A', 'email_contact' => 'a@test.com']);
        $this->createMaison(['nom' => 'Maison B', 'email_contact' => 'b@test.com']);

        $response = $this->getJson('/api/maison/admin/maisons');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'nom', 'slug', 'email_contact', 'statut']
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                'links' => ['first', 'last', 'prev', 'next']
            ]);
    }

    /**
     * Test : L'Admin Plateforme peut filtrer les maisons par statut.
     */
    public function test_admin_can_filter_maisons_by_statut(): void
    {
        $this->actingAs($this->adminPlateforme);

        $this->createMaison(['nom' => 'Maison Active', 'statut' => 'active']);
        $this->createMaison(['nom' => 'Maison Suspendue', 'statut' => 'suspendue']);

        $response = $this->getJson('/api/maison/admin/maisons?statut=active');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Maison Active', $response->json('data.0.nom'));
    }

    /**
     * Test : L'Admin Plateforme peut rechercher des maisons.
     */
    public function test_admin_can_search_maisons(): void
    {
        $this->actingAs($this->adminPlateforme);

        $this->createMaison(['nom' => 'Recherche Unique', 'email_contact' => 'unique@test.com']);

        $response = $this->getJson('/api/maison/admin/maisons?search=Recherche');

        $response->assertStatus(200);
        $this->assertEquals('Recherche Unique', $response->json('data.0.nom'));
    }

    /**
     * Test : L'Admin Plateforme peut voir les maisons en attente.
     */
    public function test_admin_can_view_pending_maisons(): void
    {
        $this->actingAs($this->adminPlateforme);

        $this->createMaison(['nom' => 'En Attente 1', 'statut' => 'en_attente']);
        $this->createMaison(['nom' => 'En Attente 2', 'statut' => 'en_attente']);
        $this->createMaison(['nom' => 'Active', 'statut' => 'active']);

        $response = $this->getJson('/api/maison/admin/maisons/en-attente');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data')); // 1 de setUp + 2 créées ici
    }

    /**
     * Test : Un membre peut voir sa propre maison.
     */
    public function test_member_can_view_own_maison(): void
    {
        $this->actingAs($this->editeurEnChef);
        $this->attachUserToMaison($this->editeurEnChef, $this->maison, 'editeur_en_chef');

        $response = $this->getJson('/api/maison/gerer');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->maison->id)
            ->assertJsonPath('data.nom', $this->maison->nom);
    }

    /**
     * Test : Un utilisateur sans maison ne peut pas voir de maison.
     */
    public function test_user_without_maison_cannot_view_maison(): void
    {
        $this->actingAs($this->editeurEnChef);

        $response = $this->getJson('/api/maison/gerer');

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Vous n\'appartenez à aucune maison d\'édition.');
    }

    // =========================================================================
    // TESTS DE MISE À JOUR DE MAISON
    // =========================================================================

    /**
     * Test : L'Éditeur en Chef peut modifier sa maison.
     */
    public function test_editeur_en_chef_can_update_own_maison(): void
    {
        $this->actingAs($this->editeurEnChef);
        $this->attachUserToMaison($this->editeurEnChef, $this->maison, 'editeur_en_chef');

        $data = [
            'nom' => 'Maison Modifiée',
            'description' => 'Nouvelle description',
        ];

        $response = $this->putJson('/api/maison/gerer', $data);

        $response->assertStatus(200)
            ->assertJsonPath('data.nom', 'Maison Modifiée')
            ->assertJsonPath('data.description', 'Nouvelle description');
    }

    /**
     * Test : Un membre non Éditeur en Chef ne peut pas modifier la maison.
     */
    public function test_non_editeur_chef_cannot_update_maison(): void
    {
        $journaliste = $this->createUserWithRole('journaliste', 'Journaliste', 'Test');
        $this->attachUserToMaison($journaliste, $this->maison, 'journaliste');

        $this->actingAs($journaliste);

        $data = ['nom' => 'Tentative Modification'];

        $response = $this->putJson('/api/maison/gerer', $data);

        $response->assertStatus(403);
    }

    /**
     * Test : La mise à jour avec un nom déjà existant échoue.
     */
    public function test_update_with_duplicate_name_fails(): void
    {
        $this->actingAs($this->superAdmin);

        $otherMaison = $this->createMaison(['nom' => 'Autre Maison', 'email_contact' => 'autre@test.com']);

        $data = ['nom' => $otherMaison->nom];

        $response = $this->putJson("/api/maison/admin/maisons/{$this->maison->id}", $data);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nom']);
    }

    // =========================================================================
    // TESTS DE VALIDATION DE MAISON (ADMIN)
    // =========================================================================

    /**
     * Test : L'Admin Plateforme peut valider une maison en attente.
     */
    public function test_admin_can_validate_pending_maison(): void
    {
        $this->actingAs($this->adminPlateforme);

        $pendingMaison = $this->createMaison(['nom' => 'À Valider', 'statut' => 'en_attente']);

        $response = $this->postJson("/api/maison/admin/maisons/{$pendingMaison->id}/valider");

        $response->assertStatus(200)
            ->assertJsonPath('data.statut', 'active')
            ->assertJsonPath('data.est_validee', true);
    }

    /**
     * Test : L'Admin Plateforme ne peut pas valider une maison déjà active.
     */
    public function test_admin_cannot_validate_already_active_maison(): void
    {
        $this->actingAs($this->adminPlateforme);

        $activeMaison = $this->createMaison(['nom' => 'Déjà Active', 'statut' => 'active']);

        $response = $this->postJson("/api/maison/admin/maisons/{$activeMaison->id}/valider");

        $response->assertStatus(422);
    }

    /**
     * Test : L'Admin Plateforme peut rejeter une maison en attente.
     */
    public function test_admin_can_reject_pending_maison(): void
    {
        $this->actingAs($this->adminPlateforme);

        $pendingMaison = $this->createMaison(['nom' => 'À Rejeter', 'statut' => 'en_attente']);

        $response = $this->postJson("/api/maison/admin/maisons/{$pendingMaison->id}/rejeter");

        $response->assertStatus(200)
            ->assertJsonPath('data.statut', 'rejetee');
    }

    /**
     * Test : L'Admin Plateforme peut suspendre une maison active.
     */
    public function test_admin_can_suspend_active_maison(): void
    {
        $this->actingAs($this->adminPlateforme);

        $activeMaison = $this->createMaison(['nom' => 'À Suspendre', 'statut' => 'active']);

        $response = $this->postJson("/api/maison/admin/maisons/{$activeMaison->id}/suspendre");

        $response->assertStatus(200)
            ->assertJsonPath('data.statut', 'suspendue');
    }

    /**
     * Test : L'Admin Plateforme peut réactiver une maison suspendue.
     */
    public function test_admin_can_reactivate_suspended_maison(): void
    {
        $this->actingAs($this->adminPlateforme);

        $suspendedMaison = $this->createMaison(['nom' => 'À Réactiver', 'statut' => 'suspendue']);

        $response = $this->postJson("/api/maison/admin/maisons/{$suspendedMaison->id}/activer");

        $response->assertStatus(200)
            ->assertJsonPath('data.statut', 'active');
    }

    // =========================================================================
    // TESTS DE STATISTIQUES
    // =========================================================================

    /**
     * Test : L'Admin Plateforme peut voir les statistiques globales.
     */
    public function test_admin_can_view_global_stats(): void
    {
        $this->actingAs($this->adminPlateforme);

        $this->createMaison(['nom' => 'Stats 1', 'statut' => 'active']);
        $this->createMaison(['nom' => 'Stats 2', 'statut' => 'en_attente']);
        $this->createMaison(['nom' => 'Stats 3', 'statut' => 'suspendue']);

        $response = $this->getJson('/api/maison/admin/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['total', 'en_attente', 'active', 'suspendue', 'rejetee']
            ]);
    }

    /**
     * Test : Un membre peut voir les statistiques de sa maison.
     */
    public function test_member_can_view_own_maison_stats(): void
    {
        $this->actingAs($this->editeurEnChef);
        $this->attachUserToMaison($this->editeurEnChef, $this->maison, 'editeur_en_chef');

        $response = $this->getJson('/api/maison/gerer/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_membres',
                    'total_articles',
                    'articles_publies',
                    'articles_en_review',
                    'articles_soumis',
                    'articles_brouillon'
                ]
            ]);
    }

    // =========================================================================
    // TESTS DE SLUG
    // =========================================================================

    /**
     * Test : La génération de slug gère les doublons.
     */
    public function test_slug_generation_handles_duplicates(): void
    {
        $this->actingAs($this->superAdmin);

        // Noms différents pour passer la validation unique:nom
        // mais produisant le même slug de base
        $data1 = ['nom' => 'Test-Slug', 'email_contact' => 'slug1@test.com'];
        $data2 = ['nom' => 'Test Slug', 'email_contact' => 'slug2@test.com'];

        $response1 = $this->postJson('/api/maison/admin/maisons', $data1);
        $response2 = $this->postJson('/api/maison/admin/maisons', $data2);

        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $response1->assertJsonPath('data.slug', 'test-slug');
        $response2->assertJsonPath('data.slug', 'test-slug-1');
    }

    // =========================================================================
    // TESTS DE SUPPRESSION
    // =========================================================================

    /**
     * Test : Le Super Admin peut supprimer une maison sans articles.
     */
    public function test_super_admin_can_delete_maison_without_articles(): void
    {
        $this->actingAs($this->superAdmin);

        $maisonASupprimer = $this->createMaison(['nom' => 'À Supprimer', 'email_contact' => 'delete@test.com']);

        $response = $this->deleteJson("/api/maison/admin/maisons/{$maisonASupprimer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test : L'Admin Plateforme ne peut pas supprimer une maison.
     */
    public function test_admin_plateforme_cannot_delete_maison(): void
    {
        $this->actingAs($this->adminPlateforme);

        $maisonASupprimer = $this->createMaison(['nom' => 'À Supprimer Admin', 'email_contact' => 'delete2@test.com']);

        $response = $this->deleteJson("/api/maison/admin/maisons/{$maisonASupprimer->id}");

        $response->assertStatus(403);
    }
}
