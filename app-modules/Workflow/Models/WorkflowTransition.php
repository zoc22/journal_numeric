<?php

declare(strict_types=1);

namespace Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\User\Models\User;
use Modules\Workflow\Enums\ArticleStatus;

/**
 * Modèle WorkflowTransition
 *
 * Enregistre chaque transition d'état dans le workflow.
 * Permet la traçabilité complète et l'audit des actions.
 *
 * @property string $id
 * @property string $workflowable_type
 * @property string $workflowable_id
 * @property string $statut_origine
 * @property string $statut_cible
 * @property string $utilisateur_id
 * @property int $role_niveau
 * @property string|null $role_utilise
 * @property string|null $commentaires
 * @property array|null $metadonnees
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class WorkflowTransition extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Nom de la table
     */
    protected $table = 'workflow_transitions';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'workflowable_type',
        'workflowable_id',
        'statut_origine',
        'statut_cible',
        'utilisateur_id',
        'role_niveau',
        'role_utilise',
        'commentaires',
        'metadonnees',
        'ip_address',
        'user_agent',
        'continent',
        'pays',
        'ville',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'metadonnees' => 'array',
        'role_niveau' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation polymorphe avec l'entité concernée (Article, etc.)
     */
    public function workflowable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relation avec l'utilisateur qui a effectué la transition
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Obtient le statut origine sous forme d'enum
     */
    public function getStatutOrigineEnum(): ArticleStatus
    {
        return ArticleStatus::fromString($this->statut_origine);
    }

    /**
     * Obtient le statut cible sous forme d'enum
     */
    public function getStatutCibleEnum(): ArticleStatus
    {
        return ArticleStatus::fromString($this->statut_cible);
    }

    /**
     * Vérifie si la transition est une validation
     */
    public function estUneValidation(): bool
    {
        return $this->getStatutCibleEnum()->estStatutValidation();
    }

    /**
     * Obtient le label du statut origine
     */
    public function getStatutOrigineLabelAttribute(): string
    {
        return $this->getStatutOrigineEnum()->label();
    }

    /**
     * Obtient le label du statut cible
     */
    public function getStatutCibleLabelAttribute(): string
    {
        return $this->getStatutCibleEnum()->label();
    }

    /**
     * Scope pour les transitions d'un article spécifique
     */
    public function scopePourArticle($query, string $articleId)
    {
        return $query->where('workflowable_type', 'Modules\Article\Models\Article')
                     ->where('workflowable_id', $articleId);
    }

    /**
     * Scope pour les transitions d'un utilisateur
     */
    public function scopeParUtilisateur($query, string $utilisateurId)
    {
        return $query->where('utilisateur_id', $utilisateurId);
    }
}
