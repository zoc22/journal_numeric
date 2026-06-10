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
            // User management
            'user.manage',
            
            // Article management
            'article.creer',
            'article.modifier',
            'article.supprimer',
            'article.voir_historique',
            
            // Workflow
            'workflow.transition',
            'workflow.voir',
            'workflow.valider_reviewer',
            'workflow.valider_editeur',
            'workflow.valider_directeur',
            'workflow.valider_final',
            'workflow.publier',
            
            // Review
            'review.assigner',
            'review.voir',
            'review.supprimer',
            'review.voir_historique',
            'review.feedback',
            
            // Media management
            'media.upload',
            'media.voir',
            'media.supprimer',
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
            'editeur_chef' => 6,
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

            // Assigner des permissions par défaut selon le rôle
            if ($roleName === 'editeur_chef') {
                $role->givePermissionTo($permissions);
            } elseif ($roleName === 'directeur_collection') {
                $role->givePermissionTo([
                    'workflow.transition', 'workflow.voir', 'workflow.valider_directeur',
                    'article.voir_historique', 'review.voir_historique', 'review.voir'
                ]);
            } elseif ($roleName === 'editeur_associe') {
                $role->givePermissionTo([
                    'article.creer', 'article.modifier', 'article.supprimer',
                    'workflow.transition', 'workflow.voir', 'workflow.valider_editeur',
                    'article.voir_historique', 'review.voir_historique', 'review.voir', 'review.assigner'
                ]);
            } elseif ($roleName === 'reviewer') {
                $role->givePermissionTo([
                    'workflow.transition', 'workflow.voir', 'workflow.valider_reviewer',
                    'review.voir', 'review.feedback'
                ]);
            } elseif ($roleName === 'journaliste') {
                $role->givePermissionTo([
                    'article.creer', 'article.modifier', 'workflow.transition',
                    'workflow.voir', 'article.voir_historique', 'media.upload', 'media.voir', 'media.supprimer'
                ]);
            }
        }

        // Assigner toutes les permissions au rôle admin_plateforme
        /** @var Role|null $adminRole */
        $adminRole = Role::where('name', 'admin_plateforme')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
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
