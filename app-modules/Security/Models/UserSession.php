<?php

declare(strict_types=1);

namespace Modules\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

/**
 * Modèle UserSession
 *
 * Suit les sessions actives des utilisateurs.
 *
 * @property string $id
 * @property string $utilisateur_id
 * @property string $session_id
 * @property string|null $token
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $device_type
 * @property string|null $continent
 * @property string|null $pays
 * @property string|null $ville
 * @property \Illuminate\Support\Carbon $last_activity
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $logged_out_at
 * @property bool $is_active
 * @property array|null $metadonnees
 */
class UserSession extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Nom de la table
     */
    protected $table = 'user_sessions';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'utilisateur_id',
        'session_id',
        'token',
        'ip_address',
        'user_agent',
        'device_type',
        'continent',
        'pays',
        'ville',
        'last_activity',
        'expires_at',
        'logged_out_at',
        'is_active',
        'metadonnees',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'last_activity' => 'datetime',
        'expires_at' => 'datetime',
        'logged_out_at' => 'datetime',
        'is_active' => 'boolean',
        'metadonnees' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }

    /**
     * Scope pour les sessions actives
     */
    public function scopeActives(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('is_active', true)
                     ->whereNull('logged_out_at')
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Scope pour un utilisateur spécifique
     */
    public function scopePourUtilisateur(\Illuminate\Database\Eloquent\Builder $query, string $utilisateurId)
    {
        return $query->where('utilisateur_id', $utilisateurId);
    }

    /**
     * Vérifie si la session est expirée
     */
    public function estExpiree(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Vérifie si la session est active
     */
    public function estActive(): bool
    {
        return $this->is_active && !$this->logged_out_at && !$this->estExpiree();
    }

    /**
     * Termine la session
     */
    public function terminer(): bool
    {
        $this->is_active = false;
        $this->logged_out_at = \Illuminate\Support\Carbon::now();

        return $this->save();
    }

    /**
     * Rafraîchit l'activité de la session
     */
    public function rafraichir(): bool
    {
        $this->last_activity = \Illuminate\Support\Carbon::now();

        return $this->save();
    }
}
