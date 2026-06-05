<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Maison;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Maison\Models\Maison;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests des fonctionnalités admin pour les maisons d'édition.
 *
 * @package Tests\Feature\Modules\Maison
 */
class AdminMaisonTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $adminPlateforme;
    protected User $simpleUser;
    protected Maison $maison;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.defaults.guard' => 'sanctum']);

        // Création des rôles
        $this->setupRoles();

        // Création des utilisateurs
        $this->superAdmin = $this->createUser('Super', 'Admin', 'super@admin.com', 'super_admin');
        $this->adminPlateforme = $this->createUser('Admin', 'Plateforme', 'admin@plateforme.com', 'admin_plateforme');
        $this->simpleUser = $this->createUser('Simple', 'User', 'simple@user.com', 'lecteur');

        // Création d'une maison
        $this->maison = Maison::create([
            'nom' => 'Maison Admin Test',
            'slug' => 'maison-admin-test',
            'description' => 'Description',
            'email_contact' => 'contact@admintest.com',
            'statut' => 'en_attente',
        ]);
    }

    /**
     * Configure les rôles.
     */
    protected function setupRoles(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'admin_plateforme', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'sanctum']);
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

        $user->assignRole($roleName);

        return $user;
    }

    // =========================================================================
    // TESTS D'AUTORISATION D'ACCÈS
    // =========================================================================

    /**
     * Test : Le Super Admin a accès à toutes les routes admin.
     */
    public function test_super_admin_has_access_to_admin_routes(): void
    {
        $this->actingAs($this->superAdmin);

        $routes = [
            'get' => ['/api/maison/admin/maisons', '/api/maison/admin/maisons/en-attente', '/api/maison/admin/stats'],
            'post' => ['/api/maison/admin/maisons', "/api/maison/admin/maisons/{$this->maison->id}/valider"],
            'put' => ["/api/maison/admin/maisons/{$this->maison->id}"],
            'delete' => ["/api/maison/admin/maisons/{$this->maison->id}"],
        ];

        foreach ($routes['get'] as $route) {
            $response = $this->getJson($route);
            $this->assertNotEquals(403, $response->status(), "Route {$route} should be accessible");
        }

        foreach ($routes['post'] as $route) {
            $response = $this->postJson($route, []);
            $this->assertNotEquals(403, $response->status(), "Route {$route} should be accessible");
        }
    }

    /**
     * Test : L'Admin Plateforme a accès aux routes admin (sauf suppression).
     */
    public function test_admin_plateforme_has_access_to_admin_routes_except_delete(): void
    {
        $this->actingAs($this->adminPlateforme);

        // Routes accessibles
        $accessibleRoutes = [
            '/api/maison/admin/maisons',
            '/api/maison/admin/maisons/en-attente',
            '/api/maison/admin/stats',
            "/api/maison/admin/maisons/{$this->maison->id}/valider",
            "/api/maison/admin/maisons/{$this->maison->id}/rejeter",
            "/api/maison/admin/maisons/{$this->maison->id}/suspendre",
            "/api/maison/admin/maisons/{$this->maison->id}/activer",
        ];

        foreach ($accessibleRoutes as $route) {
            $method = str_contains($route, 'valider') || str_contains($route, 'rejeter') ? 'post' : 'get';
            $response = $method === 'post' ? $this->postJson($route, []) : $this->getJson($route);
            $this->assertNotEquals(403, $response->status(), "Route {$route} should be accessible");
        }

        // Route non accessible (suppression)
        $response = $this->deleteJson("/api/maison/admin/maisons/{$this->maison->id}");
        $this->assertEquals(403, $response->status());
    }

    /**
     * Test : Un simple utilisateur n'a pas accès aux routes admin.
     */
    public function test_simple_user_has_no_access_to_admin_routes(): void
    {
        $this->actingAs($this->simpleUser);

        $adminRoutes = [
            '/api/maison/admin/maisons',
            '/api/maison/admin/maisons/en-attente',
            '/api/maison/admin/stats',
            "/api/maison/admin/maisons/{$this->maison->id}",
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->getJson($route);
            $this->assertEquals(403, $response->status(), "Route {$route} should be forbidden");
        }
    }

    // =========================================================================
    // TESTS DE PAGINATION
    // =========================================================================

    /**
     * Test : La pagination fonctionne correctement.
     */
    public function test_pagination_works_correctly(): void
    {
        $this->actingAs($this->superAdmin);

        // Créer 25 maisons
        for ($i = 1; $i <= 25; $i++) {
            Maison::create([
                'nom' => "Maison Pagination {$i}",
                'slug' => "maison-pagination-{$i}",
                'email_contact' => "pagination{$i}@test.com",
            ]);
        }

        $response = $this->getJson('/api/maison/admin/maisons?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(26, $response->json('meta.total')); // +1 la maison initiale
    }

    /**
     * Test : La limite de per_page est respectée (max 100).
     */
    public function test_per_page_limit_is_respected(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson('/api/maison/admin/maisons?per_page=200');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }

    // =========================================================================
    // TESTS DE TRI
    // =========================================================================

    /**
     * Test : Le tri par nom fonctionne.
     */
    public function test_sorting_by_name_works(): void
    {
        $this->actingAs($this->superAdmin);

        Maison::create(['nom' => 'A Maison', 'slug' => 'a-maison', 'email_contact' => 'a@test.com']);
        Maison::create(['nom' => 'C Maison', 'slug' => 'c-maison', 'email_contact' => 'c@test.com']);
        Maison::create(['nom' => 'B Maison', 'slug' => 'b-maison', 'email_contact' => 'b@test.com']);

        $response = $this->getJson('/api/maison/admin/maisons?order_by=nom&order_dir=asc');

        $data = $response->json('data');
        $this->assertEquals('A Maison', $data[0]['nom']);
        $this->assertEquals('B Maison', $data[1]['nom']);
        $this->assertEquals('C Maison', $data[2]['nom']);
    }

    // =========================================================================
    // TESTS DE DÉTAIL DE MAISON
    // =========================================================================

    /**
     * Test : L'Admin peut voir les détails d'une maison.
     */
    public function test_admin_can_view_maison_details(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson("/api/maison/admin/maisons/{$this->maison->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->maison->id)
            ->assertJsonPath('data.nom', $this->maison->nom);
    }

    /**
     * Test : La consultation d'une maison inexistante retourne 404.
     */
    public function test_view_nonexistent_maison_returns_404(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->getJson('/api/maison/admin/maisons/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    // =========================================================================
    // TESTS DE SYNCHRONISATION DES PERMISSIONS
    // =========================================================================

    /**
     * Test : Le Super Admin peut synchroniser les permissions.
     */
    public function test_super_admin_can_sync_permissions(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->postJson('/api/maison/admin/sync-permissions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /**
     * Test : L'Admin Plateforme ne peut pas synchroniser les permissions.
     */
    public function test_admin_plateforme_cannot_sync_permissions(): void
    {
        $this->actingAs($this->adminPlateforme);

        $response = $this->postJson('/api/maison/admin/sync-permissions');

        $response->assertStatus(403);
    }
}
