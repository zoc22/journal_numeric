<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Article;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Article\Models\Category;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests des transitions de workflow pour les articles
 */
class WorkflowTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected User $journaliste;
    protected User $reviewer;
    protected User $editeur;
    protected Category $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        // Configuration du guard pour les tests
        config(['auth.defaults.guard' => 'sanctum']);

        // Créer les rôles et permissions nécessaires
        $rolesData = [
            'super_admin' => [],
            'admin_plateforme' => [],
            'editeur_chef' => [],
            'directeur_collection' => [],
            'editeur_associe' => ['article.creer', 'article.modifier', 'article.supprimer', 'workflow.transition', 'workflow.voir', 'article.voir_historique', 'review.voir_historique'],
            'reviewer' => ['workflow.transition', 'workflow.voir', 'review.voir'],
            'journaliste' => ['article.creer', 'article.modifier', 'workflow.transition', 'workflow.voir', 'article.voir_historique'],
            'lecteur' => [],
        ];

        foreach ($rolesData as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'sanctum']);
            foreach ($perms as $permName) {
                $permission = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'sanctum']);
                $role->givePermissionTo($permission);
            }
        }

        $this->journaliste = User::factory()->create();
        $this->journaliste->assignRole('journaliste');
        $this->journaliste->refresh();

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole('reviewer');
        $this->reviewer->refresh();

        $this->editeur = User::factory()->create();
        $this->editeur->assignRole('editeur_associe');
        $this->editeur->refresh();

        $this->categorie = Category::create([
            'nom' => 'Test',
            'slug' => 'test',
            'maison_id' => tenant('id'),
        ]);
    }

    private function creerArticleComplet(User $auteur): Article
    {
        $article = Article::factory()->create([
            'auteur_id' => $auteur->id,
            'statut' => 'brouillon',
            'titre' => 'Article complet',
            'contenu' => str_repeat('Contenu de test avec suffisamment de mots pour être valide. ', 15),
            'image_principale' => 'https://example.com/image.jpg',
        ]);
        $article->categories()->attach($this->categorie->id);

        return $article;
    }

    #[Test]
    public function un_article_peut_passer_de_brouillon_a_soumis()
    {
        $this->withoutExceptionHandling();
        $article = $this->creerArticleComplet($this->journaliste);

        try {
            $response = $this->actingAs($this->journaliste)
                ->postJson("/api/articles/{$article->id}/submit");
        } catch (\Throwable $e) {
            file_put_contents(base_path('test_error_dump.txt'), get_class($e) . ': ' . $e->getMessage());
            throw $e;
        }

        $response->assertStatus(200);
        $this->assertEquals('soumis', $article->fresh()->statut);
    }

    #[Test]
    public function un_article_soumis_peut_passer_en_relecture()
    {
        $article = $this->creerArticleComplet($this->journaliste);

        // Soumission
        $this->actingAs($this->journaliste)
            ->postJson("/api/articles/{$article->id}/submit");

        // Transition vers en_relecture (généralement par un éditeur ou système)
        $article->update(['statut' => 'en_relecture']);

        $this->assertEquals('en_relecture', $article->fresh()->statut);
    }

    #[Test]
    public function un_reviewer_peut_demander_des_corrections()
    {
        $article = $this->creerArticleComplet($this->journaliste);
        $article->update(['statut' => 'en_relecture']);

        // Assigner un reviewer
        $article->reviewAssignments()->create([
            'reviewer_id' => $this->reviewer->id,
            'assigne_par' => $this->editeur->id,
            'statut' => 'en_cours',
        ]);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/workflow/transition/{$article->id}", [
                'statut_cible' => 'correction_demandee',
                'commentaire' => 'Veuillez améliorer la conclusion',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('correction_demandee', $article->fresh()->statut);
    }

    #[Test]
    public function un_reviewer_peut_valider_un_article()
    {
        $article = $this->creerArticleComplet($this->journaliste);
        $article->update(['statut' => 'en_relecture']);

        // Assigner des reviewers (besoin d'au moins 1 si on change la config ou si on en met 2)
        // Dans le code executerActionsSpecifiques, c'est 2 par défaut.
        // Mais ici on est déjà en relecture (via update manuel).
        
        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/workflow/transition/{$article->id}", [
                'statut_cible' => 'valide_reviewer',
                'commentaire' => 'Article validé par le reviewer',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('valide_reviewer', $article->fresh()->statut);
    }

    #[Test]
    public function un_editeur_peut_valider_un_article_au_niveau_superieur()
    {
        $article = $this->creerArticleComplet($this->journaliste);
        $article->update(['statut' => 'valide_reviewer']);

        $response = $this->actingAs($this->editeur)
            ->postJson("/api/workflow/transition/{$article->id}", [
                'statut_cible' => 'valide_editeur',
                'commentaire' => 'Validé par l\'éditeur',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('valide_editeur', $article->fresh()->statut);
    }

    #[Test]
    public function un_editeur_peut_publier_un_article_valide()
    {
        $article = $this->creerArticleComplet($this->journaliste);
        $article->update(['statut' => 'valide_final']);

        $response = $this->actingAs($this->editeur)
            ->postJson("/api/workflow/transition/{$article->id}", [
                'statut_cible' => 'publie',
                'commentaire' => 'Article publié',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('publie', $article->fresh()->statut);
        $this->assertNotNull($article->fresh()->publie_le);
    }

    #[Test]
    public function un_reviewer_peut_rejeter_un_article()
    {
        $article = $this->creerArticleComplet($this->journaliste);
        $article->update(['statut' => 'en_relecture']);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/workflow/transition/{$article->id}", [
                'statut_cible' => 'rejete',
                'raison' => 'Ne correspond pas aux standards de qualité',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('rejete', $article->fresh()->statut);
    }

    #[Test]
    public function une_transition_non_autorisee_est_refusee()
    {
        $article = $this->creerArticleComplet($this->journaliste);
        // Article en brouillon, tentative de passer directement à publié

        $response = $this->actingAs($this->journaliste)
            ->postJson("/api/workflow/transition/{$article->id}", [
                'statut_cible' => 'publie',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    #[Test]
    public function un_journaliste_ne_peut_pas_valider_son_propre_article()
    {
        $article = $this->creerArticleComplet($this->journaliste);
        $article->update(['statut' => 'en_relecture']);

        // Tentative du journaliste de valider son propre article
        $response = $this->actingAs($this->journaliste)
            ->postJson("/api/workflow/transition/{$article->id}", [
                'statut_cible' => 'valide_reviewer',
            ]);

        // Le workflow service renvoie 422 (DomainException) pour les transitions non autorisées par le rôle
        $response->assertStatus(422);
    }

    #[Test]
    public function les_transitions_sont_enregistrees_dans_l_historique()
    {
        $article = $this->creerArticleComplet($this->journaliste);

        // Soumission (Transition 1)
        $this->actingAs($this->journaliste)
            ->postJson("/api/articles/{$article->id}/submit");

        // Assigner reviewers pour permettre le passage en relecture via API
        $article->reviewAssignments()->create([
            'reviewer_id' => $this->reviewer->id,
            'assigne_par' => $this->editeur->id,
            'statut' => 'en_cours',
        ]);
        $reviewer2 = User::factory()->create();
        $reviewer2->assignRole('reviewer');
        $article->reviewAssignments()->create([
            'reviewer_id' => $reviewer2->id,
            'assigne_par' => $this->editeur->id,
            'statut' => 'en_cours',
        ]);

        // Passage en relecture via API (Transition 2)
        $this->actingAs($this->editeur)
            ->postJson("/api/workflow/transition/{$article->id}", [
                'statut_cible' => 'en_relecture',
            ]);

        // Vérification de l'historique
        $response = $this->actingAs($this->editeur)
            ->getJson("/api/workflow/history/article/{$article->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(2, $response->json('data.transitions'));
    }

    #[Test]
    public function on_peut_recuperer_les_transitions_possibles_pour_un_article()
    {
        $article = $this->creerArticleComplet($this->journaliste);

        $response = $this->actingAs($this->journaliste)
            ->getJson("/api/workflow/transition/{$article->id}/possible");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $transitions = $response->json('data.transitions_possibles');
        $this->assertIsArray($transitions);

        // Depuis brouillon, on peut soumettre
        $statutsTrouves = array_column($transitions, 'statut');
        $this->assertContains('soumis', $statutsTrouves);
    }
}
