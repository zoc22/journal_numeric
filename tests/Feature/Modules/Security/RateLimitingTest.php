<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Security;

use Tests\TestCase;
use Modules\Security\Services\RateLimitingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private RateLimitingService $rateLimitingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimitingService = app(RateLimitingService::class);
        Cache::flush();
    }

    /**
     * Test que le service de limitation fonctionne correctement.
     */
    public function test_peut_limiter_les_tentatives(): void
    {
        // On configure des limites basses pour le test
        config(['security.rate_limiting.enabled' => true]);
        config(['security.rate_limiting.max_attempts' => ['test' => 2]]);
        config(['security.rate_limiting.decay_minutes' => ['test' => 1]]);

        $key = 'test_key';

        // Première tentative : OK
        $this->assertTrue($this->rateLimitingService->attempt($key, 'test'));
        
        // Deuxième tentative : OK
        $this->assertTrue($this->rateLimitingService->attempt($key, 'test'));
        
        // Troisième tentative : Doit échouer car limite à 2
        $this->assertFalse($this->rateLimitingService->attempt($key, 'test'));
    }

    /**
     * Test de la réinitialisation du compteur.
     */
    public function test_peut_reinitialiser_le_limiteur(): void
    {
        config(['security.rate_limiting.enabled' => true]);
        config(['security.rate_limiting.max_attempts' => ['test' => 1]]);
        $key = 'test_key_clear';

        $this->assertTrue($this->rateLimitingService->attempt($key, 'test'));
        $this->assertFalse($this->rateLimitingService->attempt($key, 'test'));

        $this->rateLimitingService->clear($key);

        $this->assertTrue($this->rateLimitingService->attempt($key, 'test'));
    }
}
