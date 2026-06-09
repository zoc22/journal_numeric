<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Article\Models\Category;
use Modules\User\Models\User;
use Modules\Workflow\Models\ReviewAssignment;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ReviewProcessTest extends TestCase
{
    use RefreshDatabase;

    protected User $editeur;
    protected User $reviewer;
    protected User $journaliste;
    protected Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.defaults.guard' => 'sanctum']);
        config(['activitylog.enabled' => false]);

        // Créer les rôles et permissions
        $rolesData = [
            'editeur_associe' => ['review.assigner', 'review.voir', 'review.supprimer', 'review.voir_historique', 'article.voir_historique'],
            'reviewer' => ['review.voir', 'review.feedback'],
            'journaliste' => ['article.creer', 'article.modifier'],
            'lecteur' => [],
        ];

        foreach ($rolesData as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
            foreach ($perms as $permName) {
                $permission = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'sanctum']);
                $role->givePermissionTo($permission);
            }
        }

        $this->editeur = User::factory()->create(['nom' => 'Editeur']);
        $this->editeur->assignRole('editeur_associe');

        $this->reviewer = User::factory()->create(['nom' => 'Reviewer']);
        $this->reviewer->assignRole('reviewer');

        $this->journaliste = User::factory()->create(['nom' => 'Journaliste']);
        $this->journaliste->assignRole('journaliste');

        // Créer le rôle lecteur qui est utilisé par UserObserver
        Role::firstOrCreate(['name' => 'lecteur', 'guard_name' => 'sanctum']);

        $categorie = Category::create([
            'nom' => 'Test',
            'slug' => 'test',
            'maison_id' => tenant('id'),
        ]);

        $this->article = Article::factory()->create([
            'auteur_id' => $this->journaliste->id,
            'statut' => 'soumis',
            'titre' => 'Article à reviewer',
        ]);
        $this->article->categories()->attach($categorie->id);
    }

    #[Test]
    public function un_editeur_peut_assigner_un_reviewer()
    {
        $response = $this->actingAs($this->editeur)
            ->postJson('/api/workflow/review-assignments', [
                'article_id' => $this->article->id,
                'reviewer_id' => $this->reviewer->id,
                'ordre_review' => 1,
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('review_assignments', [
            'article_id' => $this->article->id,
            'reviewer_id' => $this->reviewer->id,
            'statut' => 'en_attente',
        ]);
    }

    #[Test]
    public function un_reviewer_peut_voir_ses_assignations()
    {
        ReviewAssignment::create([
            'article_id' => $this->article->id,
            'reviewer_id' => $this->reviewer->id,
            'assigne_par' => $this->editeur->id,
            'statut' => 'en_attente',
        ]);

        $response = $this->actingAs($this->reviewer)
            ->getJson('/api/workflow/review-assignments/my');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->article->id, $response->json('data.0.article.id'));
    }

    #[Test]
    public function un_reviewer_peut_accepter_une_assignation()
    {
        $assignment = ReviewAssignment::create([
            'article_id' => $this->article->id,
            'reviewer_id' => $this->reviewer->id,
            'assigne_par' => $this->editeur->id,
            'statut' => 'en_attente',
        ]);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/workflow/review-assignments/{$assignment->id}/accept");

        $response->assertStatus(200);
        $this->assertEquals('accepte', $assignment->fresh()->statut);
    }

    #[Test]
    public function un_reviewer_peut_refuser_une_assignation()
    {
        $assignment = ReviewAssignment::create([
            'article_id' => $this->article->id,
            'reviewer_id' => $this->reviewer->id,
            'assigne_par' => $this->editeur->id,
            'statut' => 'en_attente',
        ]);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/workflow/review-assignments/{$assignment->id}/reject", [
                'raison' => 'Pas disponible',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('refuse', $assignment->fresh()->statut);
    }

    #[Test]
    public function un_reviewer_peut_soumettre_un_feedback()
    {
        $assignment = ReviewAssignment::create([
            'article_id' => $this->article->id,
            'reviewer_id' => $this->reviewer->id,
            'assigne_par' => $this->editeur->id,
            'statut' => 'accepte',
        ]);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/workflow/feedback/assignment/{$assignment->id}", [
                'decision' => 'validation',
                'commentaire_global' => 'Excellent article',
                'score_qualite' => 5,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('review_feedback', [ // La table s'appelle review_feedback au singulier dans le modèle, mais vérifions
            'review_assignment_id' => $assignment->id,
            'decision' => 'validation',
        ]);
        
        $this->assertTrue($assignment->fresh()->feedbackEnvoye());
    }

    #[Test]
    public function un_editeur_peut_voir_l_historique_des_reviews()
    {
        $assignment = ReviewAssignment::create([
            'article_id' => $this->article->id,
            'reviewer_id' => $this->reviewer->id,
            'assigne_par' => $this->editeur->id,
            'statut' => 'termine',
        ]);

        $assignment->feedback()->create([
            'article_id' => $this->article->id,
            'reviewer_id' => $this->reviewer->id,
            'decision' => 'validation',
            'commentaire_global' => 'Bien',
        ]);

        $response = $this->actingAs($this->editeur)
            ->getJson("/api/workflow/history/article/{$this->article->id}/reviews");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(1, $response->json('data.assignations'));
    }
}
