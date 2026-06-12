<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Article;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Category;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests fonctionnels pour les catégories
 */
class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $editeur;

    protected function setUp(): void
    {
        parent::setUp();

        // Configuration du guard pour les tests
        config(['auth.defaults.guard' => 'sanctum']);

        // Créer les rôles et permissions
        $roles = ['editeur_associe', 'lecteur'];
        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
            if ($roleName === 'editeur_associe') {
                $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'categories.gerer', 'guard_name' => 'sanctum']);
                $role->givePermissionTo($permission);
            }
        }

        $this->editeur = User::factory()->create([
            'nom' => 'Editeur Test',
            'email' => 'editeur@test.com',
        ]);
        $this->editeur->assignRole('editeur_associe');
    }

    #[Test]
    public function un_editeur_peut_creer_une_categorie()
    {
        $response = $this->actingAs($this->editeur)
            ->postJson('/api/categories', [
                'nom' => 'Politique',
                'description' => 'Articles sur la politique',
                'couleur' => '#FF0000',
            ]);

        if ($response->status() !== 201) {
            fwrite(STDERR, $response->getContent() . "\n");
        }

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.nom', 'Politique');

        $this->assertDatabaseHas('categories', [
            'nom' => 'Politique',
            'slug' => 'politique',
        ]);
    }

    #[Test]
    public function on_peut_creer_une_categorie_enfant()
    {
        $parent = Category::create([
            'nom' => 'Technologie',
            'slug' => 'technologie',
            'maison_id' => tenant('id'),
        ]);

        $response = $this->actingAs($this->editeur)
            ->postJson('/api/categories', [
                'nom' => 'Intelligence Artificielle',
                'parent_id' => $parent->id,
            ]);

        $response->assertStatus(201);

        $enfant = Category::find($response->json('data.id'));
        $this->assertEquals($parent->id, $enfant->parent_id);
        $this->assertEquals(1, $enfant->niveau);
        $this->assertStringContainsString($parent->id, $enfant->chemin);
    }

    #[Test]
    public function on_peut_recuperer_l_arborescence_des_categories()
    {
        // Crée une hiérarchie de catégories
        $parent = Category::create([
            'nom' => 'Parent',
            'slug' => 'parent',
            'maison_id' => tenant('id'),
            'ordre' => 1,
        ]);

        Category::create([
            'nom' => 'Enfant 1',
            'slug' => 'enfant-1',
            'parent_id' => $parent->id,
            'maison_id' => tenant('id'),
            'ordre' => 1,
        ]);

        Category::create([
            'nom' => 'Enfant 2',
            'slug' => 'enfant-2',
            'parent_id' => $parent->id,
            'maison_id' => tenant('id'),
            'ordre' => 2,
        ]);

        $response = $this->actingAs($this->editeur)->getJson('/api/categories/arborescence');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Parent', $data[0]['nom']);
        $this->assertCount(2, $data[0]['enfants']);
    }

    #[Test]
    public function un_editeur_peut_modifier_une_categorie()
    {
        $category = Category::create([
            'nom' => 'Ancien Nom',
            'slug' => 'ancien-nom',
            'maison_id' => tenant('id'),
        ]);

        $response = $this->actingAs($this->editeur)
            ->putJson("/api/categories/{$category->id}", [
                'nom' => 'Nouveau Nom',
                'description' => 'Nouvelle description',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.nom', 'Nouveau Nom');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'nom' => 'Nouveau Nom',
            'slug' => 'nouveau-nom', // Le slug est automatiquement mis à jour
        ]);
    }

    #[Test]
    public function un_editeur_peut_supprimer_une_categorie()
    {
        $category = Category::create([
            'nom' => 'Catégorie à supprimer',
            'slug' => 'a-supprimer',
            'maison_id' => tenant('id'),
        ]);

        $response = $this->actingAs($this->editeur)
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    #[Test]
    public function supprimer_une_categorie_avec_reassignation_des_enfants()
    {
        $parent = Category::create([
            'nom' => 'Parent',
            'slug' => 'parent',
            'maison_id' => tenant('id'),
        ]);

        $newParent = Category::create([
            'nom' => 'Nouveau Parent',
            'slug' => 'nouveau-parent',
            'maison_id' => tenant('id'),
        ]);

        $enfant = Category::create([
            'nom' => 'Enfant',
            'slug' => 'enfant',
            'parent_id' => $parent->id,
            'maison_id' => tenant('id'),
        ]);

        $response = $this->actingAs($this->editeur)
            ->deleteJson("/api/categories/{$parent->id}", [
                'reassign_children' => true,
                'new_parent_id' => $newParent->id,
            ]);

        $response->assertStatus(200);

        $enfant->refresh();
        $this->assertEquals($newParent->id, $enfant->parent_id);
    }

    #[Test]
    public function une_categorie_doit_avoir_un_slug_unique()
    {
        Category::create([
            'nom' => 'Nom Unique',
            'slug' => 'nom-unique',
            'maison_id' => tenant('id'),
        ]);

        $response = $this->actingAs($this->editeur)
            ->postJson('/api/categories', [
                'nom' => 'Nom Unique',
                'maison_id' => tenant('id'),
            ]);

        $response->assertStatus(201);
        $this->assertEquals('nom-unique-1', $response->json('data.slug'));
    }

    #[Test]
    public function les_categories_sont_ordonnees()
    {
        Category::create([
            'nom' => 'Première',
            'slug' => 'premiere',
            'maison_id' => tenant('id'),
            'ordre' => 1,
        ]);

        Category::create([
            'nom' => 'Deuxième',
            'slug' => 'deuxieme',
            'maison_id' => tenant('id'),
            'ordre' => 2,
        ]);

        Category::create([
            'nom' => 'Troisième',
            'slug' => 'troisieme',
            'maison_id' => tenant('id'),
            'ordre' => 0,
        ]);

        $response = $this->actingAs($this->editeur)->getJson('/api/categories');

        $data = $response->json('data');
        $this->assertEquals('Troisième', $data[0]['nom']);
        $this->assertEquals('Première', $data[1]['nom']);
        $this->assertEquals('Deuxième', $data[2]['nom']);
    }

    #[Test]
    public function on_peut_desactiver_une_categorie()
    {
        $category = Category::create([
            'nom' => 'Catégorie Active',
            'slug' => 'active',
            'maison_id' => tenant('id'),
            'est_active' => true,
        ]);

        $response = $this->actingAs($this->editeur)
            ->putJson("/api/categories/{$category->id}", [
                'est_active' => false,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'est_active' => false,
        ]);
    }
}
