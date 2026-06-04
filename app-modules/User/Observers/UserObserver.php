<?php
// app-modules/User/Observers/UserObserver.php

namespace Modules\User\Observers;

use Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Observateur User – Réagit aux événements du modèle User.
 *
 * Les observateurs permettent d'exécuter du code automatiquement
 * lorsque certaines actions sont effectuées sur le modèle.
 *
 * Ici, on attribue automatiquement le rôle "lecteur" à tout nouvel
 * utilisateur qui n'a pas encore de rôle, et on vide le cache
 * des compteurs pour que les statistiques restent à jour.
 */
class UserObserver
{
    /**
     * Événement "created" – se déclenche après la création d'un utilisateur.
     *
     * @param User $user
     * @return void
     */
    public function created(User $user): void
    {
        // Si l'utilisateur n'a aucun rôle, on lui attribue le rôle "lecteur"
        // Cela garantit que chaque utilisateur a au moins un rôle de base.
        if ($user->roles()->count() === 0) {
            $user->assignRole('lecteur');
        }

        // On vide le cache du compteur d'utilisateurs actifs
        // Pour que la prochaine requête recalcule la valeur réelle.
        Cache::forget('active_users_count');
    }

    /**
     * Événement "updated" – se déclenche après la mise à jour d'un utilisateur.
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user): void
    {
        // Si le statut "is_active" a changé, on vide le cache
        if ($user->wasChanged('is_active')) {
            Cache::forget('active_users_count');
        }
    }

    /**
     * Événement "deleted" – se déclenche après la suppression d'un utilisateur.
     *
     * @param User $user
     * @return void
     */
    public function deleted(User $user): void
    {
        Cache::forget('active_users_count');
    }

    /**
     * Événement "restored" – se déclenche après la restauration d'un utilisateur supprimé.
     *
     * @param User $user
     * @return void
     */
    public function restored(User $user): void
    {
        Cache::forget('active_users_count');
    }
}
