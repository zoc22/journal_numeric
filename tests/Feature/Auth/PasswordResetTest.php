<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Modules\User\Models\User;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function un_utilisateur_peut_demander_un_lien_de_reinitialisation()
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/password/forgot', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function un_utilisateur_peut_reinitialiser_son_mot_de_passe_avec_un_token_valide()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/password/reset', [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function la_reinitialisation_echoue_avec_un_token_invalide()
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/password/reset', [
            'token' => 'invalid-token',
            'email' => 'user@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }
}
