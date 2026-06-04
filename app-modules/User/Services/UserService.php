<?php
// app-modules/User/Services/UserService.php

namespace Modules\User\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\User\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Service User – Contient toute la logique métier liée aux utilisateurs.
 *
 * Ce service centralise les opérations sur les utilisateurs et permet de :
 * - Créer, lire, mettre à jour, supprimer des utilisateurs (CRUD)
 * - Activer/désactiver des utilisateurs
 * - Gérer l'assignation et le retrait des rôles
 * - Récupérer les utilisateurs avec pagination
 *
 * Avantages de l'approche par service :
 * - Sépare la logique métier de la couche contrôleur
 * - Facilite les tests unitaires
 * - Rend le code plus maintenable et réutilisable
 * - Permet de partager la logique métier entre plusieurs contrôleurs
 *
 * @see UserController utilise ce service pour les opérations utilisateurs
 */
class UserService
{
    /**
     * Récupère la liste paginée des utilisateurs.
     *
     * Charge les rôles associés pour éviter les requêtes N+1 (eager loading).
     * Les utilisateurs sont triés par date de création (plus récents en premier).
     *
     * @param int $perPage Nombre d'utilisateurs par page (par défaut 15)
     * @return LengthAwarePaginator Paginator contenant les utilisateurs et les métadonnées
     */
    public function getPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            // Charge les rôles pour éviter les requêtes N+1
            ->with('roles')
            // Trie par date de création (plus récents en premier)
            ->orderBy('created_at', 'desc')
            // Retourne la pagination
            ->paginate($perPage);
    }

    /**
     * Crée un nouvel utilisateur.
     *
     * @param array<string, mixed> $data Données de l'utilisateur (nom, email, password, etc.)
     * @return User L'utilisateur créé
     * @throws \Exception Si les données sont invalides
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Met à jour un utilisateur existant.
     *
     * @param User $user L'utilisateur à modifier
     * @param array<string, mixed> $data Les nouvelles données
     * @return bool True si la mise à jour a réussi
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * Supprime un utilisateur (soft delete).
     *
     * La suppression est douce (soft delete) ce qui signifie que l'utilisateur
     * reste dans la base de données mais est marqué comme supprimé (deleted_at).
     * Il peut être restauré avec restore().
     *
     * @param User $user L'utilisateur à supprimer
     * @return bool|null True si la suppression a réussi
     */
    public function delete(User $user): ?bool
    {
        return $user->delete();
    }

    /**
     * Active un utilisateur.
     *
     * Définit l'attribut is_active à true, ce qui permet à l'utilisateur
     * de se connecter et d'accéder à la plateforme.
     *
     * @param User $user L'utilisateur à activer
     * @return bool True si l'activation a réussi
     */
    public function activate(User $user): bool
    {
        return $user->update(['is_active' => true]);
    }

    /**
     * Désactive un utilisateur.
     *
     * Définit l'attribut is_active à false, ce qui empêche l'utilisateur
     * de se connecter et d'accéder à la plateforme (sans le supprimer).
     *
     * @param User $user L'utilisateur à désactiver
     * @return bool True si la désactivation a réussi
     */
    public function deactivate(User $user): bool
    {
        return $user->update(['is_active' => false]);
    }

    /**
     * Assigne un rôle à un utilisateur.
     *
     * Ajoute un rôle à l'utilisateur. L'utilisateur peut avoir plusieurs rôles.
     * Cette méthode utilise Spatie\Permission pour la gestion des rôles.
     *
     * @param User $user L'utilisateur auquel assigner le rôle
     * @param Role $role Le rôle à assigner
     * @return void
     */
    public function assignRole(User $user, Role $role): void
    {
        $user->assignRole($role);
    }

    /**
     * Retire un rôle à un utilisateur.
     *
     * Supprime un rôle de l'utilisateur. Si l'utilisateur n'a pas ce rôle,
     * la méthode s'exécute sans erreur.
     *
     * @param User $user L'utilisateur dont retirer le rôle
     * @param Role $role Le rôle à retirer
     * @return void
     */
    public function removeRole(User $user, Role $role): void
    {
        $user->removeRole($role);
    }
}
