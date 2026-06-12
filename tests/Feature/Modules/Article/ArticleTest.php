<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Article;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\User\Models\User;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests de base pour le module Article
 */
class ArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Configuration du guard pour les tests
        config(['auth.defaults.guard' => 'sanctum']);

        // Créer le rôle lecteur qui est utilisé par UserObserver
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'sanctum']);
    }

    #[Test]
    public function un_utilisateur_peut_lister_ses_articles()
    {
        /** @var \Illuminate\Contracts\Auth\Authenticatable $user */
        $user = User::factory()->create();
        
        Article::factory()->count(3)->create([
            'auteur_id' => $user->getAuthIdentifier(),
            'maison_id' => tenant('id'),
        ]);

        $response = $this->actingAs($user)->getJson('/api/articles');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }
}
