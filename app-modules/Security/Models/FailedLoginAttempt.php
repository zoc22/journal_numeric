<?php

declare(strict_types=1);

namespace Modules\Security\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle FailedLoginAttempt
 *
 * Enregistre les tentatives de connexion échouées pour le rate limiting.
 *
 * @property int $id
 * @property string|null $email
 * @property string $ip_address
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $attempted_at
 */
class FailedLoginAttempt extends Model
{
    use HasFactory;

    /**
     * Nom de la table
     */
    protected $table = 'failed_login_attempts';

    /**
     * Désactive les timestamps
     */
    public $timestamps = false;

    /**
     * Attributs assignables
     */
    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'attempted_at',
    ];

    /**
     * Attributs à caster
     */
    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    /**
     * Scope pour les tentatives récentes d'une IP
     */
    public function scopePourIP(\Illuminate\Database\Eloquent\Builder $query, string $ip, int $minutes = 15)
    {
        return $query->where('ip_address', $ip)
                     ->where('attempted_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Scope pour les tentatives récentes d'un email
     */
    public function scopePourEmail(\Illuminate\Database\Eloquent\Builder $query, string $email, int $minutes = 15)
    {
        return $query->where('email', $email)
                     ->where('attempted_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Nettoie les anciennes tentatives
     */
    public static function nettoyerAnciennes(int $jours = 30): int
    {
        return self::where('attempted_at', '<', now()->subDays($jours))->delete();
    }
}
