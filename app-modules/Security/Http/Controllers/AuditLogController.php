<?php

declare(strict_types=1);

namespace Modules\Security\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Security\Http\Resources\AuditLogResource;
use Modules\Security\Models\AuditLog;

/**
 * Contrôleur des logs d'audit
 *
 * Permet la consultation des journaux d'audit.
 */
class AuditLogController extends Controller implements HasMiddleware
{
    /**
     * Middleware pour le contrôleur
     */
    public static function middleware(): array
    {
        return [
            'auth:sanctum',
            new Middleware('permission:audit.voir', only: ['index', 'show']),
        ];
    }

    /**
     * Constructeur
     */
    public function __construct()
    {
        //
    }

    /**
     * Liste des logs d'audit
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $user = request()->user();
        $query = AuditLog::with('utilisateur');

        // Filtrage par maison d'édition
        if (!$user->hasRole(['super_admin', 'admin_plateforme'])) {
            $query->where('maison_id', tenant('id'));
        }

        // Filtres
        if (request()->has('module')) {
            $query->where('module', request()->module);
        }

        if (request()->has('action')) {
            $query->where('action', request()->action);
        }

        if (request()->has('niveau')) {
            $query->where('niveau', request()->niveau);
        }

        if (request()->has('utilisateur_id')) {
            $query->where('utilisateur_id', request()->utilisateur_id);
        }

        if (request()->has('date_debut')) {
            $query->where('created_at', '>=', request()->date_debut);
        }

        if (request()->has('date_fin')) {
            $query->where('created_at', '<=', request()->date_fin);
        }

        if (request()->has('search')) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                  ->orWhere('utilisateur_nom', 'LIKE', "%{$search}%")
                  ->orWhere('ip_address', 'LIKE', "%{$search}%");
            });
        }

        // Tri
        $orderBy = request()->input('order_by', 'created_at');
        $orderDir = request()->input('order_dir', 'desc');
        $query->orderBy($orderBy, $orderDir);

        $perPage = min(request()->input('per_page', 50), 200);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => AuditLogResource::collection($logs),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Affiche un log d'audit spécifique
     *
     * @param AuditLog $auditLog
     * @return JsonResponse
     */
    public function show(AuditLog $auditLog): JsonResponse
    {
        $user = request()->user();

        // Vérification d'accès
        if (!$user->hasRole(['super_admin', 'admin_plateforme'])) {
            if ($auditLog->maison_id !== tenant('id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à voir ce log.',
                ], 403);
            }
        }

        $auditLog->load('utilisateur', 'maison');

        return response()->json([
            'success' => true,
            'data' => new AuditLogResource($auditLog),
        ]);
    }

    /**
     * Statistiques des logs d'audit
     *
     * @return JsonResponse
     */
    public function statistiques(): JsonResponse
    {
        $user = request()->user();
        $query = AuditLog::query();

        if (!$user->hasRole(['super_admin', 'admin_plateforme'])) {
            $query->where('maison_id', tenant('id'));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total' => (clone $query)->count(),
                'par_module' => (clone $query)->selectRaw('module, count(*) as total')->groupBy('module')->get(),
                'par_action' => (clone $query)->selectRaw('action, count(*) as total')->groupBy('action')->get(),
                'par_niveau' => (clone $query)->selectRaw('niveau, count(*) as total')->groupBy('niveau')->get(),
                'par_jour' => (clone $query)->selectRaw('DATE(created_at) as date, count(*) as total')->groupBy('date')->orderBy('date', 'desc')->limit(30)->get(),
            ],
        ]);
    }
}
