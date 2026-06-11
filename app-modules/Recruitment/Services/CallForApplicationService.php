<?php

declare(strict_types=1);

namespace Modules\Recruitment\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Recruitment\Enums\CallStatus;
use Modules\Recruitment\Models\CallForApplication;
use Modules\User\Models\User;

/**
 * Service de gestion des appels à candidatures
 *
 * Centralise la logique métier pour :
 * - Création et mise à jour des appels
 * - Publication et fermeture
 * - Gestion des permissions
 */
class CallForApplicationService
{
    /**
     * Crée un nouvel appel à candidatures
     *
     * @param array $data
     * @param User $createur
     * @param string $maisonId
     * @return CallForApplication
     */
    public function creer(array $data, User $createur, string $maisonId): CallForApplication
    {
        return DB::transaction(function () use ($data, $createur, $maisonId) {
            $call = CallForApplication::create([
                'titre' => $data['titre'],
                'slug' => CallForApplication::genererSlug($data['titre']),
                'description' => $data['description'],
                'roles_vises' => $data['roles_vises'] ?? [],
                'conditions' => $data['conditions'] ?? null,
                'prerequis' => $data['prerequis'] ?? null,
                'date_limite' => $data['date_limite'],
                'nombre_postes' => $data['nombre_postes'] ?? 1,
                'continent' => $data['continent'] ?? null,
                'pays' => $data['pays'] ?? null,
                'ville' => $data['ville'] ?? null,
                'metadonnees' => $data['metadonnees'] ?? null,
                'maison_id' => $maisonId,
                'cree_par' => $createur->id,
                'statut' => CallStatus::BROUILLON->value,
            ]);

            return $call;
        });
    }

    /**
     * Met à jour un appel à candidatures
     *
     * @param CallForApplication $call
     * @param array $data
     * @return CallForApplication
     */
    public function mettreAJour(CallForApplication $call, array $data): CallForApplication
    {
        if (!$call->getStatutEnum()->estModifiable()) {
            throw new \DomainException('Cet appel ne peut pas être modifié dans son état actuel.');
        }

        return DB::transaction(function () use ($call, $data) {
            $updatable = ['titre', 'description', 'roles_vises', 'conditions', 'prerequis', 'date_limite', 'nombre_postes', 'metadonnees', 'continent', 'pays', 'ville'];

            foreach ($updatable as $field) {
                if (isset($data[$field])) {
                    $call->$field = $data[$field];
                }
            }

            if (isset($data['titre']) && $data['titre'] !== $call->getOriginal('titre')) {
                $call->slug = CallForApplication::genererSlug($data['titre'], $call->id);
            }

            $call->save();

            return $call;
        });
    }

    /**
     * Publie un appel à candidatures
     *
     * @param CallForApplication $call
     * @param User $editeur
     * @return CallForApplication
     */
    public function publier(CallForApplication $call, User $editeur): CallForApplication
    {
        if ($call->cree_par !== $editeur->id && !$editeur->hasRole(['editeur_chef', 'admin_plateforme'])) {
            throw new \DomainException('Vous n\'êtes pas autorisé à publier cet appel.');
        }

        if (!$call->publier()) {
            throw new \DomainException('Impossible de publier cet appel. Vérifiez son état.');
        }

        Event::dispatch(new \Modules\Recruitment\Events\CallPublished($call, $editeur));

        return $call;
    }

    /**
     * Ferme un appel à candidatures
     *
     * @param CallForApplication $call
     * @param User $editeur
     * @return CallForApplication
     */
    public function fermer(CallForApplication $call, User $editeur): CallForApplication
    {
        if ($call->cree_par !== $editeur->id && !$editeur->hasRole(['editeur_chef', 'admin_plateforme'])) {
            throw new \DomainException('Vous n\'êtes pas autorisé à fermer cet appel.');
        }

        if (!$call->fermer()) {
            throw new \DomainException('Impossible de fermer cet appel.');
        }

        Event::dispatch(new \Modules\Recruitment\Events\CallClosed($call, $editeur));

        return $call;
    }

    /**
     * Annule un appel à candidatures
     *
     * @param CallForApplication $call
     * @param User $editeur
     * @return CallForApplication
     */
    public function annuler(CallForApplication $call, User $editeur): CallForApplication
    {
        if ($call->cree_par !== $editeur->id && !$editeur->hasRole(['editeur_chef', 'admin_plateforme'])) {
            throw new \DomainException('Vous n\'êtes pas autorisé à annuler cet appel.');
        }

        if (!$call->annuler()) {
            throw new \DomainException('Impossible d\'annuler cet appel.');
        }

        return $call;
    }

    /**
     * Supprime un appel à candidatures
     *
     * @param CallForApplication $call
     * @param User $utilisateur
     * @return bool
     */
    public function supprimer(CallForApplication $call, User $utilisateur): bool
    {
        if ($call->cree_par !== $utilisateur->id && !$utilisateur->hasRole(['editeur_chef', 'admin_plateforme'])) {
            throw new \DomainException('Vous n\'êtes pas autorisé à supprimer cet appel.');
        }

        // Seuls les brouillons peuvent être supprimés
        if ($call->statut !== CallStatus::BROUILLON->value) {
            throw new \DomainException('Seuls les appels en brouillon peuvent être supprimés.');
        }

        return $call->delete();
    }

    /**
     * Récupère les appels publics (ouverts et non expirés)
     *
     * @param string|null $maisonId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAppelsPublics(?string $maisonId = null)
    {
        $query = CallForApplication::publics();

        if ($maisonId) {
            $query->where('maison_id', $maisonId);
        }

        return $query->orderBy('date_limite', 'asc')->get();
    }

    /**
     * Ferme automatiquement les appels expirés
     *
     * @return int Nombre d'appels fermés
     */
    public function fermerAppelsExpires(): int
    {
        if (!config('recruitment.calls.auto_close_expired', true)) {
            return 0;
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, \Modules\Recruitment\Models\CallForApplication> $appelsExpires */
        $appelsExpires = CallForApplication::where('statut', CallStatus::OUVERT->value)
            ->where('date_limite', '<', now())
            ->get();

        $count = 0;
        foreach ($appelsExpires as $appel) {
            $appel->fermer();
            $count++;
        }

        return $count;
    }
}
