<?php
// app-modules/User/Models/User.php

namespace Modules\User\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Modules\Core\Traits\HasUuid;
use Modules\Core\Traits\HasPermissions;
use Modules\Core\Traits\HasAuditLog;

/**
 * Modèle User – Représente un utilisateur dans une maison d'édition.
 *
 * Ce modèle utilise les traits du module Core pour :
 * - HasUuid : génère automatiquement un UUID comme clé primaire
 * - HasRoles : gère les rôles/permissions via spatie/laravel-permission
 * - HasPermissions : méthodes personnalisées de vérification
 * - HasAuditLog : enregistre automatiquement les actions sur l'utilisateur
 * - SoftDeletes : permet la suppression douce (restauration possible)
 *
 * @property string $id Identifiant unique (UUID)
 * @property string $nom Nom de l'utilisateur
 * @property string|null $prenom Prénom de l'utilisateur
 * @property string $email Adresse email
 * @property string $password Mot de passe hashé
 * @property string|null $avatar URL de l'avatar
 * @property string|null $telephone Numéro de téléphone
 * @property string|null $continent Continent de l'utilisateur
 * @property string|null $pays Pays de l'utilisateur
 * @property string|null $ville Ville de l'utilisateur
 * @property string|null $poste Poste dans l'organisation
 * @property bool $is_active Statut d'activation
 * @property \Illuminate\Support\Carbon|null $email_verified_at Date de vérification de l'email
 * @property \Illuminate\Support\Carbon|null $last_login_at Date de dernière connexion
 * @property string|null $last_login_ip IP de dernière connexion
 * @property \Illuminate\Support\Carbon|null $created_at Date de création
 * @property \Illuminate\Support\Carbon|null $updated_at Date de mise à jour
 * @property \Illuminate\Support\Carbon|null $deleted_at Date de suppression douce
 * @property-read string $full_name Nom complet de l'utilisateur
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles Rôles de l'utilisateur
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Modules\Security\Models\LoginHistory> $loginHistories Historique des connexions
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes, HasRoles, HasPermissions, HasFactory {
        HasRoles::hasRole insteadof HasPermissions;
        HasRoles::hasAnyRole insteadof HasPermissions;
        HasRoles::assignRole insteadof HasPermissions;
    }
    use HasUuid, HasAuditLog;

    /**
     * La table associée au modèle.
     */
    protected $table = 'users';

    /**
     * Le type de la clé primaire (string pour UUID).
     */
    protected $keyType = 'string';

    /**
     * Guard name pour spatie/laravel-permission.
     */
    protected $guard_name = 'sanctum';

    /**
     * Indique si la clé primaire est auto-incrémentée.
     */
    public $incrementing = false;

    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'avatar',
        'telephone',
        'continent',
        'pays',
        'ville',
        'poste',
        'is_active',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * Les attributs qui doivent être cachés pour la sérialisation.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Les attributs qui doivent être castés.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot du modèle – initialise l'audit log.
     */
    protected static function boot()
    {
        parent::boot();
        static::bootHasAuditLog(); // Active la journalisation automatique
    }

    /**
     * Relation avec l'historique des connexions.
     *
     * Permet de récupérer toutes les connexions enregistrées pour cet utilisateur.
     * Utile pour les audits de sécurité et le suivi d'accès.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function loginHistories()
    {
        return $this->hasMany(\Modules\Security\Models\LoginHistory::class, 'utilisateur_id');
    }

    /**
     * Relation avec les maisons d'édition (via pivot membre_maison).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function membres()
    {
        return $this->hasMany(\Modules\Maison\Models\MembreMaison::class, 'utilisateur_id');
    }

    /**
     * Vérifie si l'utilisateur a accès au back-office.
     *
     * Un utilisateur peut accéder au back-office s'il a l'un des rôles suivants :
     * - super_admin : accès complet
     * - admin_plateforme : gestion de la plateforme
     * - editeur_chef : gestion éditoriale complète
     * - directeur_collection : gestion de collection
     * - editeur_associe : édition associée
     * - reviewer : review et validation
     * - journaliste : création de contenu
     *
     * Le rôle "lecteur" n'a pas accès au back-office (accès public en lecture seule).
     *
     * @return bool True si l'utilisateur peut accéder au back-office
     */
    public function canAccessBackoffice(): bool
    {
        // Liste des rôles qui ont accès au back-office
        $backofficeRoles = [
            'super_admin',
            'admin_plateforme',
            'editeur_chef',
            'directeur_collection',
            'editeur_associe',
            'reviewer',
            'journaliste',
        ];

        // Utilise la méthode hasAnyRole() de Spatie\Permission
        return $this->hasAnyRole($backofficeRoles);
    }

    /**
     * Retourne le nom complet de l'utilisateur.
     *
     * Attribut calculé (accessor) qui combine prénom et nom.
     * Exemple : "Jean Dupont"
     *
     * @return string Le nom complet
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    /**
     * Scope pour récupérer uniquement les utilisateurs actifs.
     *
     * Exemple d'utilisation :
     *   User::active()->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
