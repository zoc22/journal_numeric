<?php

declare(strict_types=1);

namespace Modules\Media\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Article\Models\Article;
use Modules\Media\Models\ArticleMedia;
use Modules\Media\Models\Media;
use Modules\User\Models\User;

/**
 * Service de gestion des associations article-média
 *
 * Gère l'attachement, le détachement et l'organisation
 * des médias associés aux articles.
 */
class ArticleMediaService
{
    /**
     * Attache un média à un article
     *
     * @param Article $article
     * @param Media $media
     * @param string $typeUsage
     * @param array $metadonnees
     * @param int|null $ordre
     * @return ArticleMedia
     */
    public function attach(
        Article $article,
        Media $media,
        string $typeUsage = 'inline',
        array $metadonnees = [],
        ?int $ordre = null
    ): ArticleMedia {
        // Vérifie si le média est déjà attaché
        $existing = ArticleMedia::where('article_id', $article->id)
            ->where('media_id', $media->id)
            ->first();

        if ($existing) {
            throw new \DomainException('Ce média est déjà associé à cet article');
        }

        // Détermine l'ordre
        if ($ordre === null) {
            $maxOrdre = ArticleMedia::where('article_id', $article->id)
                ->where('type_usage', $typeUsage)
                ->max('ordre_affichage') ?? 0;
            $ordre = $maxOrdre + 1;
        }

        return DB::transaction(function () use ($article, $media, $typeUsage, $metadonnees, $ordre) {
            $articleMedia = ArticleMedia::create([
                'article_id' => $article->id,
                'media_id' => $media->id,
                'type_usage' => $typeUsage,
                'metadonnees' => $metadonnees,
                'ordre_affichage' => $ordre,
                'est_actif' => true,
            ]);

            // Si c'est une image de couverture, met à jour l'article
            if ($typeUsage === 'couverture') {
                $article->image_principale = $media->url;
                $article->saveQuietly();
            }

            return $articleMedia;
        });
    }

    /**
     * Détache un média d'un article
     *
     * @param Article $article
     * @param Media $media
     * @return bool
     */
    public function detach(Article $article, Media $media): bool
    {
        /** @var ArticleMedia|null $articleMedia */
        $articleMedia = ArticleMedia::where('article_id', $article->id)
            ->where('media_id', $media->id)
            ->first();

        if (!$articleMedia) {
            throw new \DomainException('Ce média n\'est pas associé à cet article');
        }

        $deleted = (bool) $articleMedia->delete();

        // Si c'était l'image de couverture, la retire de l'article
        if ($articleMedia->type_usage === 'couverture') {
            $article->image_principale = null;
            $article->saveQuietly();
        }

        return $deleted;
    }

    /**
     * Met à jour l'ordre des médias
     *
     * @param Article $article
     * @param array $ordreMediaIds
     * @param string $typeUsage
     * @return void
     */
    public function updateOrder(Article $article, array $ordreMediaIds, string $typeUsage = 'inline'): void
    {
        DB::transaction(function () use ($article, $ordreMediaIds, $typeUsage) {
            foreach ($ordreMediaIds as $index => $mediaId) {
                ArticleMedia::where('article_id', $article->id)
                    ->where('media_id', $mediaId)
                    ->where('type_usage', $typeUsage)
                    ->update(['ordre_affichage' => $index + 1]);
            }
        });
    }

    /**
     * Définit l'image de couverture d'un article
     *
     * @param Article $article
     * @param Media $media
     * @param array $metadonnees
     * @return ArticleMedia
     */
    public function setCoverImage(Article $article, Media $media, array $metadonnees = []): ArticleMedia
    {
        // Supprime l'ancienne image de couverture
        ArticleMedia::where('article_id', $article->id)
            ->where('type_usage', 'couverture')
            ->delete();

        return $this->attach($article, $media, 'couverture', $metadonnees, 1);
    }

    /**
     * Récupère les médias d'un article par type
     *
     * @param Article $article
     * @param string|null $typeUsage
     * @return Collection
     */
    public function getByArticle(Article $article, ?string $typeUsage = null): Collection
    {
        $query = ArticleMedia::with('media')
            ->where('article_id', $article->id)
            ->where('est_actif', true);

        if ($typeUsage) {
            $query->where('type_usage', $typeUsage);
        }

        return $query->orderBy('ordre_affichage')->get();
    }

    /**
     * Récupère l'image de couverture d'un article
     *
     * @param Article $article
     * @return ArticleMedia|null
     */
    public function getCoverImage(Article $article): ?ArticleMedia
    {
        return ArticleMedia::with('media')
            ->where('article_id', $article->id)
            ->where('type_usage', 'couverture')
            ->first();
    }

    /**
     * Active ou désactive un média dans un article
     *
     * @param Article $article
     * @param Media $media
     * @param bool $actif
     * @return bool
     */
    public function setActive(Article $article, Media $media, bool $actif): bool
    {
        /** @var ArticleMedia|null $articleMedia */
        $articleMedia = ArticleMedia::where('article_id', $article->id)
            ->where('media_id', $media->id)
            ->first();

        if (!$articleMedia) {
            throw new \DomainException('Ce média n\'est pas associé à cet article');
        }

        $articleMedia->est_actif = $actif;

        return (bool) $articleMedia->save();
    }
}
