<?php

declare(strict_types=1);

namespace Modules\Article\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Article\Http\Requests\StoreArticleRequest;
use Modules\Article\Http\Requests\UpdateArticleRequest;
use Modules\Article\Http\Resources\ArticleResource;
use Modules\Article\Http\Resources\ArticleVersionResource;
use Modules\Article\Models\Article;
use Modules\Article\Services\ArticleService;
use Modules\Article\Services\CategoryService;
use Modules\Workflow\Services\WorkflowService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Contrôleur de gestion des articles
 *
 * Gère toutes les opérations CRUD sur les articles ainsi que
 * les actions spécifiques du workflow éditorial.
 */
class ArticleController extends Controller implements HasMiddleware
{
    /**
     * Constructeur
     */
    public function __construct(
        protected ArticleService $articleService,
        protected WorkflowService $workflowService,
        protected CategoryService $categoryService
    ) {}

    /**
     * Configuration des middlewares
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware('permission:article.creer', only: ['store']),
            new Middleware('permission:article.modifier', only: ['update', 'restaurerVersion']),
            new Middleware('permission:article.supprimer', only: ['destroy']),
        ];
    }

    /**
     * Liste des articles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Article::query()->with(['auteur', 'categories']);

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Filtre par catégorie
        if ($request->filled('categorie_id')) {
            $query->whereHas('categories', fn($q) => $q->where('categories.id', $request->categorie_id));
        }

        // Filtre par auteur
        if ($request->filled('auteur_id')) {
            $query->where('auteur_id', $request->auteur_id);
        }

        // Filtres de localisation
        if ($request->filled('continent')) {
            $query->where('continent', $request->continent);
        }
        if ($request->filled('pays')) {
            $query->where('pays', $request->pays);
        }
        if ($request->filled('ville')) {
            $query->where('ville', $request->ville);
        }

        // Filtre par maison d'édition
        $user = $request->user();
        if ($user && !$user->hasRole(['super_admin', 'admin_plateforme'])) {
            $query->where('maison_id', tenant('id'));
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'LIKE', "%{$search}%")
                  ->orWhere('contenu', 'LIKE', "%{$search}%");
            });
        }

        // Tri
        $orderBy = $request->input('order_by', 'created_at');
        $orderDir = $request->input('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = (int) min($request->input('per_page', 15), 100);
        $articles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => ArticleResource::collection($articles),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ],
        ]);
    }

    /**
     * Crée un nouvel article
     *
     * @param StoreArticleRequest $request
     * @return JsonResponse
     */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['auteur_id'] = $request->user()->id;
        $data['maison_id'] = tenant('id');
        $data['slug'] = Article::genererSlug($data['titre']);
        $data['temps_lecture'] = Article::calculerTempsLecture($data['contenu'] ?? null);

        // Récupère la localisation de l'utilisateur si non fournie
        if (empty($data['continent'])) $data['continent'] = $request->user()->continent;
        if (empty($data['pays'])) $data['pays'] = $request->user()->pays;
        if (empty($data['ville'])) $data['ville'] = $request->user()->ville;

        $article = $this->articleService->creer($data);

        if ($request->has('categories')) {
            $article->categories()->sync($request->categories);
        }

        return response()->json([
            'success' => true,
            'message' => 'Article créé avec succès',
            'data' => new ArticleResource($article->load(['auteur', 'categories'])),
        ], Response::HTTP_CREATED);
    }

    /**
     * Affiche un article
     *
     * @param Article $article
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Article $article, Request $request): JsonResponse
    {
        // Incrémente les vues si l'article est publié
        if ($article->estPublie() && !$request->user()) {
            $article->incrementVues();
        }

        $article->load(['auteur', 'categories', 'tags', 'versions.createur']);

        return response()->json([
            'success' => true,
            'data' => new ArticleResource($article),
        ]);
    }

    /**
     * Met à jour un article
     *
     * @param UpdateArticleRequest $request
     * @param Article $article
     * @return JsonResponse
     */
    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        if (!$article->estModifiable()) {
            return response()->json([
                'success' => false,
                'message' => 'Cet article ne peut pas être modifié dans son état actuel.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $data = $request->validated();

        if (isset($data['contenu'])) {
            $data['temps_lecture'] = Article::calculerTempsLecture($data['contenu']);
        }

        $article = $this->articleService->mettreAJour($article, $data);

        if ($request->has('categories')) {
            $article->categories()->sync($request->categories);
        }

        return response()->json([
            'success' => true,
            'message' => 'Article mis à jour avec succès',
            'data' => new ArticleResource($article->fresh(['auteur', 'categories'])),
        ]);
    }

    /**
     * Soumet un article pour relecture
     *
     * @param Request $request
     * @param Article $article
     * @return JsonResponse
     */
    public function submit(Request $request, Article $article): JsonResponse
    {
        try {
            $transition = $this->workflowService->soumettreArticle(
                $article,
                $request->user(),
                $request->input('note_submission')
            );

            return response()->json([
                'success' => true,
                'message' => 'Article soumis avec succès pour relecture',
                'data' => [
                    'article' => new ArticleResource($article->fresh(['auteur'])),
                    'transition' => $transition,
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Supprime un article
     *
     * @param Article $article
     * @return JsonResponse
     */
    public function destroy(Article $article): JsonResponse
    {
        if (!$article->estBrouillon() && $article->statut !== 'rejete') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les brouillons ou articles rejetés peuvent être supprimés.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->articleService->supprimer($article);

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé avec succès',
        ]);
    }

    /**
     * Liste des versions d'un article
     *
     * @param Article $article
     * @return JsonResponse
     */
    public function versions(Article $article): JsonResponse
    {
        $versions = $article->versions()->with('createur')->get();

        return response()->json([
            'success' => true,
            'data' => ArticleVersionResource::collection($versions),
        ]);
    }

    /**
     * Restaure une version antérieure
     *
     * @param Request $request
     * @param Article $article
     * @param string $versionId
     * @return JsonResponse
     */
    public function restaurerVersion(Request $request, Article $article, string $versionId): JsonResponse
    {
        if (!$article->estModifiable()) {
            return response()->json([
                'success' => false,
                'message' => 'Cet article ne peut pas être restauré dans son état actuel.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $version = $article->versions()->findOrFail($versionId);

        $article = $this->articleService->restaurerVersion($article, $version, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Version restaurée avec succès',
            'data' => new ArticleResource($article),
        ]);
    }

    /**
     * Statistiques d'un article
     *
     * @param Article $article
     * @return JsonResponse
     */
    public function statistiques(Article $article): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'vues' => $article->nb_vues,
                'partages' => $article->nb_partages,
                'likes' => $article->nb_likes,
                'commentaires' => $article->nb_commentaires,
                'temps_lecture' => $article->temps_lecture,
                'version_actuelle' => $article->version_numero,
                'date_publication' => $article->publie_le instanceof \Carbon\Carbon ? $article->publie_le->toISOString() : $article->publie_le,
                'auteur' => $article->auteur?->only(['id', 'nom']),
            ],
        ]);
    }
}
