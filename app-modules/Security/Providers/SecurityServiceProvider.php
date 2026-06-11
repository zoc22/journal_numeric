<?php

declare(strict_types=1);

namespace Modules\Security\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Security\Services\AuditService;
use Modules\Security\Services\RateLimitingService;
use Modules\Security\Services\SecurityService;
use Modules\Security\Services\SessionTrackingService;

class SecurityServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Security';

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'security'
        );

        $this->app->singleton(AuditService::class);
        $this->app->singleton(SecurityService::class);
        $this->app->singleton(RateLimitingService::class);
        $this->app->singleton(SessionTrackingService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('security.php'),
        ], 'security-config');
    }
}
