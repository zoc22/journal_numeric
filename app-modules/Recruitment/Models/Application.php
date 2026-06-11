<?php

declare(strict_types=1);

namespace Modules\Recruitment\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Recruitment\Enums\ApplicationStatus;
use Modules\User\Models\User;
use Modules\Core\Traits\HasUuid;

/**
 * Modèle Application
 *
 * Représente une candidature soumise par un utilisateur
 * en réponse à un appel à candidatures.
 *
 * @property string $id
 * @property string $appel_id
 * @property string $candidat_id
 * @property string $lettre_motivation
 * @property array|null $experiences
 * @property array|null $formations
 * @property array|null $portfolio
 * @property array|null $competences
 * @property array|null $documents
 * @property string|null $cv_url
 * @property string $statut
 * @property string|null $examinee_par
 * @property string|null $commentaires_examen
 * @property int|null $score
 * @property Carbon $soumise_le
 * @property Carbon|null $examinee_le
 */
class Application extends Model
{
    use HasFactory;
    use HasUuid;

    /**
     * Nom de la table
     */
    protected $table = 'applications';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'appel_id',
        'candidat_id',
        'lettre_motivation',
        'experiences',
        'formations',
        'portfolio',
        'competences',
        'documents',
        'cv_url',
        'continent',
        'pays',
        'ville',
        'statut',
        'examinee_par',
        'commentaires_examen',
        'score',
        'soumise_le',
        'examinee_le',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'experiences' => 'array',
        'formations' => 'array',
        'portfolio' => 'array',
        'competences' => 'array',
        'documents' => 'array',
        'score' => 'integer',
        'soumise_le' => 'datetime',
        'examinee_le' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'appel à candidatures
     */
    public function appel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CallForApplication::class, 'appel_id');
    }

    /**
     * Relation avec le candidat
     */
    public function candidat(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'candidat_id');
    }

    /**
     * Relation avec l'examinateur
     */
    public function examinateur(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'examinee_par');
    }

    /**
     * Obtient le statut sous forme d'enum
     */
    public function getStatutEnum(): ApplicationStatus
    {
        return ApplicationStatus::from($this->statut);
    }

    /**
     * Obtient le label du statut
     */
    public function getStatutLabelAttribute(): string
    {
        return $this->getStatutEnum()->label();
    }

    /**
     * Vérifie si la candidature peut être modifiée
     */
    public function estModifiable(): bool
    {
        return $this->statut === ApplicationStatus::EN_ATTENTE->value;
    }

    /**
     * Passe la candidature en revue
     */
    public function passerEnRevue(User $examinateur): bool
    {
        if ($this->statut !== ApplicationStatus::EN_ATTENTE->value) {
            return false;
        }

        return $this->update([
            'statut' => ApplicationStatus::EN_REVUE->value,
            'examinee_par' => $examinateur->id,
            'examinee_le' => now(),
        ]);
    }

    /**
     * Accepte la candidature
     */
    public function accepter(User $examinateur, ?string $commentaires = null, ?int $score = null): bool
    {
        if (!in_array($this->statut, [ApplicationStatus::EN_ATTENTE->value, ApplicationStatus::EN_REVUE->value])) {
            return false;
        }

        $data = [
            'statut' => ApplicationStatus::ACCEPTEE->value,
            'examinee_par' => $examinateur->id,
            'examinee_le' => now(),
            'commentaires_examen' => $commentaires,
        ];

        if ($score !== null) {
            $data['score'] = $score;
        }

        return $this->update($data);
    }

    /**
     * Rejette la candidature
     */
    public function rejeter(User $examinateur, ?string $commentaires = null): bool
    {
        if (!in_array($this->statut, [ApplicationStatus::EN_ATTENTE->value, ApplicationStatus::EN_REVUE->value])) {
            return false;
        }

        return $this->update([
            'statut' => ApplicationStatus::REJETEE->value,
            'examinee_par' => $examinateur->id,
            'examinee_le' => now(),
            'commentaires_examen' => $commentaires,
        ]);
    }

    /**
     * Scope pour les candidatures en attente
     */
    public function scopeEnAttente(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('statut', ApplicationStatus::EN_ATTENTE->value);
    }

    /**
     * Scope pour les candidatures d'un appel
     */
    public function scopePourAppel(\Illuminate\Database\Eloquent\Builder $query, string $appelId)
    {
        return $query->where('appel_id', $appelId);
    }

    /**
     * Scope pour les candidatures d'un candidat
     */
    public function scopePourCandidat(\Illuminate\Database\Eloquent\Builder $query, string $candidatId)
    {
        return $query->where('candidat_id', $candidatId);
    }

    /**
     * Boot du modèle
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($application) {
            if (empty($application->soumise_le)) {
                $application->soumise_le = now();
            }
        });
    }
}
