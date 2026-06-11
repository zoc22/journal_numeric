<?php

namespace Tests\Feature\Modules\Recruitment;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Recruitment\Models\CallForApplication;
use Modules\Recruitment\Models\Application;
use Modules\Recruitment\Enums\CallStatus;
use Modules\Recruitment\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class RecruitmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // S'assurer que le tenant par défaut existe pour éviter les erreurs de FK
        \App\Models\Tenant::firstOrCreate(
            ['id' => '00000000-0000-0000-0000-000000000000'],
            [
                'name' => 'Test Maison',
                'status' => 'active',
                'slug' => 'test-maison'
            ]
        );
    }

    /**
     * Test de création d'un appel à candidatures
     */
    public function test_can_create_call_for_application(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $this->assertNotNull($admin, 'Admin user not found. Check if seeding worked.');
        
        $data = [
            'titre' => 'Nouvel appel à candidatures',
            'description' => 'Ceci est une description détaillée de plus de cinquante caractères pour passer la validation.',
            'roles_vises' => ['journaliste', 'reviewer'],
            'date_limite' => now()->addDays(30)->format('Y-m-d'),
            'continent' => 'Afrique',
            'pays' => 'Sénégal',
            'ville' => 'Dakar',
            'nombre_postes' => 5,
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/recruitment/calls', $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.titre', $data['titre'])
            ->assertJsonPath('data.continent', 'Afrique');

        $this->assertDatabaseHas('calls_for_applications', [
            'titre' => $data['titre'],
            'continent' => 'Afrique',
            'statut' => CallStatus::BROUILLON->value,
        ]);
    }

    /**
     * Test de publication d'un appel
     */
    public function test_can_publish_call(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        
        $call = CallForApplication::create([
            'titre' => 'Appel à publier',
            'description' => 'Description de test...',
            'roles_vises' => ['journaliste'],
            'date_limite' => now()->addDays(10),
            'maison_id' => '00000000-0000-0000-0000-000000000000',
            'cree_par' => $admin->id,
            'statut' => CallStatus::BROUILLON->value,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/recruitment/calls/{$call->id}/publish");

        $response->assertStatus(200)
            ->assertJsonPath('data.statut', CallStatus::OUVERT->value);

        $this->assertEquals(CallStatus::OUVERT->value, $call->fresh()->statut);
    }

    /**
     * Test de soumission d'une candidature
     */
    public function test_can_submit_application(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $candidat = User::where('email', 'lecteur@example.com')->first();
        
        $call = CallForApplication::create([
            'titre' => 'Appel ouvert',
            'description' => 'Description de test...',
            'roles_vises' => ['journaliste'],
            'date_limite' => now()->addDays(10),
            'maison_id' => '00000000-0000-0000-0000-000000000000',
            'cree_par' => $admin->id,
            'statut' => CallStatus::BROUILLON->value,
        ]);
        
        $call->publier();

        $data = [
            'lettre_motivation' => 'Je suis très motivé par ce poste car j\'ai une grande expérience dans le domaine du journalisme depuis plus de dix ans. Voici ma lettre de motivation étendue pour passer la validation. Elle doit faire plus de cent caractères.',
            'continent' => 'Europe',
            'pays' => 'France',
            'ville' => 'Paris',
        ];

        $response = $this->actingAs($candidat, 'sanctum')
            ->postJson("/api/recruitment/applications/call/{$call->id}", $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.continent', 'Europe');

        $this->assertDatabaseHas('applications', [
            'appel_id' => $call->id,
            'candidat_id' => $candidat->id,
            'continent' => 'Europe',
        ]);
    }

    /**
     * Test de revue d'une candidature
     */
    public function test_can_review_application(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $candidat = User::where('email', 'lecteur@example.com')->first();
        
        $call = CallForApplication::create([
            'titre' => 'Appel pour revue',
            'description' => 'Description...',
            'roles_vises' => ['journaliste'],
            'date_limite' => now()->addDays(10),
            'maison_id' => '00000000-0000-0000-0000-000000000000',
            'cree_par' => $admin->id,
            'statut' => CallStatus::OUVERT->value,
        ]);

        $application = Application::create([
            'appel_id' => $call->id,
            'candidat_id' => $candidat->id,
            'lettre_motivation' => 'Motivation...',
            'statut' => ApplicationStatus::EN_ATTENTE->value,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/recruitment/applications/{$application->id}/review", [
                'decision' => 'acceptee',
                'commentaires_examen' => 'Excellent profil.',
                'score' => 95,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.statut', ApplicationStatus::ACCEPTEE->value);

        $this->assertEquals(ApplicationStatus::ACCEPTEE->value, $application->fresh()->statut);
        $this->assertEquals(95, $application->fresh()->score);
    }
}
