<?php

declare(strict_types=1);

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Recruitment\Enums\CallStatus;
use Modules\Recruitment\Http\Requests\StoreCallRequest;
use Modules\Recruitment\Http\Requests\UpdateCallRequest;
use Modules\Recruitment\Http\Resources\CallForApplicationResource;
use Modules\Recruitment\Models\CallForApplication;
use Modules\Recruitment\Services\CallForApplicationService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôleur de gestion des appels à candidatures
 *
 * Gère les opérations CRUD sur les appels à candidatures
 * ainsi que leur publication et fermeture.
 */
class CallForApplicationController extends Controller implements HasMiddleware
{
    /**
     * Configuration des middlewares
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth:sanctum'),
            new Middleware('permission:recruitment.creer_appel', only: ['store']),
            new Middleware('permission:recruitment.modifier_appel', only: ['update']),
            new Middleware('permission:recruitment.supprimer_appel', only: ['destroy']),
            new Middleware('permission:recruitment.publier_appel', only: ['publish']),
        ];
    }

    /**
     * Constructeur
     */
    public function __construct(
        protected CallForApplicationService $callService
    ) {}

    /**
     * Liste des appels à candidatures
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = request()->user();
        $query = CallForApplication::query();

        // Filtrage par statut
        if (request()->has('statut')) {
            $query->where('statut', request()->input('statut'));
        }

        // Si l'utilisateur n'est pas admin, filtre par maison et statut public
        if (!$user->hasRole(['super_admin', 'admin_plateforme'])) {
            $query->where('maison_id', tenant('id'));

            // Pour les non-authentifiés ou lecteurs, ne montrer que les appels ouverts
            if (!$user->hasRole(['editeur_associe', 'editeur_chef', 'directeur_collection'])) {
                $query->where('statut', CallStatus::OUVERT->value);
            }
        }

        // Recherche
        if (request()->has('search')) {
            $search = request()->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Tri
        $orderBy = request()->input('order_by', 'created_at');
        $orderDir = request()->input('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = (int) min(request()->input('per_page', 15), 100);
        $calls = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => CallForApplicationResource::collection($calls),
            'meta' => [
                'current_page' => $calls->currentPage(),
                'last_page' => $calls->lastPage(),
                'per_page' => $calls->perPage(),
                'total' => $calls->total(),
            ],
        ]);
    }

    /**
     * Crée un nouvel appel à candidatures
     *
     * @param StoreCallRequest $request
     * @return JsonResponse
     */
    public function store(StoreCallRequest $request): JsonResponse
    {
        $call = $this->callService->creer(
            $request->validated(),
            $request->user(),
            tenant('id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Appel à candidatures créé avec succès',
            'data' => new CallForApplicationResource($call),
        ], Response::HTTP_CREATED);
    }

    /**
     * Affiche un appel à candidatures
     *
     * @param CallForApplication $call
     * @return JsonResponse
     */
    public function show(CallForApplication $call): JsonResponse
    {
        $user = request()->user();

        // Vérification d'accès
        if ($user && !$user->hasRole(['super_admin', 'admin_plateforme', 'editeur_chef', 'editeur_associe'])) {
            if ($call->statut !== CallStatus::OUVERT->value) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet appel n\'est pas accessible.',
                ], Response::HTTP_FORBIDDEN);
            }
        }

        $call->load(['createur', 'candidatures' => function ($q) use ($user) {
            if ($user && $user->hasRole(['editeur_chef', 'editeur_associe', 'admin_plateforme'])) {
                $q->with('candidat');
            }
        }]);

        return response()->json([
            'success' => true,
            'data' => new CallForApplicationResource($call),
        ]);
    }

    /**
     * Met à jour un appel à candidatures
     *
     * @param UpdateCallRequest $request
     * @param CallForApplication $call
     * @return JsonResponse
     */
    public function update(UpdateCallRequest $request, CallForApplication $call): JsonResponse
    {
        try {
            $call = $this->callService->mettreAJour($call, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Appel mis à jour avec succès',
                'data' => new CallForApplicationResource($call),
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Publie un appel à candidatures
     *
     * @param CallForApplication $call
     * @return JsonResponse
     */
    public function publish(CallForApplication $call): JsonResponse
    {
        try {
            $call = $this->callService->publier($call, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Appel publié avec succès',
                'data' => new CallForApplicationResource($call),
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Ferme un appel à candidatures
     *
     * @param CallForApplication $call
     * @return JsonResponse
     */
    public function close(CallForApplication $call): JsonResponse
    {
        try {
            $call = $this->callService->fermer($call, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Appel fermé avec succès',
                'data' => new CallForApplicationResource($call),
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Supprime un appel à candidatures
     *
     * @param CallForApplication $call
     * @return JsonResponse
     */
    public function destroy(CallForApplication $call): JsonResponse
    {
        try {
            $this->callService->supprimer($call, request()->user());

            return response()->json([
                'success' => true,
                'message' => 'Appel supprimé avec succès',
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
