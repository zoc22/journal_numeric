<?php
// app-modules/Core/Traits/HasPermissions.php

namespace Modules\Core\Traits;

use Illuminate\Support\Facades\Gate;

/**
 * Ce trait ajoute des méthodes de vérification des permissions à un modèle (typiquement User).
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasPermissions
{
    /**
     * Vérifie si l'utilisateur possède une permission donnée.
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        // Utilise Gate pour vérifier la permission via Spatie ou le système natif
        if (Gate::forUser($this)->allows($permission)) {
            return true;
        }

        // Fallback sur la méthode can() si elle existe (Eloquent standard)
        if (method_exists($this, 'can')) {
            return $this->can($permission);
        }

        return false;
    }

    /**
     * Vérifie si l'utilisateur possède un rôle donné.
     *
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        // Éviter la récursion infinie en ne s'appelant pas soi-même si Spatie n'est pas là
        // On vérifie si le modèle a une méthode hasRole définie par une autre classe/trait (comme Spatie)
        // Mais method_exists($this, 'hasRole') retournera toujours true ici.
        
        // On vérifie si Spatie\Permission\Traits\HasRoles est utilisé (en cherchant un attribut ou une relation spécifique)
        if (method_exists($this, 'roles')) {
            // Probablement Spatie\Permission
            // On évite d'appeler $this->hasRole($role) directement car c'est NOUS qui sommes appelés.
            // On peut essayer de vérifier via l'attribut roles ou une autre méthode de Spatie.
            // Mais le plus simple est de vérifier l'attribut 'role' si on est dans un système simple.
        }

        // Fallback : on suppose qu'il existe un attribut 'role' ou une colonne dans la DB
        $attributes = $this->getAttributes();
        if (isset($this->role)) {
            return $this->role === $role;
        }
        if (array_key_exists('role', $attributes)) {
            return $attributes['role'] === $role;
        }

        return false;
    }

    /**
     * Attribue un rôle à l'utilisateur.
     *
     * @param string $role
     * @return void
     */
    public function assignRole(string $role): void
    {
        // Éviter la récursion infinie
        // Si on a un attribut role, on le met à jour
        if (isset($this->role) || array_key_exists('role', $this->getAttributes())) {
            $this->role = $role;
            if (method_exists($this, 'save')) {
                $this->save();
            }
        }
    }
}
