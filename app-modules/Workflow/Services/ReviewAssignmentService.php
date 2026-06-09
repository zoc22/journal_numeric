<?php

declare(strict_types=1);

namespace Modules\Workflow\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Article\Models\Article;
use Modules\User\Models\User;
use Modules\Workflow\Models\ReviewAssignment;
use Modules\Workflow\Models\ReviewFeedback;

/**
 * Service de gestion des assignations de review
 *
 * Gère l'assignation des reviewers aux articles et le suivi
 * des feedbacks.
 */
class ReviewAssignmentService
{
    /**
     * Assigne un reviewer à un article
     */
    public function assigner(Article $article, User $reviewer, User $assignePar, ?int $ordre = null): ReviewAssignment
    {
        // Vérifie que le reviewer n'est pas déjà assigné
        $existant = ReviewAssignment::where('article_id', $article->id)
            ->where('reviewer_id', $reviewer->id)
            ->first();

        if ($existant) {
            throw new \DomainException("Ce reviewer est déjà assigné à cet article");
        }

        // Vérifie que le reviewer a bien le rôle reviewer
        if (!$reviewer->hasRole('reviewer')) {
            throw new \DomainException("L'utilisateur sélectionné n'a pas le rôle reviewer");
        }

        // Détermine l'ordre
        if (is_null($ordre)) {
            $maxOrdre = ReviewAssignment::where('article_id', $article->id)->max('ordre_review') ?? 0;
            $ordre = $maxOrdre + 1;
        }

        return DB::transaction(function () use ($article, $reviewer, $assignePar, $ordre) {
            $assignment = ReviewAssignment::create([
                'article_id' => $article->id,
                'reviewer_id' => $reviewer->id,
                'assigne_par' => $assignePar->id,
                'ordre_review' => $ordre,
                'statut' => 'en_attente',
            ]);

            // Log l'assignation
            activity()
                ->performedOn($article)
                ->causedBy($assignePar)
                ->withProperties(['reviewer_id' => $reviewer->id])
                ->log("Reviewer {$reviewer->nom} assigné à l'article");

            return $assignment;
        });
    }

    /**
     * Récupère les assignations d'un article
     */
    public function getByArticle(string $articleId): Collection
    {
        return ReviewAssignment::with(['reviewer', 'feedback'])
            ->where('article_id', $articleId)
            ->orderBy('ordre_review')
            ->get();
    }

    /**
     * Récupère les assignations en attente pour un reviewer
     */
    public function getPendingForReviewer(string $reviewerId): Collection
    {
        return ReviewAssignment::with(['article' => function ($q) {
                $q->with(['auteur', 'categories']);
            }])
            ->where('reviewer_id', $reviewerId)
            ->whereIn('statut', ['en_attente', 'accepte', 'en_cours'])
            ->orderBy('deadline')
            ->get();
    }

    /**
     * Accepte une assignation
     */
    public function accepter(ReviewAssignment $assignment, User $reviewer): bool
    {
        if ($assignment->reviewer_id !== $reviewer->id) {
            throw new \DomainException("Vous n'êtes pas autorisé à accepter cette assignation");
        }

        if (!$assignment->estEnAttente()) {
            throw new \DomainException("Cette assignation ne peut pas être acceptée dans son état actuel");
        }

        return $assignment->accepter();
    }

    /**
     * Refuse une assignation
     */
    public function refuser(ReviewAssignment $assignment, User $reviewer, ?string $raison = null): bool
    {
        if ($assignment->reviewer_id !== $reviewer->id) {
            throw new \DomainException("Vous n'êtes pas autorisé à refuser cette assignation");
        }

        if (!$assignment->estEnAttente()) {
            throw new \DomainException("Cette assignation ne peut pas être refusée dans son état actuel");
        }

        return $assignment->refuser($raison);
    }

    /**
     * Enregistre un feedback de review
     */
    public function soumettreFeedback(
        ReviewAssignment $assignment,
        User $reviewer,
        string $decision,
        ?string $commentaireGlobal = null,
        ?array $annotations = null,
        ?array $corrections = null,
        ?int $scoreQualite = null
    ): ReviewFeedback {
        if ($assignment->reviewer_id !== $reviewer->id) {
            throw new \DomainException("Vous n'êtes pas autorisé à soumettre un feedback pour cette assignation");
        }

        if ($assignment->feedbackEnvoye()) {
            throw new \DomainException("Un feedback a déjà été soumis pour cette assignation");
        }

        return DB::transaction(function () use ($assignment, $reviewer, $decision, $commentaireGlobal, $annotations, $corrections, $scoreQualite) {
            // Récupère le numéro d'itération actuel
            $iteration = ReviewFeedback::where('review_assignment_id', $assignment->id)->count() + 1;

            $feedback = ReviewFeedback::create([
                'review_assignment_id' => $assignment->id,
                'article_id' => $assignment->article_id,
                'reviewer_id' => $reviewer->id,
                'numero_iteration' => $iteration,
                'commentaire_global' => $commentaireGlobal,
                'annotations' => $annotations,
                'corrections_demandees' => $corrections,
                'decision' => $decision,
                'score_qualite' => $scoreQualite,
            ]);

            // Met à jour le statut de l'assignation
            $assignment->statut = 'retour_envoye';
            $assignment->save();

            // Log le feedback
            activity()
                ->performedOn($assignment->article)
                ->causedBy($reviewer)
                ->withProperties(['decision' => $decision])
                ->log("Feedback de review soumis: {$decision}");

            return $feedback;
        });
    }

    /**
     * Vérifie si tous les reviewers ont donné leur feedback
     */
    public function tousLesFeedbacksRecus(Article $article): bool
    {
        $assignments = ReviewAssignment::where('article_id', $article->id)
            ->whereNotIn('statut', ['refuse', 'expire'])
            ->get();

        foreach ($assignments as $assignment) {
            if (!$assignment->feedbackEnvoye()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifie si tous les reviewers ont validé l'article
     */
    public function tousLesReviewersOntValide(Article $article): bool
    {
        $assignments = ReviewAssignment::where('article_id', $article->id)
            ->whereHas('feedback', function ($q) {
                $q->where('decision', 'validation');
            })
            ->get();

        $totalActifs = ReviewAssignment::where('article_id', $article->id)
            ->whereNotIn('statut', ['refuse', 'expire'])
            ->count();

        return $assignments->count() >= $totalActifs && $totalActifs > 0;
    }

    /**
     * Récupère la moyenne des scores de qualité
     */
    public function getScoreQualiteMoyen(Article $article): ?float
    {
        $scores = ReviewFeedback::where('article_id', $article->id)
            ->whereNotNull('score_qualite')
            ->pluck('score_qualite');

        if ($scores->isEmpty()) {
            return null;
        }

        return $scores->avg();
    }

    /**
     * Supprime une assignation
     */
    public function supprimer(ReviewAssignment $assignment): bool
    {
        if ($assignment->feedbackEnvoye()) {
            throw new \DomainException("Impossible de supprimer une assignation qui a déjà reçu un feedback");
        }

        return $assignment->delete();
    }
}
