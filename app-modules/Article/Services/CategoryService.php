<?php

declare(strict_types=1);

namespace Modules\Article\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Article\Models\Category;

/**
 * Service de gestion des catégories *
 * Centralise la logique métier pour les catégories :
 * - Gestion hiérarchique
 * - Arborescence
 * - Cache
 */
class CategoryService
{
    /**
     * Crée une nouvelle catégorie
     *
     * @param array $data
     * @return Category
     */
    public function creer(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            $category = Category::create($data);

            // Met à jour le chemin hiérarchique
            $category->mettreAJourChemin();

            $this->clearCache();

            return $category;
        });
    }

    /**
     * Met à jour une catégorie
     *
     * @param Category $category
     * @param array $data
     * @return Category
     */
    public function mettreAJour(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            $ancienParentId = $category->parent_id;

            $category->update($data);

            // Si le parent a changé, met à jour l'arborescence
            if (isset($data['parent_id']) && $data['parent_id'] !== $ancienParentId) {
                $category->mettreAJourChemin();
            }

            $this->clearCache();

            return $category;
        });
    }

    /**
     * Supprime une catégorie
     *
     * @param Category $category
     * @param bool $reassignChildren Réassigne les enfants à un autre parent
     * @param string|null $newParentId
     * @return bool
     */
    public function supprimer(Category $category, bool $reassignChildren = false, ?string $newParentId = null): bool
    {
        return DB::transaction(function () use ($category, $reassignChildren, $newParentId) {
            if ($reassignChildren && $newParentId) {
                // Réassigne les enfants au nouveau parent
                $category->enfants()->update(['parent_id' => $newParentId]);

                // Met à jour le chemin des enfants réassignés
                $newParent = Category::find($newParentId);
                if ($newParent) {
                    foreach ($category->enfants as $enfant) {
                        $enfant->mettreAJourChemin();
                    }
                }
            } elseif ($reassignChildren && !$newParentId) {
                // Déplace les enfants au niveau racine
                $category->enfants()->update(['parent_id' => null]);
                foreach ($category->enfants as $enfant) {
                    $enfant->mettreAJourChemin();
                }
            }

            $deleted = $category->delete();

            $this->clearCache();

            return $deleted;
        });
    }

    /**
     * Obtient l'arborescence complète des catégories
     *
     * @param string|null $maisonId
     * @return Collection
     */
    public function getArborescence(?string $maisonId = null): Collection
    {
        $cacheKey = $maisonId ? "categories.tree.{$maisonId}" : "categories.tree.global";

        $cache = Cache::store();
        if ($cache->supportsTags()) {
            $cache = $cache->tags(['categories']);
        }

        return $cache->remember($cacheKey, 3600, function () use ($maisonId) {
            $query = Category::with(['enfants' => function ($q) {
                $q->orderBy('ordre');
            }]);

            if ($maisonId) {
                $query->where('maison_id', $maisonId);
            }

            return $query->whereNull('parent_id')
                         ->orderBy('ordre')
                         ->get();
        });
    }

    /**
     * Obtient le chemin complet d'une catégorie
     *
     * @param Category $category
     * @return array
     */
    public function getCheminComplet(Category $category): array
    {
        return $category->chemin_complet;
    }

    /**
     * Nettoie le cache des catégories
     *
     * @return void
     */
    protected function clearCache(): void
    {
        $cache = Cache::store();
        if ($cache->supportsTags()) {
            $cache->tags(['categories'])->flush();
        } else {
            // Si pas de tags, on peut au moins essayer de vider les clés connues
            // ou vider tout le cache si acceptable
            Cache::flush();
        }
    }
}
