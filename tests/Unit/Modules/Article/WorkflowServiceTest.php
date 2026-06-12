<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Article;

use Tests\TestCase;
use Modules\Article\Models\Article;
use Modules\User\Models\User;
use Modules\Workflow\Services\WorkflowService;
use Modules\Workflow\Enums\ArticleStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class WorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private WorkflowService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflowService = app(WorkflowService::class);
        
        // Configuration minimale des rôles pour WorkflowService
        Role::firstOrCreate(['name' => 'admin_plateforme', 'guard_name' => 'sanctum']);
    }

    /**
     * Test que le WorkflowService peut faire transiter un article.
     */
    public function test_peut_effectuer_une_transition_de_statut_sur_article(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin_plateforme');

        $article = Article::factory()->create([
            'statut' => ArticleStatus::BROUILLON->value,
            'maison_id' => tenant('id') ?? '00000000-0000-0000-0000-000000000000',
        ]);

        $categorie = \Modules\Article\Models\Category::create([
            'nom' => 'Test',
            'slug' => 'test-cat',
            'maison_id' => $article->maison_id,
        ]);
        $article->categories()->attach($categorie->id);

        $transition = $this->workflowService->transition(
            $article,
            ArticleStatus::SOUMIS->value,
            $user,
            'Soumission initiale pour test'
        );

        $this->assertEquals(ArticleStatus::SOUMIS->value, $article->fresh()->statut);
        $this->assertDatabaseHas('workflow_transitions', [
            'workflowable_id' => $article->id,
            'statut_cible' => ArticleStatus::SOUMIS->value,
            'utilisateur_id' => $user->id,
        ]);
    }
}
