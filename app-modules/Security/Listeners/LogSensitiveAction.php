<?php

declare(strict_types=1);

namespace Modules\Security\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Security\Events\SensitiveActionPerformed;

/**
 * Listener LogSensitiveAction
 *
 * Enregistre les actions sensibles dans les logs système.
 */
class LogSensitiveAction
{
    /**
     * Handle the event.
     */
    public function handle(SensitiveActionPerformed $event): void
    {
        $auditLog = $event->auditLog;

        Log::channel('security')->warning('Action sensible détectée', [
            'audit_log_id' => $auditLog->id,
            'action' => $auditLog->action,
            'module' => $auditLog->module,
            'utilisateur_id' => $auditLog->utilisateur_id,
            'utilisateur_nom' => $auditLog->utilisateur_nom,
            'ip_address' => $auditLog->ip_address,
            'niveau' => $auditLog->niveau,
            'description' => $auditLog->description,
            'created_at' => $auditLog->created_at->toISOString(),
        ]);

        // Si l'action est critique, log également dans le channel critical
        if ($auditLog->niveau === 'critical') {
            Log::channel('critical')->critical('Action critique', [
                'audit_log_id' => $auditLog->id,
                'action' => $auditLog->action,
                'module' => $auditLog->module,
                'utilisateur_id' => $auditLog->utilisateur_id,
                'description' => $auditLog->description,
            ]);
        }
    }
}
