<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Article\Models\Article;
use Modules\User\Models\User;

/**
 * Modèle ReviewIteration
 *
 * Suit chaque cycle de correction d'un article.
 * Un article peut passer par plusieurs itérations avant validation finale.
 *
 * @property string $id
 * @property string $article_id
 * @property string $journaliste_id
 * @property int $numero_iteration
 * @property string $statut
 * @property string|null $corrections_apportees
 * @property string|null $notes_journaliste
 * @property Carbon|null $correction_demandee_le
 * @property Carbon|null $correction_soumise_le
 * @property Carbon|null $terminee_le
 */
class ReviewIteration extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Nom de la table
     */
    protected $table = 'review_iterations';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'article_id',
        'journaliste_id',
        'numero_iteration',
        'statut',
        'corrections_apportees',
        'notes_journaliste',
        'correction_demandee_le',
        'correction_soumise_le',
        'terminee_le',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'numero_iteration' => 'integer',
        'correction_demandee_le' => 'datetime',
        'correction_soumise_le' => 'datetime',
        'terminee_le' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'article
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * Relation avec le journaliste
     */
    public function journaliste(): BelongsTo
    {
        return $this->belongsTo(User::class, 'journaliste_id');
    }

    /**
     * Vérifie si des corrections sont demandées
     */
    public function estEnAttenteDeCorrection(): bool
    {
        return $this->statut === 'correction_demandee';
    }

    /**
     * Vérifie si les corrections sont soumises
     */
    public function estSoumise(): bool
    {
        return $this->statut === 'correction_soumise';
    }

    /**
     * Vérifie si l'itération est terminée
     */
    public function estTerminee(): bool
    {
        return $this->statut === 'terminee';
    }

    /**
     * Demande des corrections (démarre une itération)
     */
    public function demanderCorrections(): bool
    {
        $this->statut = 'correction_demandee';
        $this->correction_demandee_le = now();

        return $this->save();
    }

    /**
     * Soumet les corrections (journaliste)
     */
    public function soumettreCorrections(string $corrections, ?string $notes = null): bool
    {
        $this->corrections_apportees = $corrections;
        $this->notes_journaliste = $notes;
        $this->statut = 'correction_soumise';
        $this->correction_soumise_le = now();

        return $this->save();
    }

    /**
     * Termine l'itération (après validation)
     */
    public function terminer(): bool
    {
        $this->statut = 'terminee';
        $this->terminee_le = now();

        return $this->save();
    }

    /**
     * Scope pour les itérations actives
     */
    public function scopeActives($query)
    {
        return $query->whereIn('statut', ['correction_demandee', 'correction_soumise']);
    }

    /**
     * Scope pour une itération spécifique d'un article
     */
    public function scopePourArticle($query, string $articleId)
    {
        return $query->where('article_id', $articleId);
    }

    /**
     * Obtient la prochaine itération pour un article
     */
    public static function prochaineIteration(string $articleId): int
    {
        $max = self::where('article_id', $articleId)->max('numero_iteration');
        return ($max ?? 0) + 1;
    }
}
