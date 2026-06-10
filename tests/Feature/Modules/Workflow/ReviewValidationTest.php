<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Workflow;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Article\Models\Category;
use Modules\User\Models\User;
use Modules\Workflow\Enums\ArticleStatus;
use Modules\Workflow\Services\WorkflowService;
use Tests\TestCase;

/**
 * Tests des validations multi-niveaux
 *
 * Vérifie le bon fonctionnement du workflow hiérarchique :
 * - Reviewer → Éditeur Associé → Directeur → Éditeur en Chef → Publication
 */
class ReviewValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $journaliste;
    protected User $reviewer;
    protected User $editeurAssocie;
    protected User $directeur;
    protected User $editeurChef;
    protected Category $categorie;
    protected WorkflowService $workflowService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialisation de la tenancy
        $tenant = \App\Models\Tenant::firstOrCreate(['id' => '00000000-0000-0000-0000-000000000000'], ['name' => 'Test Maison']);
        if (!$tenant->domains()->where('domain', 'localhost')->exists()) {
            $tenant->domains()->create(['domain' => 'localhost']);
        }
        tenancy()->initialize($tenant);

        // Création des utilisateurs
        $this->journaliste = User::factory()->create(['nom' => 'Journaliste']);
        $this->journaliste->assignRole('journaliste');

        $this->reviewer = User::factory()->create(['nom' => 'Reviewer']);
        $this->reviewer->assignRole('reviewer');

        $this->editeurAssocie = User::factory()->create(['nom' => 'Éditeur Associé']);
        $this->editeurAssocie->assignRole('editeur_associe');

        $this->directeur = User::factory()->create(['nom' => 'Directeur']);
        $this->directeur->assignRole('directeur_collection');

        $this->editeurChef = User::factory()->create(['nom' => 'Éditeur en Chef']);
        $this->editeurChef->assignRole('editeur_chef');

        // Création d'une catégorie
        $this->categorie = Category::create([
            'nom' => 'Test',
            'slug' => 'test',
            'maison_id' => tenant('id'),
        ]);

        $this->workflowService = app(WorkflowService::class);
    }

    /**
     * Crée un article complet et le soumet
     */
    private function creerEtSoumettreArticle(): Article
    {
        $article = Article::factory()->create([
            'auteur_id' => $this->journaliste->id,
            'statut' => ArticleStatus::BROUILLON->value,
            'titre' => 'Article de test',
            'contenu' => str_repeat('Contenu de test avec suffisamment de mots pour être valide. ', 20),
            'image_principale' => 'test-image.jpg',
        ]);
        $article->categories()->attach($this->categorie->id);

        // Soumission
        $this->workflowService->soumettreArticle($article, $this->journaliste);

        // Passage en relecture (normalement fait par un éditeur)
        $article->update(['statut' => ArticleStatus::EN_RELECTURE->value]);

        return $article;
    }

    /**
     * @test
     */
    public function un_reviewer_peut_valider_un_article_en_relecture()
    {
        $article = $this->creerEtSoumettreArticle();

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/workflow/validate/reviewer/{$article->id}", [
                'commentaire' => 'Article bien écrit, je le valide',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Article validé avec succès par le reviewer');

        $this->assertEquals(ArticleStatus::VALIDE_REVIEWER->value, $article->fresh()->statut);
    }

    /**
     * @test
     */
    public function un_editeur_associe_peut_valider_apres_le_reviewer()
    {
        $article = $this->creerEtSoumettreArticle();

        // Validation par reviewer
        $article->update(['statut' => ArticleStatus::VALIDE_REVIEWER->value]);

        $response = $this->actingAs($this->editeurAssocie)
            ->postJson("/api/workflow/validate/editeur-associe/{$article->id}", [
                'commentaire' => 'Validé par l\'éditeur associé',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertEquals(ArticleStatus::VALIDE_EDITEUR->value, $article->fresh()->statut);
    }

    /**
     * @test
     */
    public function un_directeur_peut_valider_apres_lediteur_associe()
    {
        $article = $this->creerEtSoumettreArticle();

        // Validations précédentes
        $article->update(['statut' => ArticleStatus::VALIDE_EDITEUR->value]);

        $response = $this->actingAs($this->directeur)
            ->postJson("/api/workflow/validate/directeur/{$article->id}", [
                'commentaire' => 'Validé par le directeur',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertEquals(ArticleStatus::VALIDE_DIRECTEUR->value, $article->fresh()->statut);
    }

    /**
     * @test
     */
    public function un_editeur_chef_peut_faire_la_validation_finale()
    {
        $article = $this->creerEtSoumettreArticle();

        // Validations précédentes
        $article->update(['statut' => ArticleStatus::VALIDE_DIRECTEUR->value]);

        $response = $this->actingAs($this->editeurChef)
            ->postJson("/api/workflow/validate/final/{$article->id}", [
                'commentaire' => 'Validation finale accordée',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertEquals(ArticleStatus::VALIDE_FINAL->value, $article->fresh()->statut);
    }

    /**
     * @test
     */
    public function un_editeur_chef_peut_publier_apres_validation_finale()
    {
        $article = $this->creerEtSoumettreArticle();

        // Validation finale
        $article->update(['statut' => ArticleStatus::VALIDE_FINAL->value]);

        $response = $this->actingAs($this->editeurChef)
            ->postJson("/api/workflow/validate/publier/{$article->id}", [
                'commentaire' => 'Félicitations pour cet excellent article!',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('message', 'Article publié avec succès');

        $this->assertEquals(ArticleStatus::PUBLIE->value, $article->fresh()->statut);
        $this->assertNotNull($article->fresh()->publie_le);
    }

    /**
     * @test
     */
    public function un_reviewer_ne_peut_pas_valider_un_article_deja_valide()
    {
        $article = $this->creerEtSoumettreArticle();

        // Déjà validé par un autre reviewer
        $article->update(['statut' => ArticleStatus::VALIDE_REVIEWER->value]);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/workflow/validate/reviewer/{$article->id}");

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * @test
     */
    public function un_editeur_associe_ne_peut_pas_valider_sans_validation_reviewer()
    {
        $article = $this->creerEtSoumettreArticle();

        // L'article n'a pas la validation reviewer (juste en relecture)
        $article->update(['statut' => ArticleStatus::EN_RELECTURE->value]);

        $response = $this->actingAs($this->editeurAssocie)
            ->postJson("/api/workflow/validate/editeur-associe/{$article->id}");

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
    }

    /**
     * @test
     */
    public function un_journaliste_ne_peut_pas_faire_de_validation()
    {
        $article = $this->creerEtSoumettreArticle();
        $article->update(['statut' => ArticleStatus::EN_RELECTURE->value]);

        $response = $this->actingAs($this->journaliste)
            ->postJson("/api/workflow/validate/reviewer/{$article->id}");

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function on_peut_consulter_les_niveaux_de_validation_disponibles()
    {
        $article = $this->creerEtSoumettreArticle();
        $article->update(['statut' => ArticleStatus::VALIDE_REVIEWER->value]);

        $response = $this->actingAs($this->editeurAssocie)
            ->getJson("/api/workflow/validate/disponibles/{$article->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $disponibles = $response->json('data.niveaux_disponibles');
        $this->assertCount(1, $disponibles);
        $this->assertEquals('editeur_associe', $disponibles[0]['niveau']);
    }

    #[Test]
    public function un_reviewer_peut_rejeter_un_article()
    {
        $article = $this->creerEtSoumettreArticle();
        $article->update(['statut' => ArticleStatus::EN_RELECTURE->value]);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/workflow/transition/{$article->id}", [
                'statut_cible' => 'rejete',
                'raison' => 'Ne correspond pas aux standards de qualité',
                'commentaire' => 'Veuillez revoir la structure de l\'article',
            ]);

        $response->assertStatus(200);
        $this->assertEquals(ArticleStatus::REJETE->value, $article->fresh()->statut);
    }
}
