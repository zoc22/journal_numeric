<?php

declare(strict_types=1);

namespace Modules\Maison\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Modules\Maison\Http\Resources\MaisonCollection;
use Modules\Maison\Http\Resources\MaisonResource;
use Modules\Maison\Models\Maison;
use Modules\User\Models\User;

/**
 * Service de gestion des maisons d'édition.
 */
class MaisonService
{
    /**
     * Crée une nouvelle maison d'édition.
     *
     * @param array<string, mixed> $data Données de la maison
     * @return MaisonResource La maison créée
     * @throws \Exception
     */
    public function create(array $data): MaisonResource
    {
        // Génération automatique du slug si non fourni
        if (empty($data['slug'])) {
            $data['slug'] = Maison::generateSlug($data['nom']);
        }

        $maison = Maison::create($data);

        if (!$maison) {
            throw new \Exception('Impossible de créer la maison d\'édition.');
        }

        return new MaisonResource($maison);
    }

    /**
     * Met à jour une maison d'édition.
     *
     * @param string $maisonId ID de la maison
     * @param array<string, mixed> $data Données à mettre à jour
     * @return MaisonResource La maison mise à jour
     * @throws ModelNotFoundException
     */
    public function update(string $maisonId, array $data): MaisonResource
    {
        $maison = Maison::findOrFail($maisonId);

        $maison->update($data);

        return new MaisonResource($maison->fresh());
    }

    /**
     * Supprime une maison d'édition.
     *
     * @param string $maisonId ID de la maison
     * @return bool
     * @throws ModelNotFoundException|\Exception
     */
    public function delete(string $maisonId): bool
    {
        $maison = Maison::findOrFail($maisonId);

        // Vérification : une maison avec des articles ne peut pas être supprimée
        // À réactiver quand le module Article sera implémenté
        /*
        if ($maison->articles()->count() > 0) {
            throw new \Exception('Impossible de supprimer une maison qui a des articles.');
        }
        */

        return $maison->delete();
    }

    /**
     * Récupère une maison par son ID.
     *
     * @param string $maisonId ID de la maison
     * @param array<string> $relations Relations à charger
     * @return MaisonResource
     * @throws ModelNotFoundException
     */
    public function findById(string $maisonId, array $relations = []): MaisonResource
    {
        $query = Maison::query();

        if (!empty($relations)) {
            $query->with($relations);
        }

        $maison = $query->findOrFail($maisonId);

        return new MaisonResource($maison);
    }

    /**
     * Récupère une maison par son slug.
     *
     * @param string $slug Slug de la maison
     * @return MaisonResource
     * @throws ModelNotFoundException
     */
    public function findBySlug(string $slug): MaisonResource
    {
        $maison = Maison::where('slug', $slug)->firstOrFail();

        return new MaisonResource($maison);
    }

    /**
     * Liste paginée des maisons.
     *
     * @param array<string, mixed> $filtres Filtres de recherche
     * @param int $perPage Nombre d'éléments par page
     * @return MaisonCollection
     */
    public function paginate(array $filtres = [], int $perPage = 15): MaisonCollection
    {
        $query = Maison::query();

        // Filtre par statut
        if (!empty($filtres['statut'])) {
            $query->where('statut', $filtres['statut']);
        }

        // Recherche textuelle
        if (!empty($filtres['search'])) {
            $query->search($filtres['search']);
        }

        // Tri
        $orderBy = $filtres['order_by'] ?? 'created_at';
        $orderDir = $filtres['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        // Chargement des relations pour l'affichage
        $query->withCount(['membres']);

        $maisons = $query->paginate($perPage);

        return new MaisonCollection($maisons);
    }

    /**
     * Liste des maisons actives (publiques).
     *
     * @param int $perPage Nombre d'éléments par page
     * @return MaisonCollection
     */
    public function getActiveMaisons(int $perPage = 15): MaisonCollection
    {
        $maisons = Maison::actives()
            ->orderBy('nom', 'asc')
            ->paginate($perPage);

        return new MaisonCollection($maisons);
    }

    /**
     * Valide une maison (admin plateforme).
     *
     * @param string $maisonId ID de la maison
     * @param string $valideurId ID de l'admin qui valide
     * @return MaisonResource
     * @throws ModelNotFoundException|\Exception
     */
    public function valider(string $maisonId, string $valideurId): MaisonResource
    {
        $maison = Maison::findOrFail($maisonId);

        if (!$maison->estEnAttente()) {
            throw new \Exception('Seule une maison en attente peut être validée.');
        }

        $maison->activer($valideurId);

        return new MaisonResource($maison->fresh());
    }

    /**
     * Rejette une maison (admin plateforme).
     *
     * @param string $maisonId ID de la maison
     * @param string $valideurId ID de l'admin qui rejette
     * @return MaisonResource
     * @throws ModelNotFoundException|\Exception
     */
    public function rejeter(string $maisonId, string $valideurId): MaisonResource
    {
        $maison = Maison::findOrFail($maisonId);

        if (!$maison->estEnAttente()) {
            throw new \Exception('Seule une maison en attente peut être rejetée.');
        }

        $maison->rejeter($valideurId);

        return new MaisonResource($maison->fresh());
    }

    /**
     * Suspend une maison active.
     *
     * @param string $maisonId ID de la maison
     * @return MaisonResource
     * @throws ModelNotFoundException|\Exception
     */
    public function suspendre(string $maisonId): MaisonResource
    {
        $maison = Maison::findOrFail($maisonId);

        if (!$maison->estValidee()) {
            throw new \Exception('Seule une maison active peut être suspendue.');
        }

        $maison->suspendre();

        return new MaisonResource($maison->fresh());
    }

    /**
     * Active une maison suspendue.
     *
     * @param string $maisonId ID de la maison
     * @param string $valideurId ID de l'admin qui active
     * @return MaisonResource
     * @throws ModelNotFoundException|\Exception
     */
    public function activer(string $maisonId, string $valideurId): MaisonResource
    {
        $maison = Maison::findOrFail($maisonId);

        if (!$maison->estSuspendue()) {
            throw new \Exception('Seule une maison suspendue peut être réactivée.');
        }

        $maison->activer($valideurId);

        return new MaisonResource($maison->fresh());
    }

    /**
     * Compte le nombre de maisons par statut.
     *
     * @return array<string, int>
     */
    public function countByStatut(): array
    {
        return [
            'total' => Maison::count(),
            'en_attente' => Maison::enAttente()->count(),
            'active' => Maison::actives()->count(),
            'suspendue' => Maison::suspendues()->count(),
            'rejetee' => Maison::where('statut', 'rejetee')->count(),
        ];
    }
}
