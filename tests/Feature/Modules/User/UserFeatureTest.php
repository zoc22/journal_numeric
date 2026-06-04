<?php

namespace Tests\Feature\Modules\User;

use Tests\TestCase;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class UserFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
            'tenant'
        ]);

        // Setup basic roles and permissions for testing
        Permission::firstOrCreate(['name' => 'user.manage', 'guard_name' => 'sanctum']);
        $adminRole = Role::firstOrCreate(['name' => 'admin_plateforme', 'guard_name' => 'sanctum']);
        $adminRole->givePermissionTo('user.manage');
        
        Role::firstOrCreate(['name' => 'journaliste', 'guard_name' => 'sanctum']);
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'sanctum']);
    }

    /**
     * Test create user functionality.
     */
    public function test_can_create_user(): void
    {
        $admin = User::create([
            'nom' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin_plateforme');

        Sanctum::actingAs($admin, ['*']);

        $userData = [
            'nom' => 'New',
            'prenom' => 'User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'continent' => 'Afrique',
            'pays' => 'Sénégal',
            'ville' => 'Dakar',
            'role' => 'journaliste',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nom', 'New')
            ->assertJsonPath('data.continent', 'Afrique');

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'continent' => 'Afrique',
            'pays' => 'Sénégal',
            'ville' => 'Dakar',
        ]);

        $user = User::where('email', 'newuser@example.com')->first();
        if ($user && !($user instanceof User)) {
            fwrite(STDERR, "DEBUG: \$user is of class " . get_class($user) . "\n");
        }
        $this->assertTrue($user->hasRole('journaliste'));
    }

    /**
     * Test update user functionality.
     */
    public function test_can_update_user(): void
    {
        $admin = User::create([
            'nom' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin_plateforme');

        $user = User::create([
            'nom' => 'OldName',
            'email' => 'old@example.com',
            'password' => Hash::make('password'),
        ]);

        Sanctum::actingAs($admin, ['*']);

        $updateData = [
            'nom' => 'UpdatedName',
            'continent' => 'Europe',
            'pays' => 'France',
            'ville' => 'Paris',
        ];

        $response = $this->putJson("/api/users/{$user->id}", $updateData);

        if ($response->status() !== 200) {
            fwrite(STDERR, $response->getContent() . "\n");
        }

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nom', 'UpdatedName');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nom' => 'UpdatedName',
            'continent' => 'Europe',
        ]);
    }

    /**
     * Test profile update functionality.
     */
    public function test_can_update_own_profile(): void
    {
        $user = User::create([
            'nom' => 'MyProfile',
            'email' => 'profile@example.com',
            'password' => Hash::make('password'),
        ]);

        Sanctum::actingAs($user, ['*']);

        $updateData = [
            'nom' => 'MyUpdatedProfile',
            'continent' => 'Amérique',
            'pays' => 'Canada',
            'ville' => 'Montréal',
        ];

        $response = $this->putJson('/api/profile', $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nom', 'MyUpdatedProfile')
            ->assertJsonPath('data.continent', 'Amérique');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'continent' => 'Amérique',
        ]);
    }

    /**
     * Test user activation and deactivation.
     */
    public function test_can_activate_and_deactivate_user(): void
    {
        $admin = User::create([
            'nom' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin_plateforme');

        $user = User::create([
            'nom' => 'StatusUser',
            'email' => 'status@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        Sanctum::actingAs($admin, ['*']);

        // Deactivate
        $this->postJson("/api/users/{$user->id}/deactivate")
            ->assertStatus(200);
        
        $user->refresh();
        $this->assertFalse($user->is_active);

        // Activate
        $this->postJson("/api/users/{$user->id}/activate")
            ->assertStatus(200);
        
        $user->refresh();
        $this->assertTrue($user->is_active);
    }

    /**
     * Test list users functionality.
     */
    public function test_can_list_users(): void
    {
        $admin = User::create([
            'nom' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('admin_plateforme');

        User::create([
            'nom' => 'User1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
        ]);

        Sanctum::actingAs($admin, ['*']);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'nom', 'email', 'continent', 'pays', 'ville']
                ]
            ]);
    }
}
