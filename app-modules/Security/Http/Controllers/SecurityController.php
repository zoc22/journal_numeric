<?php

declare(strict_types=1);

namespace Modules\Security\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Security\Http\Resources\LoginHistoryResource;
use Modules\Security\Models\LoginHistory;
use Modules\Security\Services\SecurityService;

/**
 * Contrôleur de sécurité
 *
 * Gère les aspects de sécurité : sessions, historiques de connexion.
 */
class SecurityController extends Controller
{
    /**
     * @var SecurityService
     */
    protected SecurityService $securityService;

    /**
     * Constructeur
     */
    public function __construct(SecurityService $securityService)
    {
        $this->middleware('auth:sanctum');
        $this->securityService = $securityService;
    }

    /**
     * Historique des connexions de l'utilisateur
     *
     * @return JsonResponse
     */
    public function loginHistory(): JsonResponse
    {
        $user = request()->user();

        $history = LoginHistory::where('utilisateur_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(request()->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => LoginHistoryResource::collection($history),
        ]);
    }

    /**
     * Sessions actives de l'utilisateur
     *
     * @return JsonResponse
     */
    public function activeSessions(): JsonResponse
    {
        $user = request()->user();
        $sessions = $this->securityService->getUserActiveSessions($user);
        $currentSessionId = request()->session()->getId();

        return response()->json([
            'success' => true,
            'data' => $sessions->map(function ($session) use ($currentSessionId) {
                return [
                    'id' => $session->id,
                    'is_current' => $session->session_id === $currentSessionId,
                    'ip_address' => $session->ip_address,
                    'device_type' => $session->device_type,
                    'last_activity' => $session->last_activity->toISOString(),
                    'created_at' => $session->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Termine toutes les autres sessions
     *
     * @return JsonResponse
     */
    public function terminateOtherSessions(): JsonResponse
    {
        $user = request()->user();
        $currentSessionId = request()->session()->getId();

        $count = $this->securityService->terminateOtherSessions($user, $currentSessionId);

        return response()->json([
            'success' => true,
            'message' => "{$count} session(s) terminée(s)",
        ]);
    }

    /**
     * Termine une session spécifique
     *
     * @param string $sessionId
     * @return JsonResponse
     */
    public function terminateSession(string $sessionId): JsonResponse
    {
        $user = request()->user();
        $currentSessionId = request()->session()->getId();

        if ($sessionId === $currentSessionId) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas terminer votre session actuelle via cette action.',
            ], 422);
        }

        $session = \Modules\Security\Models\UserSession::where('utilisateur_id', $user->id)
            ->where('session_id', $sessionId)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Session non trouvée.',
            ], 404);
        }

        $session->terminer();

        return response()->json([
            'success' => true,
            'message' => 'Session terminée avec succès',
        ]);
    }

    /**
     * Statistiques de sécurité
     *
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        $user = request()->user();
        $maisonId = $user->hasRole(['super_admin', 'admin_plateforme']) ? null : tenant('id');

        $stats = $this->securityService->getSecurityStats($maisonId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
