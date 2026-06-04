<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Charger les migrations centrales depuis le sous-dossier
        $this->loadMigrationsFrom(database_path('migrations/central'));

        // Charger les helpers si nécessaire (déjà fait via composer.json mais pour sécurité)
        $helperPath = base_path('app-modules/Core/Helpers/helpers.php');
        if (file_exists($helperPath)) {
            require_once $helperPath;
        }
    }
}
