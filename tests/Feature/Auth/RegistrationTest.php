<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function un_utilisateur_peut_s_inscrire_avec_des_donnees_valides()
    {
        $response = $this->postJson('/api/register', [
            'nom' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user',
                'token',
            ],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'jean@example.com',
            'nom' => 'Jean Dupont',
        ]);
    }

    /**
     * @test
     */
    public function l_inscription_echoue_si_l_email_est_deja_pris()
    {
        // Premier utilisateur
        $this->postJson('/api/register', [
            'nom' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Deuxième tentative avec le même email
        $response = $this->postJson('/api/register', [
            'nom' => 'Jean Dupont 2',
            'email' => 'jean@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * @test
     */
    public function l_inscription_echoue_si_le_mot_de_passe_est_trop_court()
    {
        $response = $this->postJson('/api/register', [
            'nom' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * @test
     */
    public function l_inscription_echoue_si_les_mots_de_passe_ne_correspondent_pas()
    {
        $response = $this->postJson('/api/register', [
            'nom' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    /**
     * @test
     */
    public function l_inscription_echoue_sans_nom()
    {
        $response = $this->postJson('/api/register', [
            'email' => 'jean@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nom']);
    }

    /**
     * @test
     */
    public function l_inscription_echoue_avec_un_email_invalide()
    {
        $response = $this->postJson('/api/register', [
            'nom' => 'Jean Dupont',
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}
