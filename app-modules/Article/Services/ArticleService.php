<?php

declare(strict_types=1);

namespace Modules\Article\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Article\Models\Article;
use Modules\Article\Models\Tag;
use Modules\User\Models\User;

/**
 * Service de gestion des articles
 *
 * Centralise la logique métier pour la gestion des articles :
 * - Création, mise à jour, suppression
 * - Gestion des catégories et tags
 * - Cache et performance
 */
class ArticleService
{
    /**
     * @var VersionService
     */
    protected VersionService $versionService;

    /**
     * Constructeur
     */
    public function __construct(VersionService $versionService)
    {
        $this->versionService = $versionService;
    }

    /**
     * Crée un nouvel article
     *
     * @param array $data
     * @return Article
     */
    public function creer(array $data): Article
    {
        return DB::transaction(function () use ($data) {
            // Crée l'article
            $article = Article::create($data);

            // Gère les tags
            if (!empty($data['tags'])) {
                $this->synchroniserTags($article, $data['tags']);
            }

            // Crée la version initiale
            $this->versionService->creerVersion($article, $article->auteur, 'Version initiale');

            // Nettoie le cache
            $this->clearCache($article);

            Log::info('Article créé', [
                'article_id' => $article->id,
                'titre' => $article->titre,
                'auteur_id' => $article->auteur_id,
            ]);

            return $article;
        });
    }

    /**
     * Met à jour un article existant
     *
     * @param Article $article
     * @param array $data
     * @return Article
     */
    public function mettreAJour(Article $article, array $data): Article
    {
        return DB::transaction(function () use ($article, $data) {
            $ancienStatut = $article->statut;

            // Met à jour l'article
            $article->update($data);

            // Gère les tags
            if (isset($data['tags'])) {
                $this->synchroniserTags($article, $data['tags']);
            }

            // Crée une version si nécessaire
            $resumeModif = $data['resume_modifications'] ?? 'Mise à jour de l\'article';
            $this->versionService->creerVersion($article, $article->auteur, $resumeModif);

            // Nettoie le cache
            $this->clearCache($article);

            Log::info('Article mis à jour', [
                'article_id' => $article->id,
                'titre' => $article->titre,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $article->statut,
            ]);

            return $article;
        });
    }

    /**
     * Synchronise les tags d'un article
     *
     * @param Article $article
     * @param array $tagNoms
     * @return void
     */
    protected function synchroniserTags(Article $article, array $tagNoms): void
    {
        $tagIds = [];

        foreach ($tagNoms as $nom) {
            $nom = trim($nom);
            if (empty($nom)) {
                continue;
            }

            // Crée ou trouve le tag
            $tag = Tag::firstOrCreate(
                [
                    'slug' => Tag::genererSlug($nom),
                    'maison_id' => $article->maison_id,
                ],
                [
                    'nom' => $nom,
                ]
            );

            $tagIds[] = $tag->id;
        }

        $article->tags()->sync($tagIds);
    }

    /**
     * Supprime un article
     *
     * @param Article $article
     * @return bool
     */
    public function supprimer(Article $article): bool
    {
        return DB::transaction(function () use ($article) {
            // Détache les relations
            $article->categories()->detach();
            $article->tags()->detach();

            // Supprime l'article
            $deleted = $article->delete();

            if ($deleted) {
                // Nettoie le cache
                $this->clearCache($article);

                Log::info('Article supprimé', [
                    'article_id' => $article->id,
                    'titre' => $article->titre,
                ]);
            }

            return $deleted;
        });
    }

    /**
     * Restaure une version antérieure
     *
     * @param Article $article
     * @param \Modules\Article\Models\ArticleVersion $version
     * @param User $utilisateur
     * @return Article
     */
    public function restaurerVersion(Article $article, \Modules\Article\Models\ArticleVersion $version, User $utilisateur): Article
    {
        return DB::transaction(function () use ($article, $version, $utilisateur) {
            // Restaure le contenu
            $article->titre = $version->titre;
            $article->contenu = $version->contenu;
            $article->resume = $version->resume;
            $article->image_principale = $version->image_principale;
            $article->galerie_medias = $version->galerie_medias;
            $article->meta_title = $version->meta_title;
            $article->meta_description = $version->meta_description;
            $article->meta_keywords = $version->meta_keywords;

            // Incrémente le numéro de version
            $article->version_numero = $article->versions()->max('numero_version') + 1;

            $article->save();

            // Crée une nouvelle version pour la restauration
            $this->versionService->creerVersion(
                $article,
                $utilisateur,
                "Restauration de la version {$version->numero_version}"
            );

            // Nettoie le cache
            $this->clearCache($article);

            Log::info('Version d\'article restaurée', [
                'article_id' => $article->id,
                'version_restauree' => $version->numero_version,
                'utilisateur_id' => $utilisateur->id,
            ]);

            return $article;
        });
    }

    /**
     * Nettoie le cache pour un article
     *
     * @param Article $article
     * @return void
     */
    protected function clearCache(Article $article): void
    {
        Cache::forget("article.{$article->id}");
        Cache::forget("article.{$article->slug}");
    }

    /**
     * Obtient un article avec cache
     *
     * @param string $id
     * @return Article|null
     */
    public function getAvecCache(string $id): ?Article
    {
        $cacheKey = "article.{$id}";

        return Cache::remember($cacheKey, 3600, function () use ($id) {
            return Article::with(['auteur', 'categories', 'tags'])
                ->find($id);
        });
    }
}
