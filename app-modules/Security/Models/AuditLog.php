<?php

declare(strict_types=1);

namespace Modules\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use Modules\User\Models\User;

/**
 * Modèle AuditLog
 *
 * Enregistre toutes les actions sensibles pour la traçabilité.
 *
 * @property string $id
 * @property string|null $maison_id
 * @property string $contexte
 * @property string|null $entite_type
 * @property string|null $entite_id
 * @property string $action
 * @property string $module
 * @property string $utilisateur_id
 * @property string|null $utilisateur_nom
 * @property string|null $utilisateur_role
 * @property array|null $anciennes_valeurs
 * @property array|null $nouvelles_valeurs
 * @property array|null $champs_modifies
 * @property array|null $metadonnees
 * @property string|null $description
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $continent
 * @property string|null $pays
 * @property string|null $ville
 * @property string $niveau
 */
class AuditLog extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Nom de la table
     */
    protected $table = 'audit_logs';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'maison_id',
        'contexte',
        'entite_type',
        'entite_id',
        'action',
        'module',
        'utilisateur_id',
        'utilisateur_nom',
        'utilisateur_role',
        'anciennes_valeurs',
        'nouvelles_valeurs',
        'champs_modifies',
        'metadonnees',
        'description',
        'ip_address',
        'user_agent',
        'continent',
        'pays',
        'ville',
        'niveau',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'anciennes_valeurs' => 'array',
        'nouvelles_valeurs' => 'array',
        'champs_modifies' => 'array',
        'metadonnees' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec la maison d'édition
     */
    public function maison(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'maison_id');
    }

    /**
     * Relation avec l'utilisateur
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Scope pour un module spécifique
     */
    public function scopePourModule(\Illuminate\Database\Eloquent\Builder $query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope pour une action spécifique
     */
    public function scopePourAction(\Illuminate\Database\Eloquent\Builder $query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope pour une entité spécifique
     */
    public function scopePourEntite(\Illuminate\Database\Eloquent\Builder $query, string $type, string $id)
    {
        return $query->where('entite_type', $type)->where('entite_id', $id);
    }

    /**
     * Scope pour un niveau de criticité
     */
    public function scopeNiveau(\Illuminate\Database\Eloquent\Builder $query, string $niveau)
    {
        return $query->where('niveau', $niveau);
    }

    /**
     * Scope pour une maison spécifique
     */
    public function scopePourMaison(\Illuminate\Database\Eloquent\Builder $query, ?string $maisonId)
    {
        if ($maisonId) {
            return $query->where('maison_id', $maisonId);
        }
        return $query->whereNull('maison_id');
    }

    /**
     * Scope pour les logs critiques
     */
    public function scopeCritiques(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('niveau', 'critical');
    }

    /**
     * Vérifie si le log est critique
     */
    public function estCritique(): bool
    {
        return $this->niveau === 'critical';
    }

    /**
     * Obtient le label du niveau
     */
    public function getNiveauLabelAttribute(): string
    {
        return match($this->niveau) {
            'info' => 'Information',
            'warning' => 'Attention',
            'critical' => 'Critique',
            default => $this->niveau,
        };
    }

    /**
     * Obtient la couleur du niveau
     */
    public function getNiveauCouleurAttribute(): string
    {
        return match($this->niveau) {
            'info' => 'blue',
            'warning' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }
}
