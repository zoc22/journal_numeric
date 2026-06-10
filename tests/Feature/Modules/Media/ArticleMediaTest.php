<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Media;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Article\Models\Category;
use Modules\Media\Models\Media;
use Modules\User\Models\User;
use Tests\TestCase;

class ArticleMediaTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Article $article;
    protected Media $media;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialisation de la tenancy
        $tenant = \App\Models\Tenant::firstOrCreate(['id' => '00000000-0000-0000-0000-000000000000'], ['name' => 'Test Maison']);
        if (!$tenant->domains()->where('domain', 'localhost')->exists()) {
            $tenant->domains()->create(['domain' => 'localhost']);
        }
        tenancy()->initialize($tenant);

        $this->user = User::factory()->create();
        $this->user->assignRole('journaliste');

        $categorie = Category::create([
            'nom' => 'Test',
            'slug' => 'test',
            'maison_id' => tenant('id'),
        ]);

        $this->article = Article::create([
            'titre' => 'Article de test',
            'contenu' => 'Contenu de test',
            'auteur_id' => $this->user->id,
            'maison_id' => tenant('id'),
            'statut' => 'brouillon',
        ]);
        $this->article->categories()->attach($categorie->id);

        $this->media = Media::create([
            'nom_fichier' => 'test.jpg',
            'nom_original' => 'test.jpg',
            'chemin' => 'media/test.jpg',
            'disque' => 'public',
            'type_mime' => 'image/jpeg',
            'extension' => 'jpg',
            'taille' => 1024,
            'hash' => 'fakehash',
            'type' => 'image',
            'televerse_par' => $this->user->id,
            'maison_id' => tenant('id'),
        ]);
    }

    /** @test */
    public function on_peut_attacher_un_media_a_un_article()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/workflow/article-media/{$this->article->id}/{$this->media->id}", [
                'type_usage' => 'inline',
                'ordre_affichage' => 1,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('article_media', [
            'article_id' => $this->article->id,
            'media_id' => $this->media->id,
            'type_usage' => 'inline',
        ]);
    }

    /** @test */
    public function on_peut_definir_une_image_de_couverture()
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/workflow/article-media/{$this->article->id}/{$this->media->id}/cover");

        $response->assertStatus(200);
        $this->assertDatabaseHas('article_media', [
            'article_id' => $this->article->id,
            'media_id' => $this->media->id,
            'type_usage' => 'couverture',
        ]);

        $this->assertEquals($this->media->url, $this->article->fresh()->image_principale);
    }

    /** @test */
    public function on_peut_detacher_un_media_dun_article()
    {
        $this->article->medias()->attach($this->media->id, [
            'id' => \Illuminate\Support\Str::uuid(),
            'type_usage' => 'inline',
            'ordre_affichage' => 1,
            'est_actif' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/workflow/article-media/{$this->article->id}/{$this->media->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('article_media', [
            'article_id' => $this->article->id,
            'media_id' => $this->media->id,
        ]);
    }
}
