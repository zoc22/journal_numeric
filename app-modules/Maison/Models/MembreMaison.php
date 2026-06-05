<?php

declare(strict_types=1);

namespace Modules\Maison\Models;

use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasAuditLog;
use Modules\Core\Traits\HasUuid;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Classe représentant l'appartenance d'un utilisateur à une maison d'édition.
 *
 * Cette table pivot gère :
 * - Le lien entre un utilisateur et une maison
 * - Le rôle de l'utilisateur au sein de la maison
 * - Le statut actif/inactif du membre
 * - Les dates d'adhésion et de départ
 *
 * @property string $id UUID du membre
 * @property string $maison_id ID de la maison
 * @property string $utilisateur_id ID de l'utilisateur
 * @property int|string|null $role_id ID du rôle Spatie
 * @property bool $est_actif Statut actif/inactif du membre
 * @property \Carbon\Carbon $a_rejoint_le Date d'adhésion
 * @property \Carbon\Carbon|null $a_quitte_le Date de départ
 * @property \Carbon\Carbon $created_at Date de création
 * @property \Carbon\Carbon $updated_at Date de modification
 *
 * @property-read Maison $maison La maison associée
 * @property-read User $utilisateur L'utilisateur associé
 * @property-read Role|null $role Le rôle Spatie associé
 */
class MembreMaison extends Model
{
    use HasFactory;
    use HasTimestamps;
    use HasUuid;
    use HasAuditLog;

    /**
     * Le nom de la table associée à ce modèle.
     *
     * @var string
     */
    protected $table = 'membre_maison';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'maison_id',
        'utilisateur_id',
        'role_id',
        'est_actif',
        'a_rejoint_le',
        'a_quitte_le',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'est_actif' => 'boolean',
        'a_rejoint_le' => 'datetime',
        'a_quitte_le' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Les attributs avec leurs valeurs par défaut.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'est_actif' => true,
    ];

    // =========================================================================
    // RELATIONS
    // =========================================================================

    /**
     * Relation : La maison associée à ce membre.
     *
     * @return BelongsTo
     */
    public function maison(): BelongsTo
    {
        return $this->belongsTo(Maison::class, 'maison_id');
    }

    /**
     * Relation : L'utilisateur associé à ce membre.
     *
     * @return BelongsTo
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Relation : Le rôle Spatie associé à ce membre.
     *
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // =========================================================================
    // SCOPES
    // =========================================================================

    /**
     * Scope : Filtrer les membres actifs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActifs($query)
    {
        return $query->where('est_actif', true);
    }

    /**
     * Scope : Filtrer les membres inactifs.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactifs($query)
    {
        return $query->where('est_actif', false);
    }

    /**
     * Scope : Filtrer par maison.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $maisonId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParMaison($query, string $maisonId)
    {
        return $query->where('maison_id', $maisonId);
    }

    /**
     * Scope : Filtrer par utilisateur.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $utilisateurId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParUtilisateur($query, string $utilisateurId)
    {
        return $query->where('utilisateur_id', $utilisateurId);
    }

    /**
     * Scope : Filtrer par rôle.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $roleId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParRole($query, string $roleId)
    {
        return $query->where('role_id', $roleId);
    }

    // =========================================================================
    // MÉTHODES MÉTIER
    // =========================================================================

    /**
     * Active le membre (réintègre la maison).
     *
     * @return bool
     */
    public function activer(): bool
    {
        if ($this->est_actif) {
            return true;
        }

        $this->est_actif = true;
        $this->a_quitte_le = null;

        return $this->save();
    }

    /**
     * Désactive le membre (quitte la maison).
     *
     * @return bool
     */
    public function desactiver(): bool
    {
        if (!$this->est_actif) {
            return true;
        }

        $this->est_actif = false;
        $this->a_quitte_le = now();

        return $this->save();
    }

    /**
     * Vérifie si le membre est actif.
     *
     * @return bool
     */
    public function estActif(): bool
    {
        return $this->est_actif;
    }

    /**
     * Change le rôle du membre dans la maison.
     *
     * @param int|string $roleId
     * @return bool
     */
    public function changerRole(int|string $roleId): bool
    {
        $this->role_id = $roleId;
        return $this->save();
    }
}
