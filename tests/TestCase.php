<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected $defaultHeaders = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Vider le cache des permissions de Spatie pour éviter les problèmes entre les tests
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Initialiser la tenancy pour les tests qui en ont besoin
        if (config('app.env') === 'testing' && class_exists(\App\Models\Tenant::class)) {
            try {
                $tenant = \App\Models\Tenant::firstOrCreate(
                    ['id' => '00000000-0000-0000-0000-000000000000'],
                    [
                        'name' => 'Test Maison',
                        'status' => 'active'
                    ]
                );
                
                if (!$tenant->domains()->where('domain', 'test.localhost')->exists()) {
                    $tenant->domains()->create(['domain' => 'test.localhost']);
                }
                if (!$tenant->domains()->where('domain', 'localhost')->exists()) {
                    $tenant->domains()->create(['domain' => 'localhost']);
                }
                
                tenancy()->initialize($tenant);
                
                // Définir l'hôte par défaut pour les requêtes HTTP dans les tests
                $this->baseUrl = 'http://test.localhost';
                $this->defaultHeaders['Host'] = 'test.localhost';
                $this->withServerVariables(['HTTP_HOST' => 'test.localhost']);
                
            } catch (\Exception $e) {
                // Ignore
            }
        }

        // Seed basic roles and permissions for all tests
        try {
            $this->seed(\Database\Seeders\TestDatabaseSeeder::class);
        } catch (\Exception $e) {
            // Ignorer si les tables ne sont pas prêtes
        }
    }
}
