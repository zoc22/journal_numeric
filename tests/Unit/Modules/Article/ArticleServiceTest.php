<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Article;

use Tests\TestCase;
use Modules\Article\Models\Article;
use Modules\Article\Services\ArticleService;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArticleServiceTest extends TestCase
{
    use RefreshDatabase;

    private ArticleService $articleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->articleService = app(ArticleService::class);
    }

    /**
     * Test de création d'un article via le service.
     */
    public function test_peut_creer_un_article(): void
    {
        $user = User::factory()->create();
        $data = [
            'titre' => 'Nouvel article de test',
            'contenu' => 'Contenu de test',
            'auteur_id' => $user->id,
            'maison_id' => tenant('id') ?? '00000000-0000-0000-0000-000000000000',
        ];

        $article = $this->articleService->creer($data);

        $this->assertInstanceOf(Article::class, $article);
        $this->assertEquals('Nouvel article de test', $article->titre);
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
        // Le service crée automatiquement une version initiale
        $this->assertDatabaseHas('article_versions', ['article_id' => $article->id]);
    }

    /**
     * Test de mise à jour d'un article.
     */
    public function test_peut_mettre_a_jour_un_article(): void
    {
        $article = Article::factory()->create(['titre' => 'Ancien titre']);
        $data = ['titre' => 'Nouveau titre'];

        $updatedArticle = $this->articleService->mettreAJour($article, $data);

        $this->assertEquals('Nouveau titre', $updatedArticle->titre);
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'titre' => 'Nouveau titre']);
    }

    /**
     * Test de suppression d'un article (soft delete).
     */
    public function test_peut_supprimer_un_article(): void
    {
        $article = Article::factory()->create();

        $result = $this->articleService->supprimer($article);

        $this->assertTrue($result);
        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }

    /**
     * Test de la restauration d'une version.
     */
    public function test_peut_restaurer_une_version(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'auteur_id' => $user->id,
            'titre' => 'Version 2',
        ]);
        
        $version = \Modules\Article\Models\ArticleVersion::create([
            'article_id' => $article->id,
            'cree_par' => $user->id,
            'titre' => 'Version 1',
            'contenu' => 'Contenu version 1',
            'numero_version' => 1,
        ]);

        $this->articleService->restaurerVersion($article, $version, $user);

        $this->assertEquals('Version 1', $article->fresh()->titre);
        $this->assertEquals('Contenu version 1', $article->fresh()->contenu);
    }
}
