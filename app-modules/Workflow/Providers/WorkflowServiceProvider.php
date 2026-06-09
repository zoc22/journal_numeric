<?php

declare(strict_types=1);

namespace Modules\Workflow\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Workflow\Services\ReviewAssignmentService;
use Modules\Workflow\Services\ValidationRulesService;
use Modules\Workflow\Services\WorkflowService;

/**
 * Service Provider du module Workflow
 */
class WorkflowServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Workflow';

    public function register(): void
    {
        // Fusionne la configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'workflow'
        );

        // Enregistre les services
        $this->app->singleton(WorkflowService::class);
        $this->app->singleton(ReviewAssignmentService::class);
        $this->app->singleton(ValidationRulesService::class);
    }

    public function boot(): void
    {
        // Charge les migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Charge les routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Publie la configuration
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('workflow.php'),
        ], 'workflow-config');
    }
}
