<?php

declare(strict_types=1);

namespace Modules\Security\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Security\Models\AuditLog;
use Modules\Security\Models\FailedLoginAttempt;
use Modules\Security\Models\LoginHistory;
use Modules\Security\Models\UserSession;

/**
 * Listener CleanOldLogs
 *
 * Nettoie périodiquement les anciens logs et données de sécurité.
 */
class CleanOldLogs
{
    /**
     * Handle the event (peut être appelé par un scheduler)
     */
    public function handle(): void
    {
        $retentionDays = config('security.logging.retention_days', 90);
        $cutoffDate = now()->subDays($retentionDays);

        // Nettoie les logs d'audit
        $auditDeleted = AuditLog::where('created_at', '<', $cutoffDate)->delete();

        // Nettoie les historiques de connexion (conserve plus longtemps)
        $loginHistoryRetention = $retentionDays * 2;
        $loginHistoryCutoff = now()->subDays($loginHistoryRetention);
        $loginHistoryDeleted = LoginHistory::where('created_at', '<', $loginHistoryCutoff)->delete();

        // Nettoie les tentatives échouées (conserve 30 jours)
        $failedAttemptsDeleted = FailedLoginAttempt::nettoyerAnciennes(30);

        // Nettoie les sessions expirées
        $expiredSessionsDeleted = UserSession::where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('expires_at')
                  ->where('expires_at', '<', now());
            })
            ->update([
                'is_active' => false,
                'logged_out_at' => now(),
            ]);

        Log::channel('security')->info('Nettoyage automatique des logs effectué', [
            'audit_logs_deleted' => $auditDeleted,
            'login_histories_deleted' => $loginHistoryDeleted,
            'failed_attempts_deleted' => $failedAttemptsDeleted,
            'expired_sessions_terminated' => $expiredSessionsDeleted,
            'retention_days' => $retentionDays,
        ]);
    }

    /**
     * Nettoie les logs plus anciens qu'une date spécifique
     *
     * @param \DateTimeInterface $date
     * @return array
     */
    public function cleanOlderThan(\DateTimeInterface $date): array
    {
        $auditDeleted = AuditLog::where('created_at', '<', $date)->delete();
        $loginHistoryDeleted = LoginHistory::where('created_at', '<', $date)->delete();

        return [
            'audit_logs' => $auditDeleted,
            'login_histories' => $loginHistoryDeleted,
        ];
    }

    /**
     * Nettoie les logs pour une maison d'édition spécifique
     *
     * @param string $maisonId
     * @param \DateTimeInterface|null $cutoffDate
     * @return array
     */
    public function cleanForMaison(string $maisonId, ?\DateTimeInterface $cutoffDate = null): array
    {
        $cutoffDate = $cutoffDate ?? now()->subDays(config('security.logging.retention_days', 90));

        $auditDeleted = AuditLog::where('maison_id', $maisonId)
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        return [
            'audit_logs' => $auditDeleted,
        ];
    }
}
