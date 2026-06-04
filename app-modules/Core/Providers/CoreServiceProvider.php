<?php
// app-modules/Core/Providers/CoreServiceProvider.php

namespace Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Modules\Core\Services\ModuleManager;
use Modules\Core\Http\Middleware\PermissionMiddleware;
use Modules\Core\Http\Middleware\RoleMiddleware;

/**
 * Ce service provider enregistre les composants du module Core :
 * - le ModuleManager en singleton,
 * - les middlewares,
 * - les helpers (déjà chargés via composer.json),
 * - et éventuellement les migrations.
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Nom du module (utilisé pour les ressources).
     *
     * @var string
     */
    protected string $moduleName = 'Core';

    /**
     * Version bas du nom.
     *
     * @var string
     */
    protected string $moduleNameLower = 'core';

    /**
     * Bootstrap des services du module.
     * Cette méthode est appelée après l'enregistrement.
     *
     * @return void
     */
    public function boot(): void
    {
        // Charger les fichiers de configuration
        $this->registerConfig();

        // Charger les migrations (si le module Core a des tables)
        $this->loadMigrations();

        // Enregistrer les commandes artisan (s'il y en a)
        $this->registerCommands();

        // Enregistrer les middlewares
        $this->registerMiddleware();
    }

    /**
     * Enregistre les services dans le conteneur.
     *
     * @return void
     */
    public function register(): void
    {
        // Le ModuleManager est en singleton pour être réutilisé partout
        $this->app->singleton(ModuleManager::class, function ($app) {
            return new ModuleManager();
        });
    }

    /**
     * Fusionne les fichiers de configuration du module avec la config globale.
     *
     * @return void
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/module.php'),
            $this->moduleNameLower
        );
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/permissions.php'),
            "{$this->moduleNameLower}.permissions"
        );
    }

    /**
     * Charge les migrations du module.
     * (Ici, le module Core n'a pas besoin de tables, mais la méthode est prête)
     *
     * @return void
     */
    protected function loadMigrations(): void
    {
        $migrationPath = module_path($this->moduleName, 'database/migrations');
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    /**
     * Enregistre les commandes artisan du module.
     *
     * @return void
     */
    protected function registerCommands(): void
    {
        $commandPath = module_path($this->moduleName, 'Console/Commands');
        if (is_dir($commandPath)) {
            $this->commands(array_map(function ($file) {
                return "Modules\\{$this->moduleName}\\Console\\Commands\\" . basename($file, '.php');
            }, glob($commandPath . '/*.php')));
        }
    }

    /**
     * Enregistre les middlewares dans le routeur.
     *
     * @return void
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('role', RoleMiddleware::class);
    }
}
