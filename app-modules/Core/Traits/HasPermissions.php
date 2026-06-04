<?php
// app-modules/Core/Traits/HasPermissions.php

namespace Modules\Core\Traits;

use Illuminate\Support\Facades\Gate;

/**
 * Ce trait ajoute des méthodes de vérification des permissions à un modèle (typiquement User).
 *
 * IMPORTANT : Ce trait est conçu pour travailler avec Spatie\Permission\Traits\HasRoles.
 * Il ne fournit que des méthodes utilitaires supplémentaires et n'entre pas en conflit
 * avec les méthodes de Spatie (hasRole, assignRole, removeRole, syncRoles, etc.).
 *
 * Les méthodes spécifiques à ce trait sont préfixées ou utilisent des noms différents
 * pour éviter les collisions avec les méthodes de Spatie (hasPermission, hasAnyPermission, etc.).
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 * @property string|null $role
 */
trait HasPermissions
{
    /**
     * Vérifie si l'utilisateur possède un rôle donné.
     *
     * Cette méthode fournit une vérification de base basée sur un attribut 'role'
     * ou une propriété du modèle. Elle sert de fallback si Spatie n'est pas utilisé.
     *
     * @param string $role Le nom du rôle à vérifier
     * @return bool True si l'utilisateur a ce rôle
     */
    public function hasRole(string $role): bool
    {
        // Si Spatie est utilisé via HasRoles, cette méthode sera généralement
        // remplacée par celle de Spatie dans le modèle final.

        // Vérification via attribut Eloquent (recommandé)
        if (method_exists($this, 'getAttribute')) {
            return $this->getAttribute('role') === $role;
        }

        // Vérification via propriété directe (fallback pour modèles simples)
        if (isset($this->role) && $this->role === $role) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur possède au moins un des rôles donnés.
     *
     * @param array|string $roles Liste des rôles
     * @return bool
     */
    public function hasAnyRole($roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Assigne un rôle à l'utilisateur (implémentation simple).
     *
     * @param string $role Le nom du rôle à assigner
     * @return $this
     */
    public function assignRole(string $role)
    {
        if (method_exists($this, 'setAttribute')) {
            $this->setAttribute('role', $role);
        } else {
            $this->role = $role;
        }

        if (method_exists($this, 'save')) {
            $this->save();
        }

        return $this;
    }

    /**
     * Vérifie si l'utilisateur possède une permission donnée via Gate.
     *
     * Cette méthode utilise le Gate de Laravel pour vérifier la permission.
     * Elle est différente de hasPermission() de Spatie qui vérifie les permissions directes.
     *
     * @param string $permission Le nom de la permission à vérifier
     * @return bool True si l'utilisateur a la permission via Gate
     */
    public function hasPermissionViaGate(string $permission): bool
    {
        // Utilise Gate pour vérifier la permission via Spatie ou le système natif
        if (Gate::forUser($this)->allows($permission)) {
            return true;
        }

        // Fallback sur la méthode can() si elle existe (Eloquent standard + Spatie)
        if (method_exists($this, 'can')) {
            return $this->can($permission);
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur possède au moins une des permissions données via Gate.
     *
     * Utile pour vérifier plusieurs permissions en un seul appel.
     *
     * @param array<string> $permissions Liste des permissions à vérifier
     * @return bool True si l'utilisateur a au moins une permission de la liste
     */
    public function hasAnyPermissionViaGate(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermissionViaGate($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Vérifie si l'utilisateur possède toutes les permissions données via Gate.
     *
     * @param array<string> $permissions Liste des permissions à vérifier
     * @return bool True si l'utilisateur a toutes les permissions de la liste
     */
    public function hasAllPermissionsViaGate(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermissionViaGate($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Récupère toutes les permissions de l'utilisateur via la relation permissions.
     *
     * Cette méthode accède directement à la relation permissions du modèle.
     * Différent de getAllPermissions() de Spatie qui inclut les permissions héritées.
     *
     * @return \Illuminate\Database\Eloquent\Collection Les permissions directes de l'utilisateur
     */
    public function getCoreDirectPermissions()
    {
        // Si Spatie est utilisé, utilise sa relation permissions
        if (method_exists($this, 'permissions')) {
            return $this->permissions;
        }

        // Fallback : retourne une collection vide
        return collect([]);
    }

    /**
     * Vérifie si l'utilisateur a accès au back-office (admin panel).
     *
     * Cette méthode est une implémentation par défaut qui vérifie les rôles.
     * Les modèles peuvent surcharger cette méthode pour une logique personnalisée.
     *
     * @return bool True si l'utilisateur peut accéder au back-office
     */
    public function canAccessBackoffice(): bool
    {
        // Vérifier si l'utilisateur a au moins un rôle qui donne accès au back-office
        $backofficeRoles = [
            'super_admin',
            'admin_plateforme',
            'editeur_chef',
            'directeur_collection',
            'editeur_associe',
            'reviewer',
            'journaliste',
        ];

        if (method_exists($this, 'hasAnyRole')) {
            // Utilise la méthode de Spatie
            return $this->hasAnyRole($backofficeRoles);
        }

        return false;
    }

    /**
     * Retourne le niveau d'accès de l'utilisateur basé sur son rôle le plus élevé.
     *
     * Utile pour les vérifications hiérarchiques.
     *
     * @return int Le niveau d'accès (0 si aucun rôle)
     */
    public function getAccessLevel(): int
    {
        // Si l'utilisateur a des rôles, retourne le niveau du rôle le plus élevé
        if (method_exists($this, 'roles') && !empty($this->roles)) {
            return $this->roles->max('level') ?? 0;
        }

        return 0;
    }
}
