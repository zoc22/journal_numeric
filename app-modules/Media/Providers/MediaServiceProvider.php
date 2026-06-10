<?php

declare(strict_types=1);

namespace Modules\Media\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Media\Services\ArticleMediaService;
use Modules\Media\Services\ImageOptimizationService;
use Modules\Media\Services\MediaService;

class MediaServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Media';

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'media'
        );

        $this->app->singleton(MediaService::class);
        $this->app->singleton(ImageOptimizationService::class);
        $this->app->singleton(ArticleMediaService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('media.php'),
        ], 'media-config');
    }
}
