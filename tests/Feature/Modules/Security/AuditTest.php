<?php

namespace Tests\Feature\Modules\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Modules\Security\Models\AuditLog;
use Modules\Security\Models\LoginHistory;
use Modules\Security\Services\AuditService;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_log_an_action()
    {
        config(['security.logging.log_all_actions' => true]);

        // S'assurer qu'on n'est pas dans un contexte tenant pour ce test simple
        if (function_exists('tenancy') && tenancy()->initialized) {
            tenancy()->end();
        }

        $user = User::factory()->create([
            'continent' => 'Africa',
            'pays' => 'Senegal',
            'ville' => 'Dakar',
        ]);

        $this->actingAs($user, 'sanctum');

        $auditService = app(AuditService::class);
        $auditService->log('test_action', 'test_module', [
            'description' => 'Test description'
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'test_action',
            'module' => 'test_module',
            'utilisateur_id' => $user->id,
            'maison_id' => null,
            'continent' => 'Africa',
            'pays' => 'Senegal',
            'ville' => 'Dakar',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_log_a_login()
    {
        $user = User::factory()->create([
            'continent' => 'Europe',
            'pays' => 'France',
            'ville' => 'Paris',
        ]);

        $auditService = app(AuditService::class);
        $auditService->logLogin($user, true);

        $this->assertDatabaseHas('login_histories', [
            'utilisateur_id' => $user->id,
            'succes' => true,
            'continent' => 'Europe',
            'pays' => 'France',
            'ville' => 'Paris',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_log_a_failed_login()
    {
        $email = 'failed@example.com';
        $auditService = app(AuditService::class);
        $auditService->logLogin(null, false, $email, 'Invalid credentials');

        $this->assertDatabaseHas('login_histories', [
            'email' => $email,
            'succes' => false,
            'message_erreur' => 'Invalid credentials',
        ]);

        $this->assertDatabaseHas('failed_login_attempts', [
            'email' => $email,
        ]);
    }
}
