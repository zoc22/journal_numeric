<?php
// app-modules/User/Providers/UserServiceProvider.php

namespace Modules\User\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Modules\User\Models\User;
use Modules\User\Observers\UserObserver;

class UserServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'User';
    protected string $moduleNameLower = 'user';

    public function boot(): void
    {
        $this->registerConfig();
        $this->loadMigrations(); // Re-enabled after resolving central conflict
        $this->registerRoutes();
        $this->registerObservers();
    }

    public function register(): void
    {
        $this->app->register(\Spatie\Permission\PermissionServiceProvider::class);
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    protected function loadMigrations(): void
    {
        // Use lowercase 'database/migrations' to match the actual directory structure
        $migrationPath = module_path($this->moduleName, 'database/migrations');
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    protected function registerRoutes(): void
    {
        // Use lowercase 'routes/api.php' to match the actual directory structure
        $routePath = module_path($this->moduleName, 'routes/api.php');
        if (file_exists($routePath)) {
            $this->loadRoutesFrom($routePath);
        }
    }

    protected function registerObservers(): void
    {
        User::observe(UserObserver::class);
    }
}
