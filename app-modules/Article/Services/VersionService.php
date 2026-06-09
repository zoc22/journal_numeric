<?php

declare(strict_types=1);

namespace Modules\Article\Services;

use Illuminate\Support\Facades\DB;
use Modules\Article\Models\Article;
use Modules\Article\Models\ArticleVersion;
use Modules\User\Models\User;

/**
 * Service de gestion des versions d'articles
 *
 * Centralise la logique métier pour le versioning :
 * - Création de versions
 * - Comparaison entre versions
 * - Nettoyage des anciennes versions
 */
class VersionService
{
    /**
     * Crée une nouvelle version d'un article
     *
     * @param Article $article
     * @param User $auteur
     * @param string|null $resumeModifications
     * @return ArticleVersion
     */
    public function creerVersion(Article $article, User $auteur, ?string $resumeModifications = null): ArticleVersion
    {
        return DB::transaction(function () use ($article, $auteur, $resumeModifications) {
            $ancienneVersionActuelle = $article->versionActuelle;

            // Calcule le numéro de la nouvelle version
            $nouveauNumero = (int) ($article->versions()->max('numero_version') ?? 0) + 1;
            
            // Prépare les changements détaillés
            $changements = null;
            if ($ancienneVersionActuelle) {
                $changements = $this->comparerVersions($ancienneVersionActuelle, $article);
            }

            // Crée la nouvelle version
            $version = new ArticleVersion();
            $version->article_id = $article->id;
            $version->numero_version = $nouveauNumero;
            $version->titre = $article->titre;
            $version->contenu = $article->contenu;
            $version->resume = $article->resume;
            $version->image_principale = $article->image_principale;
            $version->galerie_medias = $article->galerie_medias;
            $version->meta_title = $article->meta_title;
            $version->meta_description = $article->meta_description;
            $version->meta_keywords = $article->meta_keywords;
            $version->cree_par = $auteur->id;
            $version->resume_modifications = $resumeModifications;
            $version->changements_detailles = $changements;
            $version->est_publiee = $article->statut === 'publie';
            $version->est_actuelle = true;
            $version->save();

            // Met à jour l'article avec la nouvelle version
            $article->version_actuelle_id = $version->id;
            $article->version_numero = $nouveauNumero;
            $article->saveQuietly();

            // Marque l'ancienne version comme non actuelle
            if ($ancienneVersionActuelle) {
                $ancienneVersionActuelle->update(['est_actuelle' => false]);
            }

            // Nettoie les anciennes versions si nécessaire
            $this->nettoyerAnciennesVersions($article);

            return $version;
        });
    }

    /**
     * Compare deux versions et retourne les différences
     *
     * @param ArticleVersion $ancienneVersion
     * @param Article $nouvelArticle
     * @return array|null
     */
    protected function comparerVersions(ArticleVersion $ancienneVersion, Article $nouvelArticle): ?array
    {
        $differences = [];

        // Compare le titre
        if ($ancienneVersion->titre !== $nouvelArticle->titre) {
            $differences['titre'] = [
                'avant' => $ancienneVersion->titre,
                'apres' => $nouvelArticle->titre,
            ];
        }

        // Compare le résumé
        if ($ancienneVersion->resume !== $nouvelArticle->resume) {
            $differences['resume'] = [
                'avant' => $ancienneVersion->resume,
                'apres' => $nouvelArticle->resume,
            ];
        }

        // Compare l'image principale
        if ($ancienneVersion->image_principale !== $nouvelArticle->image_principale) {
            $differences['image_principale'] = [
                'avant' => $ancienneVersion->image_principale,
                'apres' => $nouvelArticle->image_principale,
            ];
        }

        // Note : Pour le contenu, on ne stocke pas la différence complète car trop volumineuse
        if (strlen($ancienneVersion->contenu) !== strlen($nouvelArticle->contenu)) {
            $differences['contenu'] = [
                'longueur_avant' => strlen($ancienneVersion->contenu),
                'longueur_apres' => strlen($nouvelArticle->contenu),
            ];
        }

        return empty($differences) ? null : $differences;
    }

    /**
     * Nettoie les anciennes versions au-delà du maximum configuré
     *
     * @param Article $article
     * @return void
     */
    protected function nettoyerAnciennesVersions(Article $article): void
    {
        $maxVersions = config('article.versioning.max_versions', 50);

        $versionsCount = $article->versions()->count();

        if ($versionsCount > $maxVersions) {
            $aSupprimer = $versionsCount - $maxVersions;

            $article->versions()
                ->where('est_publiee', false)
                ->where('est_actuelle', false)
                ->orderBy('numero_version', 'asc')
                ->limit($aSupprimer)
                ->delete();
        }
    }

    /**
     * Compare deux versions spécifiques
     *
     * @param ArticleVersion $version1
     * @param ArticleVersion $version2
     * @return array
     */
    public function comparer(ArticleVersion $version1, ArticleVersion $version2): array
    {
        return $version1->comparer($version2);
    }
}
