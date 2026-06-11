<?php

declare(strict_types=1);

namespace Modules\Security\Services;

use Illuminate\Http\Request;
use Modules\Security\Models\UserSession;

/**
 * Service de suivi des sessions
 *
 * Gère le suivi des sessions utilisateur pour la sécurité.
 */
class SessionTrackingService
{
    /**
     * Enregistre une nouvelle session
     *
     * @param Request $request
     * @param string $sessionId
     * @param string|null $token
     * @return UserSession|null
     */
    public function trackSession(Request $request, string $sessionId, ?string $token): ?UserSession
    {
        if (!config('security.sessions.track_enabled', true)) {
            return null;
        }

        $user = $request->user();

        if (!$user) {
            return null;
        }

        $securityService = app(SecurityService::class);

        return $securityService->createSession(
            $user,
            $sessionId,
            $token,
            $request->ip(),
            $request->userAgent()
        );
    }

    /**
     * Met à jour l'activité d'une session
     *
     * @param string $sessionId
     * @return void
     */
    public function updateActivity(string $sessionId): void
    {
        if (!config('security.sessions.track_enabled', true)) {
            return;
        }

        $securityService = app(SecurityService::class);
        $securityService->updateSessionActivity($sessionId);
    }

    /**
     * Termine une session
     *
     * @param string $sessionId
     * @return void
     */
    public function endSession(string $sessionId): void
    {
        if (!config('security.sessions.track_enabled', true)) {
            return;
        }

        $securityService = app(SecurityService::class);
        $securityService->terminateSession($sessionId);
    }

    /**
     * Termine toutes les sessions d'un utilisateur
     *
     * @param string $userId
     * @param string|null $exceptSessionId
     * @return int
     */
    public function endAllUserSessions(string $userId, ?string $exceptSessionId = null): int
    {
        if (!config('security.sessions.track_enabled', true)) {
            return 0;
        }

        $user = \Modules\User\Models\User::find($userId);

        if (!$user) {
            return 0;
        }

        $securityService = app(SecurityService::class);

        return $securityService->terminateOtherSessions($user, $exceptSessionId);
    }
}
