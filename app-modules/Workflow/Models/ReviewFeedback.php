<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Article\Models\Article;
use Modules\User\Models\User;
use Illuminate\Support\Carbon;

/**
 * Modèle ReviewFeedback
 *
 * Enregistre le feedback d'un reviewer sur un article.
 * Contient la décision, les commentaires et les annotations.
 *
 * @property string $id
 * @property string $review_assignment_id
 * @property string $article_id
 * @property string $reviewer_id
 * @property int $numero_iteration
 * @property string|null $commentaire_global
 * @property array|null $annotations
 * @property array|null $corrections_demandees
 * @property string $decision
 * @property int|null $score_qualite
 * @property Carbon $retour_le
 */
class ReviewFeedback extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Nom de la table
     */
    protected $table = 'review_feedback';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'review_assignment_id',
        'article_id',
        'reviewer_id',
        'numero_iteration',
        'commentaire_global',
        'annotations',
        'corrections_demandees',
        'decision',
        'score_qualite',
        'retour_le',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'annotations' => 'array',
        'corrections_demandees' => 'array',
        'numero_iteration' => 'integer',
        'score_qualite' => 'integer',
        'retour_le' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Attributs par défaut
     */
    protected $attributes = [
        'numero_iteration' => 1,
    ];

    /**
     * Relation avec l'assignation
     */
    public function reviewAssignment(): BelongsTo
    {
        return $this->belongsTo(ReviewAssignment::class, 'review_assignment_id');
    }

    /**
     * Relation avec l'article
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * Relation avec le reviewer
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Vérifie si la décision est une validation
     */
    public function estValidation(): bool
    {
        return $this->decision === 'validation';
    }

    /**
     * Vérifie si la décision demande des corrections
     */
    public function demandeCorrection(): bool
    {
        return $this->decision === 'correction';
    }

    /**
     * Vérifie si la décision est un rejet
     */
    public function estRejet(): bool
    {
        return $this->decision === 'rejet';
    }

    /**
     * Obtient la liste structurée des corrections
     */
    public function getCorrectionsListeAttribute(): array
    {
        if (!$this->corrections_demandees) {
            return [];
        }

        return $this->corrections_demandees;
    }

    /**
     * Obtient les annotations formatées
     */
    public function getAnnotationsFormateesAttribute(): array
    {
        if (!$this->annotations) {
            return [];
        }

        return $this->annotations;
    }

    /**
     * Scope pour les feedbacks de validation
     */
    public function scopeValidations($query)
    {
        return $query->where('decision', 'validation');
    }

    /**
     * Scope pour les feedbacks de correction
     */
    public function scopeCorrections($query)
    {
        return $query->where('decision', 'correction');
    }
}
