<?php

declare(strict_types=1);

namespace Modules\Maison\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Maison\Models\Maison;
use Modules\Maison\Observers\MaisonObserver;

/**
 * Service provider du module Maison.
 * Enregistre les routes, les migrations et les observers.
 */
class MaisonServiceProvider extends ServiceProvider
{
    /**
     * Nom du module.
     */
    protected string $moduleName = 'Maison';

    /**
     * Chemin racine du module.
     */
    protected string $modulePath;

    /**
     * Constructeur.
     */
    public function __construct(\Illuminate\Contracts\Foundation\Application $app)
    {
        parent::__construct($app);
        $this->modulePath = dirname(__DIR__, 1);
    }

    /**
     * Bootstrap des services du module.
     */
    public function boot(): void
    {
        // Enregistrement des routes (uniquement dans le contexte tenant)
        $this->registerRoutes();

        // Enregistrement des migrations
        $this->registerMigrations();

        // Enregistrement des observers
        $this->registerObservers();

        // Publication de la configuration
        $this->registerConfig();
    }

    /**
     * Enregistrement des services dans le conteneur.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            $this->modulePath . '/Config/config.php',
            'module.maison'
        );
    }

    /**
     * Enregistrement des routes API.
     */
    protected function registerRoutes(): void
    {
        // Chargement des routes tenant (pour les maisons)
        $routesPath = $this->modulePath . '/routes/api.php';

        if (file_exists($routesPath)) {
            $this->loadRoutesFrom($routesPath);
        }
    }

    /**
     * Enregistrement des migrations.
     * Note: Le dossier doit s'appeler 'migrations' (minuscule) ou 'Migrations'
     */
    protected function registerMigrations(): void
    {
        // Essayer d'abord avec 'migrations' (minuscule)
        $migrationsPath = $this->modulePath . '/Database/migrations';

        if (!is_dir($migrationsPath)) {
            // Essayer avec 'Migrations' (majuscule)
            $migrationsPath = $this->modulePath . '/Database/Migrations';
        }

        if (is_dir($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }

    /**
     * Enregistrement des observers.
     */
    protected function registerObservers(): void
    {
        // Vérifier que la classe Maison existe avant d'enregistrer l'observer
        if (class_exists(Maison::class)) {
            Maison::observe(MaisonObserver::class);
        }
    }

    /**
     * Enregistrement de la configuration.
     */
    protected function registerConfig(): void
    {
        $configPath = $this->modulePath . '/Config/config.php';

        if (file_exists($configPath)) {
            $this->publishes([
                $configPath => config_path('module.maison.php'),
            ], 'config');
        }
    }
}
