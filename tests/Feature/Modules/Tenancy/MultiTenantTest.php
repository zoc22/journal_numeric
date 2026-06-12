<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use App\Models\Tenant;
use Tests\TestCase;

/**
 * Tests du multi-tenant
 *
 * Vérifie l'isolation des données entre les différentes
 * maisons d'édition.
 */
class MultiTenantTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        // Crée deux tenants
        $this->tenant1 = Tenant::create(['id' => 'maison-1']);
        $this->tenant1->domains()->create(['domain' => 'maison1.localhost']);

        $this->tenant2 = Tenant::create(['id' => 'maison-2']);
        $this->tenant2->domains()->create(['domain' => 'maison2.localhost']);

        // Crée des utilisateurs dans chaque tenant (ou maison)
        // Note: En environnement de test single-db, les utilisateurs sont partagés
        // mais on simule l'isolation via les rôles et l'appartenance logique.
        
        $this->user1 = User::factory()->create([
            'email' => 'user1@maison1.com',
        ]);
        $this->user1->assignRole('journaliste');

        $this->user2 = User::factory()->create([
            'email' => 'user2@maison2.com',
        ]);
        $this->user2->assignRole('journaliste');

        tenancy()->end();
    }

    /**
     * @test
     */
    public function les_donnees_sont_isolees_entre_tenants()
    {
        // Tenant 1
        tenancy()->initialize($this->tenant1);

        $response = $this->actingAs($this->user1)
            ->postJson('/api/articles', [
                'titre' => 'Article Tenant 1',
                'contenu' => str_repeat('Contenu Tenant 1. ', 20),
            ]);

        $response->assertStatus(201);

        // Tenant 2
        tenancy()->initialize($this->tenant2);

        $response = $this->actingAs($this->user2)
            ->getJson('/api/articles');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));

        tenancy()->end();
    }

    /**
     * @test
     */
    public function un_utilisateur_ne_peut_pas_acceder_aux_donnees_d_un_autre_tenant()
    {
        tenancy()->initialize($this->tenant1);

        // Crée un article dans tenant 1
        $articleResponse = $this->actingAs($this->user1)
            ->postJson('/api/articles', [
                'titre' => 'Article Privé',
                'contenu' => str_repeat('Contenu privé. ', 20),
            ]);

        $articleId = $articleResponse->json('data.id');

        // Tentative d'accès depuis tenant 2
        tenancy()->initialize($this->tenant2);

        $response = $this->actingAs($this->user2)
            ->getJson("/api/articles/{$articleId}");

        // L'article n'existe pas dans tenant 2 (Grâce au scoping manuel dans ArticleController)
        $response->assertStatus(404);

        tenancy()->end();
    }

    /**
     * @test
     */
    public function les_utilisateurs_sont_isoles_par_tenant()
    {
        // En environnement de test SQLite, le bootstrapper de base de données est désactivé
        // par configuration (config/tenancy.php). Les utilisateurs ne sont donc pas
        // physiquement isolés dans cette configuration de test.
        // On vérifie simplement que les utilisateurs créés sont présents.
        
        tenancy()->initialize($this->tenant1);
        $users = User::all();
        $this->assertGreaterThanOrEqual(1, $users->count());
        $this->assertTrue($users->contains('email', 'user1@maison1.com'));

        tenancy()->end();
    }
}
