<?php

declare(strict_types=1);

namespace Modules\Security\Traits;

use Modules\Security\Services\SecurityService;

/**
 * Trait HasSecurityEvents
 *
 * Ajoute des événements de sécurité aux modèles User.
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait HasSecurityEvents
{
    /**
     * Vérifie si l'utilisateur a une session active sur un autre appareil
     *
     * @param string|null $currentSessionId
     * @return bool
     */
    public function hasOtherActiveSessions(?string $currentSessionId = null): bool
    {
        $securityService = app(SecurityService::class);
        $sessions = $securityService->getUserActiveSessions($this);

        if ($currentSessionId) {
            $sessions = $sessions->where('session_id', '!=', $currentSessionId);
        }

        return $sessions->count() > 0;
    }

    /**
     * Termine toutes les autres sessions de l'utilisateur
     *
     * @param string|null $keepSessionId
     * @return int
     */
    public function terminateOtherSessions(?string $keepSessionId = null): int
    {
        $securityService = app(SecurityService::class);

        return $securityService->terminateOtherSessions($this, $keepSessionId);
    }

    /**
     * Enregistre une connexion réussie
     *
     * @param bool $succes
     * @param string|null $email
     * @param string|null $message
     * @return void
     */
    public function recordLoginAttempt(bool $succes, ?string $email = null, ?string $message = null): void
    {
        $auditService = app(\Modules\Security\Services\AuditService::class);
        $auditService->logLogin($succes ? $this : null, $succes, $email ?? $this->email, $message);
    }

    /**
     * Vérifie si le mot de passe doit être changé
     *
     * @param int $days
     * @return bool
     */
    public function shouldChangePassword(int $days = 90): bool
    {
        if (!$this->password_updated_at) {
            return true;
        }

        return $this->password_updated_at->diffInDays(now()) >= $days;
    }

    /**
     * Met à jour la date du dernier changement de mot de passe
     *
     * @return void
     */
    public function updatePasswordChangeDate(): void
    {
        $this->password_updated_at = now();
        $this->saveQuietly();
    }
}
