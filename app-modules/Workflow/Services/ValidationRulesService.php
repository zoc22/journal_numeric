<?php

declare(strict_types=1);

namespace Modules\Workflow\Services;

use Modules\Article\Models\Article;
use Modules\User\Models\User;
use Modules\Workflow\Enums\ArticleStatus;

/**
 * Service de validation des règles métier pour le workflow
 *
 * Contient toutes les règles de validation spécifiques
 * aux transitions de workflow.
 */
class ValidationRulesService
{
    /**
     * Valide les prérequis avant une transition
     */
    public function validerPreRequisTransition(Article $article, ArticleStatus $statutCible, User $utilisateur): void
    {
        switch ($statutCible) {
            case ArticleStatus::PUBLIE:
                $this->validerPublication($article);
                break;

            case ArticleStatus::SOUMIS:
                $this->validerSoumission($article);
                break;

            case ArticleStatus::EN_RELECTURE:
                $this->validerMiseEnRelecture($article);
                break;

            case ArticleStatus::VALIDE_REVIEWER:
            case ArticleStatus::VALIDE_EDITEUR:
            case ArticleStatus::VALIDE_DIRECTEUR:
            case ArticleStatus::VALIDE_FINAL:
                $this->validerValidation($article, $statutCible);
                break;

            case ArticleStatus::REJETE:
                $this->validerRejet($article);
                break;
        }
    }

    /**
     * Valide qu'un article est complet pour publication
     */
    protected function validerPublication(Article $article): void
    {
        $erreurs = [];

        if (empty(trim($article->contenu))) {
            $erreurs[] = 'Le contenu est obligatoire';
        }

        if (empty($article->image_principale)) {
            $erreurs[] = 'Une image à la une est requise';
        }

        if ($article->categories()->count() === 0) {
            $erreurs[] = 'Au moins une catégorie est requise';
        }

        if (!empty($erreurs)) {
            throw new \DomainException('Publication impossible: ' . implode(', ', $erreurs));
        }
    }

    /**
     * Valide qu'un article est complet pour soumission
     */
    protected function validerSoumission(Article $article): void
    {
        $erreurs = [];

        if (empty(trim($article->titre))) {
            $erreurs[] = 'Le titre est obligatoire';
        }

        if (empty(trim($article->contenu))) {
            $erreurs[] = 'Le contenu est obligatoire';
        }

        if (strlen(trim(strip_tags($article->contenu))) < 100) {
            $erreurs[] = 'Le contenu doit faire au moins 100 caractères';
        }

        if ($article->categories()->count() === 0) {
            $erreurs[] = 'Au moins une catégorie est requise';
        }

        if (!empty($erreurs)) {
            throw new \DomainException('Article incomplet: ' . implode(', ', $erreurs));
        }
    }

    /**
     * Valide la mise en relecture
     */
    protected function validerMiseEnRelecture(Article $article): void
    {
        $minReviewers = config('workflow.review.min_reviewers_per_article', 2);
        $reviewersCount = $article->reviewAssignments()
            ->whereNotIn('statut', ['refuse', 'expire'])
            ->count();

        if ($reviewersCount < $minReviewers) {
            throw new \DomainException(
                "L'article doit avoir au moins {$minReviewers} reviewer(s) assigné(s) avant la relecture"
            );
        }
    }

    /**
     * Valide une validation à un niveau donné
     */
    protected function validerValidation(Article $article, ArticleStatus $statutCible): void
    {
        // Vérifie le niveau précédent si nécessaire
        $niveauxPrecedents = [
            ArticleStatus::VALIDE_EDITEUR->value => ArticleStatus::VALIDE_REVIEWER->value,
            ArticleStatus::VALIDE_DIRECTEUR->value => ArticleStatus::VALIDE_EDITEUR->value,
            ArticleStatus::VALIDE_FINAL->value => ArticleStatus::VALIDE_DIRECTEUR->value,
        ];

        if (isset($niveauxPrecedents[$statutCible->value])) {
            $statutPrecedent = $niveauxPrecedents[$statutCible->value];
            if ($article->statut !== $statutPrecedent) {
                throw new \DomainException(
                    "L'article doit d'abord être validé au niveau précédent ({$statutPrecedent})"
                );
            }
        }
    }

    /**
     * Valide le rejet d'un article
     */
    protected function validerRejet(Article $article): void
    {
        // Aucune règle spécifique pour le rejet
        // Tout article peut être rejeté à n'importe quelle étape (sauf publié)
        if ($article->statut === ArticleStatus::PUBLIE->value) {
            throw new \DomainException("Un article publié ne peut pas être rejeté");
        }
    }

    /**
     * Valide qu'un article est complet (générique)
     */
    public function validerArticleComplet(Article $article): void
    {
        $this->validerSoumission($article);
    }
}
