<?php

declare(strict_types=1);

namespace Modules\Security\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

/**
 * Modèle LoginHistory
 *
 * Enregistre chaque tentative de connexion pour l'analyse de sécurité.
 *
 * @property string $id
 * @property string|null $utilisateur_id
 * @property string $email
 * @property bool $succes
 * @property string|null $message_erreur
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $device_type
 * @property string|null $browser
 * @property string|null $os
 * @property string|null $country_code
 * @property string|null $continent
 * @property string|null $pays
 * @property string|null $ville
 * @property string|null $city
 * @property array|null $metadonnees
 */
class LoginHistory extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * Nom de la table
     */
    protected $table = 'login_histories';

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'utilisateur_id',
        'email',
        'succes',
        'message_erreur',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'country_code',
        'continent',
        'pays',
        'ville',
        'city',
        'metadonnees',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'succes' => 'boolean',
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
     * Scope pour les connexions réussies
     */
    public function scopeReussies(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('succes', true);
    }

    /**
     * Scope pour les connexions échouées
     */
    public function scopeEchouees(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('succes', false);
    }

    /**
     * Scope pour un utilisateur spécifique
     */
    public function scopePourUtilisateur(\Illuminate\Database\Eloquent\Builder $query, string $utilisateurId)
    {
        return $query->where('utilisateur_id', $utilisateurId);
    }

    /**
     * Scope pour une IP spécifique
     */
    public function scopePourIP(\Illuminate\Database\Eloquent\Builder $query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope pour une période
     */
    public function scopeEntreDates(\Illuminate\Database\Eloquent\Builder $query, mixed $debut, mixed $fin)
    {
        return $query->whereBetween('created_at', [$debut, $fin]);
    }
}
