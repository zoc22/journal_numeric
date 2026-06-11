<?php

declare(strict_types=1);

namespace Modules\Security\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Security\Models\FailedLoginAttempt;
use Modules\Security\Models\LoginHistory;
use Modules\Security\Models\UserSession;
use Modules\User\Models\User;

/**
 * Service de sécurité
 *
 * Gère la sécurité de la plateforme : rate limiting,
 * détection d'anomalies, gestion des sessions.
 */
class SecurityService
{
    /**
     * Vérifie si une IP est bloquée
     *
     * @param string $ip
     * @return bool
     */
    public function isIpBlocked(string $ip): bool
    {
        $cacheKey = "blocked_ip:{$ip}";
        return Cache::has($cacheKey);
    }

    /**
     * Bloque une IP temporairement
     *
     * @param string $ip
     * @param int $minutes
     * @return void
     */
    public function blockIp(string $ip, int $minutes = 60): void
    {
        $cacheKey = "blocked_ip:{$ip}";
        Cache::put($cacheKey, true, now()->addMinutes($minutes));
    }

    /**
     * Vérifie si une adresse IP a trop de tentatives échouées
     *
     * @param string $ip
     * @return bool
     */
    public function hasTooManyFailedAttempts(string $ip): bool
    {
        $maxAttempts = config('security.security.max_login_attempts', 5);
        $attempts = FailedLoginAttempt::pourIP($ip, 15)->count();

        return $attempts >= $maxAttempts;
    }

    /**
     * Récupère le nombre de tentatives échouées restantes
     *
     * @param string $ip
     * @return int
     */
    public function getRemainingAttempts(string $ip): int
    {
        $maxAttempts = config('security.security.max_login_attempts', 5);
        $attempts = FailedLoginAttempt::pourIP($ip, 15)->count();

        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Nettoie les anciennes tentatives échouées
     *
     * @return int
     */
    public function cleanFailedAttempts(): int
    {
        return FailedLoginAttempt::nettoyerAnciennes(30);
    }

    /**
     * Récupère l'activité suspecte pour une IP
     *
     * @param string $ip
     * @return array
     */
    public function getSuspiciousActivity(string $ip): array
    {
        return [
            'failed_logins_last_15min' => FailedLoginAttempt::pourIP($ip, 15)->count(),
            'failed_logins_last_hour' => FailedLoginAttempt::pourIP($ip, 60)->count(),
            'failed_logins_last_day' => FailedLoginAttempt::pourIP($ip, 1440)->count(),
            'is_blocked' => $this->isIpBlocked($ip),
        ];
    }

    /**
     * Récupère les sessions actives d'un utilisateur
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserActiveSessions(User $user)
    {
        return UserSession::pourUtilisateur($user->id)->actives()->get();
    }

    /**
     * Termine toutes les sessions d'un utilisateur sauf une
     *
     * @param User $user
     * @param string|null $keepSessionId
     * @return int
     */
    public function terminateOtherSessions(User $user, ?string $keepSessionId = null): int
    {
        $query = UserSession::where('utilisateur_id', $user->id)
            ->where('is_active', true);

        if ($keepSessionId) {
            $query->where('session_id', '!=', $keepSessionId);
        }

        $count = $query->count();

        $query->update([
            'is_active' => false,
            'logged_out_at' => now(),
        ]);

        return $count;
    }

    /**
     * Crée une nouvelle session utilisateur
     *
     * @param User $user
     * @param string $sessionId
     * @param string|null $token
     * @param string $ip
     * @param string|null $userAgent
     * @return UserSession
     */
    public function createSession(User $user, string $sessionId, ?string $token, string $ip, ?string $userAgent): UserSession
    {
        // Vérifie le nombre maximum de sessions simultanées
        $maxSessions = config('security.sessions.max_concurrent_sessions', 5);
        $activeSessions = UserSession::pourUtilisateur($user->id)->actives()->count();

        if ($activeSessions >= $maxSessions) {
            $this->terminateOtherSessions($user, null);
        }

        return UserSession::create([
            'utilisateur_id' => $user->id,
            'session_id' => $sessionId,
            'token' => $token,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'continent' => $user->continent,
            'pays' => $user->pays,
            'ville' => $user->ville,
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(config('security.sessions.session_timeout_minutes', 120)),
            'is_active' => true,
        ]);
    }

    /**
     * Met à jour l'activité d'une session
     *
     * @param string $sessionId
     * @return bool
     */
    public function updateSessionActivity(string $sessionId): bool
    {
        $session = UserSession::where('session_id', $sessionId)->first();

        if (!$session) {
            return false;
        }

        $session->last_activity = now();

        return $session->save();
    }

    /**
     * Termine une session
     *
     * @param string $sessionId
     * @return bool
     */
    public function terminateSession(string $sessionId): bool
    {
        $session = UserSession::where('session_id', $sessionId)->first();

        if (!$session) {
            return false;
        }

        return $session->terminer();
    }

    /**
     * Nettoie les sessions expirées
     *
     * @return int
     */
    public function cleanExpiredSessions(): int
    {
        return UserSession::where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('expires_at')
                  ->where('expires_at', '<', now());
            })
            ->update([
                'is_active' => false,
                'logged_out_at' => now(),
            ]);
    }

    /**
     * Vérifie si un mot de passe est assez fort
     *
     * @param string $password
     * @return bool
     */
    public function isStrongPassword(string $password): bool
    {
        $minLength = config('security.security.password_min_length', 8);

        if (strlen($password) < $minLength) {
            return false;
        }

        // Au moins une majuscule
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Au moins un chiffre
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Au moins un caractère spécial
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Récupère les statistiques de sécurité
     *
     * @param string|null $maisonId
     * @return array
     */
    public function getSecurityStats(?string $maisonId = null): array
    {
        $queryLoginHistory = LoginHistory::query();
        $queryAuditLog = \Modules\Security\Models\AuditLog::query();

        if ($maisonId) {
            $queryLoginHistory->where('maison_id', $maisonId);
            $queryAuditLog->where('maison_id', $maisonId);
        }

        return [
            'total_logins_today' => (clone $queryLoginHistory)->whereDate('created_at', today())->count(),
            'successful_logins_today' => (clone $queryLoginHistory)->whereDate('created_at', today())->where('succes', true)->count(),
            'failed_logins_today' => (clone $queryLoginHistory)->whereDate('created_at', today())->where('succes', false)->count(),
            'unique_ips_today' => (clone $queryLoginHistory)->whereDate('created_at', today())->distinct('ip_address')->count('ip_address'),
            'critical_actions_week' => (clone $queryAuditLog)->where('niveau', 'critical')->where('created_at', '>=', now()->subDays(7))->count(),
            'active_sessions' => UserSession::actives()->count(),
        ];
    }
}
