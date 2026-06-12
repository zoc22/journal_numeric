<?php

namespace Tests\Unit\Modules\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Modules\Security\Models\AuditLog;
use Modules\Security\Services\AuditService;
use Illuminate\Support\Facades\Auth;

class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditService $auditService;

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
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_format_location_data_from_user()
    {
        $user = User::factory()->create([
            'continent' => 'Africa',
            'pays' => 'Senegal',
            'ville' => 'Dakar',
        ]);

        // Access protected method via reflection if needed, or just test the side effects
        // Since AuditService is what we are testing, let's log and see.
        
        $this->actingAs($user);
        config(['security.logging.log_all_actions' => true]);

        $this->auditService->log('test', 'unit');

        $log = AuditLog::first();
        $this->assertEquals('Africa', $log->continent);
        $this->assertEquals('Senegal', $log->pays);
        $this->assertEquals('Dakar', $log->ville);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_detects_device_type_correctly()
    {
        // Use reflection to test protected methods for browser/os detection
        $reflection = new \ReflectionClass(AuditService::class);
        $method = $reflection->getMethod('detectDeviceType');
        $method->setAccessible(true);

        $this->assertEquals('mobile', $method->invoke($this->auditService, 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1'));
        $this->assertEquals('desktop', $method->invoke($this->auditService, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'));
    }
}
