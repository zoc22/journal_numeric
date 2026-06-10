<?php

declare(strict_types=1);

namespace Modules\Media\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Media\Http\Requests\UploadMediaRequest;
use Modules\Media\Http\Requests\UpdateMediaRequest;
use Modules\Media\Http\Resources\MediaResource;
use Modules\Media\Models\Media;
use Modules\Media\Services\MediaService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur de gestion des médias
 *
 * Gère les opérations CRUD sur les médias :
 * - Upload de fichiers
 * - Consultation
 * - Mise à jour des métadonnées
 * - Suppression
 */
class MediaController extends Controller implements HasMiddleware
{
    /**
     * Configuration des middlewares
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware('permission:media.upload', only: ['store']),
            new Middleware('permission:media.supprimer', only: ['destroy']),
        ];
    }

    /**
     * Constructeur
     */
    public function __construct(
        protected MediaService $mediaService
    ) {}

    /**
     * Liste des médias
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $query = Media::with('televerseur')
            ->pourMaison(tenant('id'));

        // Filtre par type
        if (request()->has('type')) {
            $query->where('type', request()->type);
        }

        // Filtre par extension
        if (request()->has('extension')) {
            $query->where('extension', request()->extension);
        }

        // Recherche par nom
        if (request()->has('search')) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom_original', 'LIKE', "%{$search}%")
                  ->orWhere('nom_fichier', 'LIKE', "%{$search}%");
            });
        }

        // Tri
        $orderBy = request()->input('order_by', 'created_at');
        $orderDir = request()->input('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = (int) min(request()->input('per_page', 30), 100);
        $medias = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => MediaResource::collection($medias),
            'meta' => [
                'current_page' => $medias->currentPage(),
                'last_page' => $medias->lastPage(),
                'per_page' => $medias->perPage(),
                'total' => $medias->total(),
            ],
        ]);
    }

    /**
     * Upload d'un nouveau média
     *
     * @param UploadMediaRequest $request
     * @return JsonResponse
     */
    public function store(UploadMediaRequest $request): JsonResponse
    {
        $file = $request->file('file');

        $media = $this->mediaService->upload(
            $file,
            $request->user(),
            tenant('id'),
            [
                'is_public' => $request->input('is_public', true),
                'quality' => $request->input('quality'),
                'allow_duplicate' => $request->input('allow_duplicate', false),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Fichier téléversé avec succès',
            'data' => new MediaResource($media),
        ], Response::HTTP_CREATED);
    }

    /**
     * Affiche un média
     *
     * @param Media $medium
     * @return JsonResponse
     */
    public function show(Media $medium): JsonResponse
    {
        $medium->load('televerseur');

        return response()->json([
            'success' => true,
            'data' => new MediaResource($medium),
        ]);
    }

    /**
     * Met à jour un média
     *
     * @param UpdateMediaRequest $request
     * @param Media $medium
     * @return JsonResponse
     */
    public function update(UpdateMediaRequest $request, Media $medium): JsonResponse
    {
        $media = $this->mediaService->updateMetadata($medium, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Média mis à jour avec succès',
            'data' => new MediaResource($media),
        ]);
    }

    /**
     * Supprime un média
     *
     * @param Media $medium
     * @return JsonResponse
     */
    public function destroy(Media $medium): JsonResponse
    {
        try {
            $this->mediaService->delete($medium, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Média supprimé avec succès',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Statistiques des médias
     *
     * @return JsonResponse
     */
    public function statistiques(): JsonResponse
    {
        $statistiques = $this->mediaService->getStatistics(tenant('id'));

        return response()->json([
            'success' => true,
            'data' => $statistiques,
        ]);
    }
}
