<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Recruitment;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Recruitment\Models\CallForApplication;
use Modules\Recruitment\Models\Application;
use Modules\Recruitment\Enums\CallStatus;
use Modules\Recruitment\Enums\ApplicationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $candidat;
    protected CallForApplication $call;

    protected function setUp(): void
    {
        parent::setUp();
        
        config(['auth.defaults.guard' => 'sanctum']);
        
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'sanctum']);
        
        $this->candidat = User::factory()->create();
        $this->candidat->assignRole('lecteur');

        $this->call = CallForApplication::create([
            'titre' => 'Appel Ouvert',
            'description' => 'Description de test assez longue pour passer la validation...',
            'roles_vises' => ['journaliste'],
            'date_limite' => now()->addDays(10),
            'maison_id' => tenant('id') ?? '00000000-0000-0000-0000-000000000000',
            'cree_par' => $this->candidat->id,
            'statut' => CallStatus::OUVERT->value,
        ]);
    }

    /**
     * Test de soumission d'une candidature.
     */
    public function test_peut_soumettre_une_candidature(): void
    {
        $data = [
            'lettre_motivation' => 'Je suis extrêmement motivé par ce poste car j\'ai une grande expérience dans le journalisme numérique depuis plusieurs années. Voici ma lettre détaillée.',
            'continent' => 'Afrique',
            'pays' => 'Sénégal',
            'ville' => 'Dakar',
        ];

        $response = $this->actingAs($this->candidat, 'sanctum')
            ->postJson("/api/recruitment/applications/call/{$this->call->id}", $data);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('applications', [
            'appel_id' => $this->call->id,
            'candidat_id' => $this->candidat->id,
            'statut' => ApplicationStatus::EN_ATTENTE->value,
        ]);
    }

    /**
     * Test de consultation de ses propres candidatures.
     */
    public function test_peut_voir_ses_propres_candidatures(): void
    {
        Application::create([
            'appel_id' => $this->call->id,
            'candidat_id' => $this->candidat->id,
            'lettre_motivation' => 'Ma motivation...',
            'statut' => ApplicationStatus::EN_ATTENTE->value,
        ]);

        $response = $this->actingAs($this->candidat, 'sanctum')
            ->getJson('/api/recruitment/applications');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
