<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Article\Models\Article;
use Modules\Media\Http\Resources\ArticleMediaResource;
use Modules\Media\Models\Media;
use Modules\Media\Services\ArticleMediaService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur de gestion des associations article-média
 *
 * Gère l'attachement et l'organisation des médias dans les articles.
 */
class ArticleMediaController extends Controller implements HasMiddleware
{
    /**
     * Configuration des middlewares
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
        ];
    }

    /**
     * Constructeur
     */
    public function __construct(
        protected ArticleMediaService $articleMediaService
    ) {}

    /**
     * Liste des médias d'un article
     *
     * @param Article $article
     * @return JsonResponse
     */
    public function index(Article $article): JsonResponse
    {
        $typeUsage = request()->input('type_usage');
        $medias = $this->articleMediaService->getByArticle($article, $typeUsage);

        return response()->json([
            'success' => true,
            'data' => ArticleMediaResource::collection($medias),
        ]);
    }

    /**
     * Attache un média à un article
     *
     * @param Article $article
     * @param Media $medium
     * @return JsonResponse
     */
    public function attach(Article $article, Media $medium): JsonResponse
    {
        $typeUsage = request()->input('type_usage', 'inline');
        $metadonnees = request()->input('metadonnees', []);
        $ordre = request()->input('ordre_affichage');

        try {
            $articleMedia = $this->articleMediaService->attach(
                $article,
                $medium,
                $typeUsage,
                $metadonnees,
                $ordre
            );

            return response()->json([
                'success' => true,
                'message' => 'Média attaché à l\'article avec succès',
                'data' => new ArticleMediaResource($articleMedia->load('media')),
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Détache un média d'un article
     *
     * @param Article $article
     * @param Media $medium
     * @return JsonResponse
     */
    public function detach(Article $article, Media $medium): JsonResponse
    {
        try {
            $this->articleMediaService->detach($article, $medium);

            return response()->json([
                'success' => true,
                'message' => 'Média détaché de l\'article avec succès',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Définit l'image de couverture d'un article
     *
     * @param Article $article
     * @param Media $medium
     * @return JsonResponse
     */
    public function setCover(Article $article, Media $medium): JsonResponse
    {
        $metadonnees = request()->input('metadonnees', []);

        $articleMedia = $this->articleMediaService->setCoverImage($article, $medium, $metadonnees);

        return response()->json([
            'success' => true,
            'message' => 'Image de couverture définie avec succès',
            'data' => new ArticleMediaResource($articleMedia->load('media')),
        ]);
    }

    /**
     * Met à jour l'ordre des médias
     *
     * @param Article $article
     * @return JsonResponse
     */
    public function updateOrder(Article $article): JsonResponse
    {
        $ordreMediaIds = request()->input('ordre', []);
        $typeUsage = request()->input('type_usage', 'inline');

        $this->articleMediaService->updateOrder($article, $ordreMediaIds, $typeUsage);

        return response()->json([
            'success' => true,
            'message' => 'Ordre des médias mis à jour avec succès',
        ]);
    }

    /**
     * Active/désactive un média dans un article
     *
     * @param Article $article
     * @param Media $medium
     * @return JsonResponse
     */
    public function setActive(Article $article, Media $medium): JsonResponse
    {
        $actif = request()->input('actif', true);

        $this->articleMediaService->setActive($article, $medium, (bool) $actif);

        return response()->json([
            'success' => true,
            'message' => $actif ? 'Média activé' : 'Média désactivé',
        ]);
    }
}
