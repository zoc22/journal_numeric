<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * Tests d'authentification
 *
 * Vérifie le bon fonctionnement des endpoints d'authentification :
 * - Login
 * - Logout
 * - Registration
 * - Récupération du profil
 * - Rate limiting
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Crée un utilisateur de test
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function un_utilisateur_peut_se_connecter_avec_des_identifiants_valides()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'token',
                ],
            ]);

        $this->assertAuthenticated();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function un_utilisateur_ne_peut_pas_se_connecter_avec_un_mot_de_passe_invalide()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonValidationErrors(['email']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function un_utilisateur_ne_peut_pas_se_connecter_avec_un_email_inexistant()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function un_utilisateur_authentifie_peut_se_deconnecter()
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function un_utilisateur_authentifie_peut_recuperer_son_profil()
    {
        $token = $this->user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/me');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.id', $this->user->id);
        $response->assertJsonPath('data.email', 'test@example.com');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function un_utilisateur_non_authentifie_ne_peut_pas_acceder_aux_routes_protegees()
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function le_rate_limiting_bloque_les_tentatives_excessives()
    {
        $response = null;

        // 6 tentatives (limite = 5)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response->assertStatus(429);
        $response->assertJsonPath('success', false);
        $response->assertJsonStructure(['message', 'retry_after']);
    }
}
