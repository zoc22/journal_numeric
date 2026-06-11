<?php

declare(strict_types=1);

namespace Modules\Recruitment\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Recruitment\Services\ApplicationService;
use Modules\Recruitment\Services\CallForApplicationService;

class RecruitmentServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Recruitment';

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'recruitment'
        );

        $this->app->singleton(CallForApplicationService::class);
        $this->app->singleton(ApplicationService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('recruitment.php'),
        ], 'recruitment-config');
    }
}
