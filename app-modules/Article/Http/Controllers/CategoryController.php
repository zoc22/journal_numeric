<?php

declare(strict_types=1);

namespace Modules\Article\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Modules\Article\Http\Requests\CategoryRequest;
use Modules\Article\Http\Resources\CategoryResource;
use Modules\Article\Models\Category;
use Modules\Article\Services\CategoryService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Contrôleur de gestion des catégories
 *
 * Gère les opérations CRUD sur les catégories ainsi que
 * la gestion de l'arborescence.
 */
class CategoryController extends Controller implements HasMiddleware
{
    /**
     * Constructeur
     */
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Configuration des middlewares
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware('permission:categories.gerer', except: ['index', 'show', 'arborescence']),
        ];
    }

    /**
     * Liste des catégories
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $categories = Category::with(['parent'])
            ->where('maison_id', tenant('id'))
            ->orderBy('ordre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Arborescence des catégories
     *
     * @return JsonResponse
     */
    public function arborescence(): JsonResponse
    {
        $arborescence = $this->categoryService->getArborescence(tenant('id'));

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection($arborescence),
        ]);
    }

    /**
     * Crée une nouvelle catégorie
     *
     * @param CategoryRequest $request
     * @return JsonResponse
     */
    public function store(CategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Category::genererSlug($data['nom']);
        $data['maison_id'] = tenant('id');

        $category = $this->categoryService->creer($data);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie créée avec succès',
            'data' => new CategoryResource($category),
        ], Response::HTTP_CREATED);
    }

    /**
     * Affiche une catégorie
     *
     * @param Category $category
     * @return JsonResponse
     */
    public function show(Category $category): JsonResponse
    {
        $category->load(['parent', 'enfants']);

        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Met à jour une catégorie
     *
     * @param CategoryRequest $request
     * @param Category $category
     * @return JsonResponse
     */
    public function update(CategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        $category = $this->categoryService->mettreAJour($category, $data);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie mise à jour avec succès',
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Supprime une catégorie
     *
     * @param Category $category
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Category $category, Request $request): JsonResponse
    {
        $reassignChildren = $request->input('reassign_children', false);
        $newParentId = $request->input('new_parent_id');

        $this->categoryService->supprimer($category, (bool)$reassignChildren, $newParentId);

        return response()->json([
            'success' => true,
            'message' => 'Catégorie supprimée avec succès',
        ]);
    }
}
