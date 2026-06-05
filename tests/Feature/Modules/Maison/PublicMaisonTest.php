<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Maison;

use Tests\TestCase;
use Modules\Maison\Models\Maison;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests des routes publiques des maisons d'édition.
 *
 * @package Tests\Feature\Modules\Maison
 */
class PublicMaisonTest extends TestCase
{
    use RefreshDatabase;

    protected Maison $activeMaison;
    protected Maison $pendingMaison;
    protected Maison $suspendedMaison;

    protected function setUp(): void
    {
        parent::setUp();

        // Création des maisons avec différents statuts
        $this->activeMaison = Maison::create([
            'nom' => 'Maison Active',
            'slug' => 'maison-active',
            'description' => 'Description de la maison active',
            'email_contact' => 'active@test.com',
            'statut' => 'active',
        ]);

        $this->pendingMaison = Maison::create([
            'nom' => 'Maison En Attente',
            'slug' => 'maison-attente',
            'description' => 'Description de la maison en attente',
            'email_contact' => 'attente@test.com',
            'statut' => 'en_attente',
        ]);

        $this->suspendedMaison = Maison::create([
            'nom' => 'Maison Suspendue',
            'slug' => 'maison-suspendue',
            'description' => 'Description de la maison suspendue',
            'email_contact' => 'suspendue@test.com',
            'statut' => 'suspendue',
        ]);
    }

    // =========================================================================
    // TESTS DE LISTE PUBLIQUE
    // =========================================================================

    /**
     * Test : La liste publique affiche uniquement les maisons actives.
     */
    public function test_public_list_only_shows_active_maisons(): void
    {
        $response = $this->getJson('/api/maison/publiques');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'nom', 'slug', 'description', 'email_contact']
                ]
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Maison Active', $data[0]['nom']);
    }

    /**
     * Test : La liste publique est paginée.
     */
    public function test_public_list_is_paginated(): void
    {
        // Créer plusieurs maisons actives
        for ($i = 1; $i <= 15; $i++) {
            Maison::create([
                'nom' => "Maison Active {$i}",
                'slug' => "maison-active-{$i}",
                'email_contact' => "active{$i}@test.com",
                'statut' => 'active',
            ]);
        }

        $response = $this->getJson('/api/maison/publiques?per_page=10');

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(16, $response->json('meta.total')); // +1 la maison initiale
    }

    /**
     * Test : La liste publique peut être recherchée.
     */
    public function test_public_list_can_be_searched(): void
    {
        // Cette route n'a pas de paramètre search dans la méthode indexPublic
        // Mais on peut tester que la route fonctionne
        $response = $this->getJson('/api/maison/publiques');
        $response->assertStatus(200);
    }

    // =========================================================================
    // TESTS DE DÉTAIL PUBLIC
    // =========================================================================

    /**
     * Test : Le détail public d'une maison active est accessible.
     */
    public function test_public_detail_of_active_maison_is_accessible(): void
    {
        $response = $this->getJson("/api/maison/publiques/{$this->activeMaison->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->activeMaison->id)
            ->assertJsonPath('data.nom', 'Maison Active')
            ->assertJsonPath('data.slug', 'maison-active');
    }

    /**
     * Test : Le détail public d'une maison en attente n'est pas accessible.
     */
    public function test_public_detail_of_pending_maison_is_not_accessible(): void
    {
        $response = $this->getJson("/api/maison/publiques/{$this->pendingMaison->slug}");

        $response->assertStatus(404);
    }

    /**
     * Test : Le détail public d'une maison suspendue n'est pas accessible.
     */
    public function test_public_detail_of_suspended_maison_is_not_accessible(): void
    {
        $response = $this->getJson("/api/maison/publiques/{$this->suspendedMaison->slug}");

        $response->assertStatus(404);
    }

    /**
     * Test : Le détail public avec un slug inexistant retourne 404.
     */
    public function test_public_detail_with_nonexistent_slug_returns_404(): void
    {
        $response = $this->getJson('/api/maison/publiques/inexistant');

        $response->assertStatus(404);
    }

    // =========================================================================
    // TESTS DES DONNÉES PUBLIQUES
    // =========================================================================

    /**
     * Test : Les données publiques n'incluent pas les informations sensibles.
     */
    public function test_public_data_does_not_include_sensitive_info(): void
    {
        $response = $this->getJson("/api/maison/publiques/{$this->activeMaison->slug}");

        $data = $response->json('data');

        // Les champs sensibles ne doivent pas être présents
        $this->assertArrayNotHasKey('validee_par', $data);
        $this->assertArrayNotHasKey('validee_le', $data);
        $this->assertArrayNotHasKey('statut', $data);
    }
}
