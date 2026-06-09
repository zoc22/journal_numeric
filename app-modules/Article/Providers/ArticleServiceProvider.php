<?php

declare(strict_types=1);

namespace Modules\Article\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Article\Services\ArticleService;
use Modules\Article\Services\CategoryService;
use Modules\Article\Services\VersionService;

/**
 * Service Provider du module Article
 *
 * Enregistre les services, migrations, routes et configurations
 * du module Article.
 */
class ArticleServiceProvider extends ServiceProvider
{
    /**
     * Nom du module
     */
    protected string $moduleName = 'Article';

    /**
     * Enregistre les services
     */
    public function register(): void
    {
        // Fusionne la configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'article'
        );

        // Enregistre les services
        $this->app->singleton(ArticleService::class, function ($app) {
            return new ArticleService($app->make(VersionService::class));
        });

        $this->app->singleton(CategoryService::class);
        $this->app->singleton(VersionService::class);
    }

    /**
     * Démarre les services
     */
    public function boot(): void
    {
        // Charge les migrations du module
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Charge les routes du module
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Publie la configuration
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('article.php'),
        ], 'article-config');

        // Charge les traductions (optionnel)
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'article');
    }
}
