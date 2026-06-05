<?php

declare(strict_types=1);

namespace Modules\Maison\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Maison\Http\Resources\MembreResource;
use Modules\Maison\Models\MembreMaison;
use Modules\Maison\Models\Maison;
use Modules\User\Models\User;

/**
 * Service de gestion des membres des maisons d'édition.
 */
class MembreService
{
    /**
     * Ajoute un membre à une maison.
     *
     * @param string $maisonId ID de la maison
     * @param string $utilisateurId ID de l'utilisateur
     * @param string $roleId ID du rôle à assigner
     * @return MembreResource
     * @throws ModelNotFoundException|\Exception
     */
    public function ajouterMembre(string $maisonId, string $utilisateurId, int|string $roleId): MembreResource
    {
        // Vérifier que la maison existe
        $maison = Maison::findOrFail($maisonId);

        // Vérifier que l'utilisateur existe
        $utilisateur = User::findOrFail($utilisateurId);

        // Vérifier que l'utilisateur n'est pas déjà membre
        if ($maison->estMembre($utilisateurId)) {
            throw new \Exception('Cet utilisateur est déjà membre de cette maison.');
        }

        // Créer le membre
        $membre = MembreMaison::create([
            'maison_id' => $maisonId,
            'utilisateur_id' => $utilisateurId,
            'role_id' => $roleId,
            'est_actif' => true,
            'a_rejoint_le' => now(),
        ]);

        return new MembreResource($membre->load(['utilisateur', 'role']));
    }

    /**
     * Modifie le rôle d'un membre.
     *
     * @param string $membreId ID du membre
     * @param int|string $roleId Nouveau ID du rôle
     * @return MembreResource
     * @throws ModelNotFoundException
     */
    public function modifierRole(string $membreId, int|string $roleId): MembreResource
    {
        $membre = MembreMaison::findOrFail($membreId);
        $membre->changerRole($roleId);

        return new MembreResource($membre->fresh(['utilisateur', 'role']));
    }

    /**
     * Retire un membre d'une maison.
     *
     * @param string $membreId ID du membre
     * @return bool
     * @throws ModelNotFoundException
     */
    public function retirerMembre(string $membreId): bool
    {
        $membre = MembreMaison::findOrFail($membreId);
        return $membre->desactiver();
    }

    /**
     * Active un membre (réintègre la maison).
     *
     * @param string $membreId ID du membre
     * @return MembreResource
     * @throws ModelNotFoundException
     */
    public function activerMembre(string $membreId): MembreResource
    {
        $membre = MembreMaison::findOrFail($membreId);
        $membre->activer();

        return new MembreResource($membre->fresh(['utilisateur', 'role']));
    }

    /**
     * Désactive un membre (quitte la maison).
     *
     * @param string $membreId ID du membre
     * @return MembreResource
     * @throws ModelNotFoundException
     */
    public function desactiverMembre(string $membreId): MembreResource
    {
        $membre = MembreMaison::findOrFail($membreId);
        $membre->desactiver();

        return new MembreResource($membre->fresh(['utilisateur', 'role']));
    }

    /**
     * Récupère un membre par son ID.
     *
     * @param string $membreId ID du membre
     * @return MembreResource
     * @throws ModelNotFoundException
     */
    public function findById(string $membreId): MembreResource
    {
        $membre = MembreMaison::with(['utilisateur', 'role'])
            ->findOrFail($membreId);

        return new MembreResource($membre);
    }

    /**
     * Liste les membres d'une maison.
     *
     * @param string $maisonId ID de la maison
     * @param array<string, mixed> $filtres Filtres
     * @param int $perPage Nombre d'éléments par page
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function listByMaison(string $maisonId, array $filtres = [], int $perPage = 15)
    {
        $query = MembreMaison::with(['utilisateur', 'role'])
            ->where('maison_id', $maisonId);

        // Filtre par statut actif/inactif
        if (isset($filtres['est_actif'])) {
            $query->where('est_actif', filter_var($filtres['est_actif'], FILTER_VALIDATE_BOOLEAN));
        }

        // Filtre par rôle
        if (!empty($filtres['role_id'])) {
            $query->where('role_id', $filtres['role_id']);
        }

        // Recherche textuelle sur l'utilisateur
        if (!empty($filtres['search'])) {
            $query->whereHas('utilisateur', function ($q) use ($filtres) {
                $q->where('nom', 'LIKE', "%{$filtres['search']}%")
                  ->orWhere('prenom', 'LIKE', "%{$filtres['search']}%")
                  ->orWhere('email', 'LIKE', "%{$filtres['search']}%");
            });
        }

        $membres = $query->paginate($perPage);

        return MembreResource::collection($membres);
    }

    /**
     * Vérifie si un utilisateur est membre d'une maison.
     *
     * @param string $maisonId ID de la maison
     * @param string $utilisateurId ID de l'utilisateur
     * @return bool
     */
    public function estMembre(string $maisonId, string $utilisateurId): bool
    {
        return MembreMaison::where('maison_id', $maisonId)
            ->where('utilisateur_id', $utilisateurId)
            ->where('est_actif', true)
            ->exists();
    }

    /**
     * Récupère le rôle d'un utilisateur dans une maison.
     *
     * @param string $maisonId ID de la maison
     * @param string $utilisateurId ID de l'utilisateur
     * @return array<string, mixed>|null
     */
    public function getRoleUtilisateur(string $maisonId, string $utilisateurId): ?array
    {
        $membre = MembreMaison::with('role')
            ->where('maison_id', $maisonId)
            ->where('utilisateur_id', $utilisateurId)
            ->where('est_actif', true)
            ->first();

        if (!$membre || !$membre->role) {
            return null;
        }

        return [
            'id' => $membre->role->id,
            'name' => $membre->role->name,
            'level' => $membre->role->level ?? 0,
        ];
    }
}
