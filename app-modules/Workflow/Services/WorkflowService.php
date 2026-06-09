<?php

declare(strict_types=1);

namespace Modules\Workflow\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Modules\Article\Models\Article;
use Modules\User\Models\User;
use Modules\Workflow\Enums\ArticleStatus;
use Modules\Workflow\Enums\RoleLevel;
use Modules\Workflow\Events\WorkflowTransitionExecuted;
use Modules\Workflow\Models\ReviewIteration;
use Modules\Workflow\Models\WorkflowTransition;

/**
 * Service de gestion du workflow éditorial
 *
 * Centralise toute la logique métier liée aux transitions
 * d'état des articles dans le workflow de publication.
 *
 * Responsabilités:
 * - Validation des transitions autorisées
 * - Exécution des changements d'état
 * - Journalisation des transitions
 * - Déclenchement des événements associés
 * - Gestion des itérations de correction
 */
class WorkflowService
{
    /**
     * @var ValidationRulesService
     */
    protected ValidationRulesService $validationRules;

    /**
     * Constructeur
     */
    public function __construct(ValidationRulesService $validationRules)
    {
        $this->validationRules = $validationRules;
    }

    /**
     * Valide et exécute une transition de workflow
     */
    public function transition(
        Article $article,
        string $nouveauStatut,
        User $utilisateur,
        ?string $commentaire = null,
        ?array $metadonnees = null
    ): WorkflowTransition {
        // Détermine le rôle et niveau de l'utilisateur
        $roleNiveau = $this->getUserRoleLevel($utilisateur, $article->maison_id);
        $statutActuel = ArticleStatus::fromString($article->statut);
        $statutCible = ArticleStatus::fromString($nouveauStatut);

        // Vérifie si la transition est autorisée
        if (!$statutActuel->transitionAutorisee($statutCible, $roleNiveau)) {
            throw new \DomainException(sprintf(
                'Transition non autorisée de "%s" vers "%s" pour un utilisateur de niveau %d',
                $statutActuel->label(),
                $statutCible->label(),
                $roleNiveau
            ));
        }

        // Valide les prérequis spécifiques
        $this->validationRules->validerPreRequisTransition($article, $statutCible, $utilisateur);

        // Exécute la transition dans une transaction
        return DB::transaction(function () use ($article, $statutCible, $utilisateur, $commentaire, $metadonnees, $roleNiveau, $statutActuel) {
            $ancienStatut = $article->statut;

            // Met à jour le statut de l'article
            $article->statut = $statutCible->value;

            // Actions spécifiques selon le nouveau statut
            $this->executerActionsSpecifiques($article, $statutCible, $utilisateur);

            // Sauvegarde
            $article->save();

            // Récupère l'IP et le user agent
            $request = request();
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();

            // Crée l'enregistrement de transition
            $transition = WorkflowTransition::create([
                'workflowable_type' => get_class($article),
                'workflowable_id' => $article->id,
                'statut_origine' => $ancienStatut,
                'statut_cible' => $statutCible->value,
                'utilisateur_id' => $utilisateur->id,
                'role_niveau' => $roleNiveau,
                'role_utilise' => $this->getUserRoleName($utilisateur, $article->maison_id),
                'commentaires' => $commentaire,
                'metadonnees' => $metadonnees,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'continent' => $utilisateur->continent,
                'pays' => $utilisateur->pays,
                'ville' => $utilisateur->ville,
            ]);

            // Gère les itérations de correction
            $this->gererIterations($article, $statutCible, $utilisateur, $commentaire);

            // Déclenche les événements
            $this->declencherEvenements($article, $statutCible, $utilisateur, $commentaire);

            // Log la transition
            if (config('workflow.logging.enabled', true)) {
                Log::info('Transition de workflow exécutée', [
                    'article_id' => $article->id,
                    'transition_id' => $transition->id,
                    'from' => $ancienStatut,
                    'to' => $statutCible->value,
                    'user_id' => $utilisateur->id,
                ]);
            }

            // Émet un événement générique
            Event::dispatch(new WorkflowTransitionExecuted($transition, $article));

            return $transition;
        });
    }

    /**
     * Soumet un article pour relecture
     */
    public function soumettreArticle(Article $article, User $journaliste, ?string $noteSubmission = null): WorkflowTransition
    {
        // Vérifie que l'utilisateur est bien l'auteur ou a les droits
        if ($article->auteur_id !== $journaliste->id && !$journaliste->hasRole(['editeur_associe', 'editeur_chef'])) {
            throw new \DomainException("Seul l'auteur de l'article ou un éditeur peut le soumettre");
        }

        // Valide que l'article est complet
        $this->validationRules->validerArticleComplet($article);

        // Crée une nouvelle version avant soumission
        $article->creerNouvelleVersion($journaliste, 'Version soumise pour relecture');

        return $this->transition($article, ArticleStatus::SOUMIS->value, $journaliste, $noteSubmission);
    }

    /**
     * Demande des corrections sur un article
     */
    public function demanderCorrections(
        Article $article,
        User $reviewer,
        string $commentaires,
        ?array $annotations = null
    ): WorkflowTransition {
        $metadonnees = ['annotations' => $annotations];

        // Récupère ou crée l'itération en cours
        /** @var ReviewIteration|null $iteration */
        $iteration = ReviewIteration::where('article_id', $article->id)
            ->where('statut', 'correction_demandee')
            ->first();

        if (!$iteration) {
            $numeroIteration = ReviewIteration::prochaineIteration($article->id);
            /** @var ReviewIteration $iteration */
            $iteration = ReviewIteration::create([
                'article_id' => $article->id,
                'journaliste_id' => $article->auteur_id,
                'numero_iteration' => $numeroIteration,
                'statut' => 'correction_demandee',
            ]);
            $iteration->demanderCorrections();
        }

        return $this->transition($article, ArticleStatus::CORRECTION_DEMANDEE->value, $reviewer, $commentaires, $metadonnees);
    }

    /**
     * Soumet des corrections (journaliste)
     */
    public function soumettreCorrections(
        Article $article,
        User $journaliste,
        string $corrections,
        ?string $notes = null
    ): WorkflowTransition {
        // Vérifie que l'utilisateur est l'auteur
        if ($article->auteur_id !== $journaliste->id) {
            throw new \DomainException("Seul l'auteur de l'article peut soumettre des corrections");
        }

        // Trouve l'itération en cours
        /** @var ReviewIteration|null $iteration */
        $iteration = ReviewIteration::where('article_id', $article->id)
            ->where('statut', 'correction_demandee')
            ->first();

        if (!$iteration) {
            throw new \DomainException("Aucune demande de correction en cours pour cet article");
        }

        // Enregistre les corrections
        $iteration->soumettreCorrections($corrections, $notes);

        // Crée une nouvelle version avec les corrections
        $article->creerNouvelleVersion($journaliste, "Corrections apportées - Itération {$iteration->numero_iteration}");

        return $this->transition($article, ArticleStatus::SOUMIS->value, $journaliste, $notes);
    }

    /**
     * Approuve un article (validation progressive)
     */
    public function approuverArticle(Article $article, User $validateur, ?string $commentaires = null): WorkflowTransition
    {
        $prochainStatut = $this->determinerProchainStatutValidation($article, $validateur);

        if (!$prochainStatut) {
            throw new \DomainException("Impossible de déterminer le prochain statut de validation pour cet utilisateur");
        }

        return $this->transition($article, $prochainStatut->value, $validateur, $commentaires);
    }

    /**
     * Publie un article
     */
    public function publierArticle(Article $article, User $editeur, ?string $messagePublication = null): WorkflowTransition
    {
        return $this->transition($article, ArticleStatus::PUBLIE->value, $editeur, $messagePublication);
    }

    /**
     * Archive un article
     */
    public function archiverArticle(Article $article, User $utilisateur, ?string $raison = null): WorkflowTransition
    {
        return $this->transition($article, ArticleStatus::ARCHIVE->value, $utilisateur, $raison);
    }

    /**
     * Rejette un article
     */
    public function rejeterArticle(Article $article, User $reviewer, string $raison): WorkflowTransition
    {
        return $this->transition($article, ArticleStatus::REJETE->value, $reviewer, $raison);
    }

    /**
     * Obtient l'historique complet des transitions d'un article
     */
    public function obtenirHistorique(Article $article)
    {
        return $article->transitionsWorkflow()
            ->with('utilisateur')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Vérifie si une transition est possible
     */
    public function transitionPossible(Article $article, string $statutCible, User $utilisateur): bool
    {
        try {
            $roleNiveau = $this->getUserRoleLevel($utilisateur, $article->maison_id);
            $statutActuel = ArticleStatus::fromString($article->statut);
            $statutCibleEnum = ArticleStatus::fromString($statutCible);

            return $statutActuel->transitionAutorisee($statutCibleEnum, $roleNiveau);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtient le niveau de rôle de l'utilisateur
     */
    protected function getUserRoleLevel(User $utilisateur, ?string $maisonId): int
    {
        // Super admin = niveau maximum
        if ($utilisateur->hasRole('super_admin')) {
            return RoleLevel::SUPER_ADMIN->value;
        }

        // Admin plateforme
        if ($utilisateur->hasRole('admin_plateforme')) {
            return RoleLevel::ADMIN_PLATEFORME->value;
        }

        // Si on est dans le contexte d'une maison, on vérifie d'abord le rôle local
        if ($maisonId) {
            $membre = $utilisateur->membres()
                ->where('maison_id', $maisonId)
                ->where('est_actif', true)
                ->with('role')
                ->first();
            
            if ($membre && $membre->role) {
                return RoleLevel::fromNom($membre->role->name)->value;
            }
        }

        // Sinon (ou si pas de rôle local), on vérifie les rôles globaux du plus haut au plus bas
        if ($utilisateur->hasRole('editeur_chef')) return RoleLevel::EDITEUR_CHEF->value;
        if ($utilisateur->hasRole('directeur_collection')) return RoleLevel::DIRECTEUR_COLLECTION->value;
        if ($utilisateur->hasRole('editeur_associe')) return RoleLevel::EDITEUR_ASSOCIE->value;
        if ($utilisateur->hasRole('reviewer')) return RoleLevel::REVIEWER->value;
        if ($utilisateur->hasRole('journaliste')) return RoleLevel::JOURNALISTE->value;

        return RoleLevel::LECTEUR->value;
    }

    /**
     * Obtient le nom du rôle de l'utilisateur
     */
    protected function getUserRoleName(User $utilisateur, ?string $maisonId): ?string
    {
        if ($maisonId) {
            $membre = $utilisateur->membres()
                ->where('maison_id', $maisonId)
                ->where('est_actif', true)
                ->with('role')
                ->first();
            
            if ($membre && $membre->role) {
                return $membre->role->name;
            }
        }

        // Rôles globaux
        $roles = [
            'editeur_chef',
            'directeur_collection',
            'editeur_associe',
            'reviewer',
            'journaliste',
            'lecteur',
        ];

        foreach ($roles as $role) {
            if ($utilisateur->hasRole($role)) {
                return $role;
            }
        }

        return 'lecteur';
    }

    /**
     * Exécute des actions spécifiques selon le nouveau statut
     */
    protected function executerActionsSpecifiques(Article $article, ArticleStatus $nouveauStatut, User $utilisateur): void
    {
        switch ($nouveauStatut) {
            case ArticleStatus::PUBLIE:
                if (is_null($article->publie_le)) {
                    $article->publie_le = now();
                }
                break;

            case ArticleStatus::ARCHIVE:
                if (is_null($article->archive_le)) {
                    $article->archive_le = now();
                }
                break;

            case ArticleStatus::EN_RELECTURE:
                // Vérifie que le nombre minimum de reviewers est assigné
                $minReviewers = config('workflow.review.min_reviewers_per_article', 2);
                $reviewersCount = $article->reviewAssignments()->actives()->count();

                if ($reviewersCount < $minReviewers) {
                    throw new \DomainException(
                        "L'article doit avoir au moins {$minReviewers} reviewer(s) assigné(s) avant de passer en relecture"
                    );
                }
                break;

            default:
                // Rien à faire
                break;
        }
    }

    /**
     * Détermine le prochain statut de validation selon le rôle
     */
    protected function determinerProchainStatutValidation(Article $article, User $utilisateur): ?ArticleStatus
    {
        $statutActuel = ArticleStatus::fromString($article->statut);
        $roleNiveau = $this->getUserRoleLevel($utilisateur, $article->maison_id);

        $sequence = [
            ArticleStatus::EN_RELECTURE->value => ArticleStatus::VALIDE_REVIEWER,
            ArticleStatus::VALIDE_REVIEWER->value => ArticleStatus::VALIDE_EDITEUR,
            ArticleStatus::VALIDE_EDITEUR->value => ArticleStatus::VALIDE_DIRECTEUR,
            ArticleStatus::VALIDE_DIRECTEUR->value => ArticleStatus::VALIDE_FINAL,
        ];

        if (isset($sequence[$statutActuel->value])) {
            $prochain = $sequence[$statutActuel->value];

            // Vérifie que l'utilisateur a le niveau requis
            $niveauRequis = $prochain->niveauValidationRequis($prochain);
            if ($roleNiveau >= $niveauRequis) {
                return $prochain;
            }
        }

        return null;
    }

    /**
     * Gère les itérations de correction
     */
    protected function gererIterations(Article $article, ArticleStatus $nouveauStatut, User $utilisateur, ?string $commentaire = null): void
    {
        if ($nouveauStatut === ArticleStatus::CORRECTION_DEMANDEE) {
            // Déjà géré dans demanderCorrections
            return;
        }

        if ($nouveauStatut === ArticleStatus::SOUMIS && $article->statut === 'correction_demandee') {
            // Mise à jour de l'itération après soumission des corrections
            /** @var ReviewIteration|null $iteration */
            $iteration = ReviewIteration::where('article_id', $article->id)
                ->where('statut', 'correction_soumise')
                ->first();

            if ($iteration) {
                $iteration->terminer();
            }
        }

        if ($nouveauStatut->estStatutValidation()) {
            // Termine l'itération en cours si elle existe
            /** @var ReviewIteration|null $iteration */
            $iteration = ReviewIteration::where('article_id', $article->id)
                ->where('statut', 'correction_soumise')
                ->first();

            if ($iteration) {
                $iteration->terminer();
            }
        }
    }

    /**
     * Déclenche les événements associés à la transition
     */
    protected function declencherEvenements(Article $article, ArticleStatus $nouveauStatut, User $utilisateur, ?string $commentaire = null): void
    {
        $events = [
            ArticleStatus::SOUMIS->value => \Modules\Workflow\Events\ArticleSubmitted::class,
            ArticleStatus::CORRECTION_DEMANDEE->value => \Modules\Workflow\Events\CorrectionRequested::class,
            ArticleStatus::PUBLIE->value => \Modules\Workflow\Events\ArticlePublished::class,
            ArticleStatus::REJETE->value => \Modules\Workflow\Events\ArticleRejected::class,
        ];

        if (isset($events[$nouveauStatut->value])) {
            $eventClass = $events[$nouveauStatut->value];
            Event::dispatch(new $eventClass($article, $utilisateur, $commentaire));
        }

        if ($nouveauStatut->estStatutValidation()) {
            Event::dispatch(new \Modules\Workflow\Events\ArticleValidated($article, $utilisateur, $nouveauStatut, $commentaire));
        }
    }
}
