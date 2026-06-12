<?php

declare(strict_types=1);

namespace Modules\Security\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Security\Models\AuditLog;
use Modules\Security\Services\SecurityService;

/**
 * Contrôleur d'audit pour l'administration
 *
 * Permet aux administrateurs de consulter les logs avancés.
 */
class AdminAuditController extends Controller implements HasMiddleware
{
    /**
     * Middleware pour le contrôleur
     */
    public static function middleware(): array
    {
        return [
            'auth:sanctum',
            new Middleware('permission:admin.audit'),
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
     * Logs d'audit pour l'administration
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $query = AuditLog::with(['utilisateur', 'maison']);

        // Filtres avancés
        if (request()->has('maison_id')) {
            $query->where('maison_id', request()->maison_id);
        }

        if (request()->has('contexte')) {
            $query->where('contexte', request()->contexte);
        }

        // (autres filtres similaires au contrôleur normal)

        $perPage = min(request()->input('per_page', 100), 500);
        $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $logs,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * Logs critiques
     *
     * @return JsonResponse
     */
    public function critical(): JsonResponse
    {
        $logs = AuditLog::critiques()
            ->with(['utilisateur', 'maison'])
            ->orderBy('created_at', 'desc')
            ->paginate(request()->input('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Nettoyage des vieux logs
     *
     * @return JsonResponse
     */
    public function clean(): JsonResponse
    {
        $days = request()->input('days', config('security.logging.retention_days', 90));

        $deleted = AuditLog::where('created_at', '<', now()->subDays($days))->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} log(s) supprimé(s)",
        ]);
    }
}
