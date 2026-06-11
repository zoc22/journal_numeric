<?php

namespace Tests\Feature\Modules\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;
use Modules\Security\Models\UserSession;
use Modules\Security\Services\SessionTrackingService;
use Illuminate\Http\Request;

class SessionTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_track_a_user_session()
    {
        $user = User::factory()->create([
            'continent' => 'America',
            'pays' => 'USA',
            'ville' => 'New York',
        ]);

        $request = Request::create('/login', 'POST');
        $request->setUserResolver(fn() => $user);

        $trackingService = app(SessionTrackingService::class);
        $session = $trackingService->trackSession($request, 'test-session-id', 'test-token');

        $this->assertNotNull($session);
        $this->assertDatabaseHas('user_sessions', [
            'utilisateur_id' => $user->id,
            'session_id' => 'test-session-id',
            'continent' => 'America',
            'pays' => 'USA',
            'ville' => 'New York',
            'is_active' => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_terminate_a_session()
    {
        $user = User::factory()->create();
        
        $session = UserSession::create([
            'utilisateur_id' => $user->id,
            'session_id' => 'to-be-terminated',
            'ip_address' => '127.0.0.1',
            'is_active' => true,
        ]);

        $trackingService = app(SessionTrackingService::class);
        $trackingService->endSession('to-be-terminated');

        $this->assertDatabaseHas('user_sessions', [
            'session_id' => 'to-be-terminated',
            'is_active' => false,
        ]);
    }
}
