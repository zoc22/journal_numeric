<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TestDatabaseSeeder extends Seeder
{
    /**
     * Seed the test database with required roles and users.
     */
    public function run(): void
    {
        // Créer les permissions
        $permissions = [
            'user.manage',
            'article.manage',
            'maison.manage',
            'reviewer.assign',
            'article.validate',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'sanctum'
            ]);
        }

        // Créer les rôles
        $roles = [
            'super_admin' => 8,
            'admin_plateforme' => 7,
            'editeur_en_chef' => 6,
            'directeur_collection' => 5,
            'editeur_associe' => 4,
            'reviewer' => 3,
            'journaliste' => 2,
            'lecteur' => 1,
        ];

        foreach ($roles as $roleName => $niveau) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'sanctum'
            ]);
        }

        // Assigner les permissions au rôle admin_plateforme
        /** @var Role|null $adminRole */
        $adminRole = Role::where('name', 'admin_plateforme')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo('user.manage');
        }

        // Créer l'utilisateur admin pour les tests
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'nom' => 'Admin',
                'prenom' => 'System',
                'password' => bcrypt('password'),
                'is_active' => true,
                'continent' => 'Afrique',
                'pays' => 'Cameroun',
                'ville' => 'Yaoundé',
            ]
        );
        $admin->assignRole('admin_plateforme');

        // Créer un utilisateur journaliste pour les tests
        $journaliste = User::firstOrCreate(
            ['email' => 'journaliste@example.com'],
            [
                'nom' => 'Journaliste',
                'prenom' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
                'continent' => 'Afrique',
                'pays' => 'Cameroun',
                'ville' => 'Douala',
            ]
        );
        $journaliste->assignRole('journaliste');

        // Créer un lecteur pour les tests
        $lecteur = User::firstOrCreate(
            ['email' => 'lecteur@example.com'],
            [
                'nom' => 'Lecteur',
                'prenom' => 'Test',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        );
        $lecteur->assignRole('lecteur');

        $this->command->info('Test database seeded successfully!');
        $this->command->info('Admin email: admin@example.com / password: password');
        $this->command->info('Journaliste email: journaliste@example.com / password: password');
        $this->command->info('Lecteur email: lecteur@example.com / password: password');
    }
}
