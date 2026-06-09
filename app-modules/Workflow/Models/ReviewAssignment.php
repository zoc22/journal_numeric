<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Article\Models\Article;
use Modules\User\Models\User;
use Modules\Workflow\Enums\ReviewStatus;

/**
 * Modèle ReviewAssignment
 *
 * Représente l'assignation d'un reviewer à un article.
 * Gère le cycle de vie complet de la relecture.
 *
 * @property string $id
 * @property string $article_id
 * @property string $reviewer_id
 * @property string $assigne_par
 * @property int $ordre_review
 * @property string $statut
 * @property Carbon $assigne_le
 * @property Carbon|null $accepte_le
 * @property Carbon|null $deadline
 * @property Carbon|null $termine_le
 * @property string|null $note_interne
 * @property array|null $metadonnees
 */
class ReviewAssignment extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Nom de la table
     */
    protected $table = 'review_assignments';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'article_id',
        'reviewer_id',
        'assigne_par',
        'ordre_review',
        'statut',
        'assigne_le',
        'accepte_le',
        'deadline',
        'termine_le',
        'note_interne',
        'metadonnees',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'metadonnees' => 'array',
        'ordre_review' => 'integer',
        'assigne_le' => 'datetime',
        'accepte_le' => 'datetime',
        'deadline' => 'datetime',
        'termine_le' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Attributs par défaut
     */
    protected $attributes = [
        'statut' => 'en_attente',
    ];

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
     * Relation avec l'assignant
     */
    public function assignePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigne_par');
    }

    /**
     * Relation avec les feedbacks
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(ReviewFeedback::class, 'review_assignment_id')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Obtient le dernier feedback
     */
    public function dernierFeedback(): ?ReviewFeedback
    {
        return $this->feedback()->first();
    }

    /**
     * Obtient le statut sous forme d'enum
     */
    public function getStatutEnum(): ReviewStatus
    {
        return ReviewStatus::from($this->statut);
    }

    /**
     * Obtient le label du statut
     */
    public function getStatutLabelAttribute(): string
    {
        return $this->getStatutEnum()->label();
    }

    /**
     * Vérifie si l'assignation est en attente
     */
    public function estEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    /**
     * Vérifie si l'assignation est acceptée
     */
    public function estAcceptee(): bool
    {
        return $this->statut === 'accepte';
    }

    /**
     * Vérifie si le feedback a été envoyé
     */
    public function feedbackEnvoye(): bool
    {
        return $this->statut === 'retour_envoye' || $this->statut === 'termine';
    }

    /**
     * Vérifie si la deadline est dépassée
     */
    public function estExpiree(): bool
    {
        if (!$this->deadline) {
            return false;
        }

        return $this->deadline->isPast() && !$this->feedbackEnvoye();
    }

    /**
     * Accepte l'assignation
     */
    public function accepter(): bool
    {
        $this->statut = 'accepte';
        $this->accepte_le = now();

        return $this->save();
    }

    /**
     * Refuse l'assignation
     */
    public function refuser(?string $raison = null): bool
    {
        $this->statut = 'refuse';
        $this->termine_le = now();

        if ($raison) {
            $metadonnees = $this->metadonnees ?? [];
            $metadonnees['refus_raison'] = $raison;
            $this->metadonnees = $metadonnees;
        }

        return $this->save();
    }

    /**
     * Marque comme en cours
     */
    public function demarrer(): bool
    {
        $this->statut = 'en_cours';

        return $this->save();
    }

    /**
     * Marque comme terminé
     */
    public function terminer(): bool
    {
        $this->statut = 'termine';
        $this->termine_le = now();

        return $this->save();
    }

    /**
     * Marque comme expiré
     */
    public function marquerCommeExpire(): bool
    {
        $this->statut = 'expire';
        $this->termine_le = now();

        return $this->save();
    }

    /**
     * Scope pour les assignations actives
     */
    public function scopeActives($query)
    {
        return $query->whereIn('statut', ['en_attente', 'accepte', 'en_cours']);
    }

    /**
     * Scope pour les assignations par reviewer
     */
    public function scopeParReviewer($query, string $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }

    /**
     * Scope pour les assignations par article
     */
    public function scopeParArticle($query, string $articleId)
    {
        return $query->where('article_id', $articleId);
    }

    /**
     * Boot du modèle
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($assignment) {
            if (empty($assignment->deadline)) {
                $deadlineDays = config('workflow.review.review_deadline_days', 14);
                $assignment->deadline = now()->addDays($deadlineDays);
            }
        });
    }
}
