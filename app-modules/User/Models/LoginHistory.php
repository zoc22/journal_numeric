<?php
// app-modules/User/Models/LoginHistory.php

namespace Modules\User\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Traits\HasUuid;

/**
 * Modèle LoginHistory – Enregistre l'historique des connexions des utilisateurs.
 *
 * Ce modèle permet de tracer :
 * - Quand un utilisateur s'est connecté
 * - L'adresse IP depuis laquelle il s'est connecté
 * - L'agent utilisateur (navigateur, système d'exploitation)
 * - Si la connexion a réussi ou échoué
 * - La raison d'échec si applicable
 *
 * Utile pour les audits de sécurité et pour détecter les accès suspects.
 */
class LoginHistory extends Model
{
    use HasUuid;

    /**
     * La table associée au modèle.
     *
     * @var string
     */
    protected $table = 'login_histories';

    /**
     * Le type de la clé primaire (string pour UUID).
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indique si la clé primaire est auto-incrémentée.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'success',
        'failure_reason',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'success' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation vers l'utilisateur qui s'est connecté.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope pour récupérer uniquement les connexions réussies.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope pour récupérer uniquement les tentatives échouées.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope pour récupérer les connexions d'un utilisateur spécifique.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope pour récupérer les connexions depuis une IP spécifique.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ipAddress
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }
}
