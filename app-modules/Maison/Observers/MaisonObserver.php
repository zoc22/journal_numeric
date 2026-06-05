<?php

declare(strict_types=1);

namespace Modules\Maison\Observers;

use Modules\Maison\Models\Maison;
use Illuminate\Support\Str;

/**
 * Observateur du modèle Maison.
 * Déclenche des actions automatiques sur les événements du modèle.
 */
class MaisonObserver
{
    /**
     * Avant la création d'une maison.
     * Génère automatiquement le slug à partir du nom.
     *
     * @param Maison $maison
     * @return void
     */
    public function creating(Maison $maison): void
    {
        \Illuminate\Support\Facades\Log::info("MaisonObserver::creating called for " . $maison->nom);
        // Génération automatique du slug si non fourni
        if (empty($maison->slug)) {
            $maison->slug = Maison::generateSlug($maison->nom);
        }
    }

    /**
     * Après la création d'une maison.
     *
     * @param Maison $maison
     * @return void
     */
    public function created(Maison $maison): void
    {
        // Logique après création (notification, création des rôles par défaut, etc.)
        // TODO: Créer automatiquement les rôles par défaut pour cette maison
    }

    /**
     * Avant la mise à jour d'une maison.
     *
     * @param Maison $maison
     * @return void
     */
    public function updating(Maison $maison): void
    {
        // Si le nom change, le slug doit être regénéré
        if ($maison->isDirty('nom')) {
            $maison->slug = Maison::generateSlug($maison->nom);
        }
    }

    /**
     * Après la mise à jour d'une maison.
     *
     * @param Maison $maison
     * @return void
     */
    public function updated(Maison $maison): void
    {
        // Logique après mise à jour
    }

    /**
     * Avant la suppression d'une maison.
     *
     * @param Maison $maison
     * @return void
     */
    public function deleting(Maison $maison): void
    {
        // Vérifier si la maison a des articles avant suppression
        // À réactiver quand le module Article sera implémenté
        /*
        if ($maison->articles()->count() > 0) {
            throw new \Exception('Impossible de supprimer une maison qui a des articles.');
        }
        */
    }

    /**
     * Après la suppression d'une maison.
     *
     * @param Maison $maison
     * @return void
     */
    public function deleted(Maison $maison): void
    {
        // Logique après suppression
    }
}
