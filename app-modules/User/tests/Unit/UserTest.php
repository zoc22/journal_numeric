<?php
// app-modules/User/Tests/Unit/UserTest.php

namespace Modules\User\Tests\Unit;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Core\Traits\HasUuid;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test unitaire du modèle User.
 *
 * Ces tests vérifient que le modèle User :
 * - Utilise les bons traits
 * - A les bons attributs
 * - Valide correctement les règles métier
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que le modèle User utilise le trait HasUuid.
     * Cela garantit que les UUID sont générés automatiquement.
     */
    public function test_user_uses_has_uuid_trait(): void
    {
        $uses = class_uses(User::class);
        $this->assertArrayHasKey(HasUuid::class, $uses);
    }

    /**
     * Test que le modèle User utilise le trait HasRoles de Spatie.
     * Cela garantit que le RBAC fonctionne.
     */
    public function test_user_uses_has_roles_trait(): void
    {
        $uses = class_uses(User::class);
        $this->assertArrayHasKey(HasRoles::class, $uses);
    }

    /**
     * Test qu'un UUID est bien généré lors de la création d'un utilisateur.
     */
    public function test_user_has_uuid_on_creation(): void
    {
        $user = User::create([
            'nom' => 'Test',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals(36, strlen($user->id));
    }

    /**
     * Test que le nom complet est correctement formé.
     */
    public function test_user_has_full_name_attribute(): void
    {
        $user = new User([
            'prenom' => 'Jean',
            'nom' => 'Dupont',
        ]);

        $this->assertEquals('Jean Dupont', $user->full_name);
    }

    /**
     * Test que l'on peut assigner un rôle à un utilisateur.
     * Cela vérifie la compatibilité entre les UUID et Spatie Permission.
     */
    public function test_can_assign_role_to_user(): void
    {
        // Créer un rôle
        $role = \Spatie\Permission\Models\Role::create([
            'name' => 'journaliste',
            'guard_name' => 'sanctum',
        ]);

        // Créer un utilisateur
        $user = User::create([
            'nom' => 'Journaliste',
            'email' => 'journaliste@example.com',
            'password' => bcrypt('password'),
        ]);

        // Assigner le rôle
        $user->assignRole($role);

        $this->assertTrue($user->hasRole('journaliste'));
    }

    /**
     * Test que le scope actif filtre correctement.
     */
    public function test_active_scope_filters_active_users(): void
    {
        User::create([
            'nom' => 'Actif',
            'email' => 'actif@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        User::create([
            'nom' => 'Inactif',
            'email' => 'inactif@example.com',
            'password' => bcrypt('password'),
            'is_active' => false,
        ]);

        $activeUsers = User::active()->get();

        $this->assertEquals(1, $activeUsers->count());
        $this->assertEquals('Actif', $activeUsers->first()->nom);
    }

    /**
     * Test que les champs de localisation peuvent être assignés.
     */
    public function test_user_has_location_fields(): void
    {
        $user = User::create([
            'nom' => 'Location Test',
            'email' => 'location@example.com',
            'password' => bcrypt('password'),
            'continent' => 'Afrique',
            'pays' => 'Sénégal',
            'ville' => 'Dakar',
        ]);

        $this->assertEquals('Afrique', $user->continent);
        $this->assertEquals('Sénégal', $user->pays);
        $this->assertEquals('Dakar', $user->ville);
        
        $userFresh = $user->fresh();
        $this->assertEquals('Afrique', $userFresh->continent);
        $this->assertEquals('Sénégal', $userFresh->pays);
        $this->assertEquals('Dakar', $userFresh->ville);
    }
}
