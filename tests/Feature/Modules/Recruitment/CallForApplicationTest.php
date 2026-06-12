<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Recruitment;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Recruitment\Models\CallForApplication;
use Modules\Recruitment\Enums\CallStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class CallForApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['auth.defaults.guard' => 'sanctum']);
        
        // Créer les rôles nécessaires
        Role::firstOrCreate(['name' => 'admin_plateforme', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'sanctum']);
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin_plateforme');
    }

    /**
     * Test de création d'un appel à candidatures.
     */
    public function test_peut_creer_un_appel_a_candidatures(): void
    {
        $data = [
            'titre' => 'Nouvel Appel à Candidatures',
            'description' => 'Ceci est une description détaillée de plus de cinquante caractères pour passer la validation du formulaire.',
            'roles_vises' => ['journaliste', 'reviewer'],
            'date_limite' => now()->addDays(30)->format('Y-m-d'),
            'continent' => 'Afrique',
            'pays' => 'Sénégal',
            'ville' => 'Dakar',
            'nombre_postes' => 5,
        ];

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/recruitment/calls', $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.titre', 'Nouvel Appel à Candidatures');

        $this->assertDatabaseHas('calls_for_applications', [
            'titre' => 'Nouvel Appel à Candidatures',
            'statut' => CallStatus::BROUILLON->value,
        ]);
    }

    /**
     * Test de publication d'un appel.
     */
    public function test_peut_publier_un_appel(): void
    {
        $call = CallForApplication::create([
            'titre' => 'Appel à publier',
            'description' => 'Description de test assez longue pour passer la validation...',
            'roles_vises' => ['journaliste'],
            'date_limite' => now()->addDays(10),
            'maison_id' => tenant('id') ?? '00000000-0000-0000-0000-000000000000',
            'cree_par' => $this->admin->id,
            'statut' => CallStatus::BROUILLON->value,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/recruitment/calls/{$call->id}/publish");

        $response->assertStatus(200)
            ->assertJsonPath('data.statut', CallStatus::OUVERT->value);

        $this->assertEquals(CallStatus::OUVERT->value, $call->fresh()->statut);
    }
}
