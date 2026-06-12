<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Security;

use Tests\TestCase;
use Modules\Security\Services\AuditService;
use Modules\Security\Models\AuditLog;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = app(AuditService::class);
        
        // S'assurer que le tenant par défaut existe pour les FK
        if (!\Illuminate\Support\Facades\DB::table('tenants')->where('id', '00000000-0000-0000-0000-000000000000')->exists()) {
            \Illuminate\Support\Facades\DB::table('tenants')->insert([
                'id' => '00000000-0000-0000-0000-000000000000',
                'name' => 'Platform',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->assertDatabaseHas('tenants', ['id' => '00000000-0000-0000-0000-000000000000']);

        // On s'assure que le logging est activé pour les tests
        config(['security.logging.enabled' => true]);
        config(['security.logging.log_all_actions' => true]);
    }

    /**
     * Test que le service d'audit enregistre correctement les actions.
     */
    public function test_peut_enregistrer_une_action_d_audit(): void
    {
        $user = User::factory()->create(['nom' => 'Test User']);
        Auth::login($user);

        $log = $this->auditService->log('test_action', 'test_module', [
            'description' => 'Description de test',
        ]);

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'test_action',
            'module' => 'test_module',
            'utilisateur_id' => $user->id,
            'description' => 'Description de test',
        ]);
    }

    /**
     * Test de l'enregistrement d'une création d'entité.
     */
    public function test_peut_enregistrer_une_creation_via_audit(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $log = $this->auditService->logCreation(
            'module_test', 
            'Article', 
            '123', 
            ['titre' => 'Nouveau titre'],
            'Création de test'
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'create',
            'module' => 'module_test',
            'entite_type' => 'Article',
            'entite_id' => '123',
            'description' => 'Création de test',
        ]);
        
        $this->assertNotNull($log->nouvelles_valeurs);
        $this->assertEquals('Nouveau titre', $log->nouvelles_valeurs['titre']);
    }

    /**
     * Test de l'enregistrement d'une mise à jour avec calcul des champs modifiés.
     */
    public function test_peut_enregistrer_une_mise_a_jour_avec_champs_modifies(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $anciennes = ['titre' => 'Ancien', 'contenu' => 'Identique'];
        $nouvelles = ['titre' => 'Nouveau', 'contenu' => 'Identique'];

        $log = $this->auditService->logUpdate(
            'module_test',
            'Article',
            '123',
            $anciennes,
            $nouvelles
        );

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'update',
            'entite_id' => '123',
        ]);

        $this->assertArrayHasKey('titre', $log->champs_modifies);
        $this->assertArrayNotHasKey('contenu', $log->champs_modifies);
        $this->assertEquals('Ancien', $log->champs_modifies['titre']['avant']);
        $this->assertEquals('Nouveau', $log->champs_modifies['titre']['apres']);
    }
}
