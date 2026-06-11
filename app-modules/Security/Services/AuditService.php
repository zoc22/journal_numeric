<?php

declare(strict_types=1);

namespace Modules\Security\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Security\Models\AuditLog;
use Modules\User\Models\User;

/**
 * Service d'audit
 *
 * Centralise la journalisation des actions sensibles.
 */
class AuditService
{
    /**
     * Enregistre une action dans les logs d'audit
     *
     * @param string $action
     * @param string $module
     * @param array $data
     * @return AuditLog
     */
    public function log(string $action, string $module, array $data = []): AuditLog
    {
        if (!config('security.logging.enabled', true)) {
            // Audit log disabled
            return new AuditLog();
        }

        $user = Auth::user();
        $request = request();
        $location = $this->getLocationData($user);

        // Ne log que les actions sensibles si log_all_actions est false
        $sensitiveActions = config('security.logging.sensitive_actions', []);
        $logAllActions = config('security.logging.log_all_actions', false);

        if (!$logAllActions && !in_array($action, $sensitiveActions)) {
            // Action non sensible et log_all_actions désactivé
            return new AuditLog();
        }

        $auditLog = AuditLog::create([
            'maison_id' => $data['maison_id'] ?? $this->getCurrentTenantId(),
            'contexte' => $data['contexte'] ?? ($this->getCurrentTenantId() ? 'tenant' : 'platform'),
            'entite_type' => $data['entite_type'] ?? null,
            'entite_id' => $data['entite_id'] ?? null,
            'action' => $action,
            'module' => $module,
            'utilisateur_id' => $user?->id,
            'utilisateur_nom' => $user?->nom,
            'utilisateur_role' => $user?->roles->first()?->name,
            'anciennes_valeurs' => $data['anciennes_valeurs'] ?? null,
            'nouvelles_valeurs' => $data['nouvelles_valeurs'] ?? null,
            'champs_modifies' => $this->calculerChampsModifies($data),
            'metadonnees' => $data['metadonnees'] ?? null,
            'description' => $data['description'] ?? null,
            'ip_address' => $request?->ip() ?? 'CLI',
            'user_agent' => $request?->userAgent(),
            'continent' => $location['continent'] ?? null,
            'pays' => $location['pays'] ?? null,
            'ville' => $location['ville'] ?? null,
            'niveau' => $data['niveau'] ?? 'info',
        ]);

        // Déclenche un événement pour les actions critiques
        if ($auditLog->estCritique()) {
            event(new \Modules\Security\Events\SensitiveActionPerformed($auditLog));
        }

        return $auditLog;
    }

    /**
     * Enregistre une action de création
     *
     * @param string $module
     * @param string $entiteType
     * @param string $entiteId
     * @param array $valeurs
     * @param string|null $description
     * @return AuditLog
     */
    public function logCreation(string $module, string $entiteType, string $entiteId, array $valeurs, ?string $description = null): AuditLog
    {
        return $this->log('create', $module, [
            'entite_type' => $entiteType,
            'entite_id' => $entiteId,
            'nouvelles_valeurs' => $valeurs,
            'description' => $description ?? "Création d'un(e) {$entiteType}",
        ]);
    }

    /**
     * Enregistre une action de mise à jour
     *
     * @param string $module
     * @param string $entiteType
     * @param string $entiteId
     * @param array $anciennesValeurs
     * @param array $nouvellesValeurs
     * @param string|null $description
     * @return AuditLog
     */
    public function logUpdate(string $module, string $entiteType, string $entiteId, array $anciennesValeurs, array $nouvellesValeurs, ?string $description = null): AuditLog
    {
        return $this->log('update', $module, [
            'entite_type' => $entiteType,
            'entite_id' => $entiteId,
            'anciennes_valeurs' => $anciennesValeurs,
            'nouvelles_valeurs' => $nouvellesValeurs,
            'description' => $description ?? "Mise à jour d'un(e) {$entiteType}",
        ]);
    }

    /**
     * Enregistre une action de suppression
     *
     * @param string $module
     * @param string $entiteType
     * @param string $entiteId
     * @param array $valeurs
     * @param string|null $description
     * @return AuditLog
     */
    public function logDelete(string $module, string $entiteType, string $entiteId, array $valeurs, ?string $description = null): AuditLog
    {
        return $this->log('delete', $module, [
            'entite_type' => $entiteType,
            'entite_id' => $entiteId,
            'anciennes_valeurs' => $valeurs,
            'niveau' => 'warning',
            'description' => $description ?? "Suppression d'un(e) {$entiteType}",
        ]);
    }

    /**
     * Enregistre une action de connexion
     *
     * @param User|null $user
     * @param bool $succes
     * @param string|null $email
     * @param string|null $message
     * @return void
     */
    public function logLogin(?User $user, bool $succes, ?string $email = null, ?string $message = null): void
    {
        $request = request();
        $location = $this->getLocationData($user);

        \Modules\Security\Models\LoginHistory::create([
            'utilisateur_id' => $user?->id,
            'email' => $email ?? $user?->email,
            'succes' => $succes,
            'message_erreur' => $message,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $this->detectDeviceType($request->userAgent()),
            'browser' => $this->detectBrowser($request->userAgent()),
            'os' => $this->detectOS($request->userAgent()),
            'continent' => $location['continent'],
            'pays' => $location['pays'],
            'ville' => $location['ville'],
        ]);

        if (!$succes) {
            $this->logFailedLogin($email ?? $user?->email, $request->ip());
        } else {
            $this->log('login', 'auth', [
                'description' => "Connexion réussie de l'utilisateur {$user?->email}",
                'metadonnees' => ['ip' => $request->ip()],
            ]);
        }
    }

    /**
     * Enregistre une tentative de connexion échouée
     *
     * @param string|null $email
     * @param string $ip
     * @return void
     */
    protected function logFailedLogin(?string $email, string $ip): void
    {
        \Modules\Security\Models\FailedLoginAttempt::create([
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'attempted_at' => now(),
        ]);

        // Vérifie le nombre de tentatives échouées
        $attempts = \Modules\Security\Models\FailedLoginAttempt::pourIP($ip, 15)->count();

        if ($attempts >= config('security.security.max_login_attempts', 5)) {
            event(new \Modules\Security\Events\SuspiciousActivityDetected(
                'too_many_failed_logins',
                ['ip' => $ip, 'attempts' => $attempts]
            ));
        }
    }

    /**
     * Récupère les données de localisation
     *
     * @param User|null $user
     * @return array
     */
    protected function getLocationData(?User $user = null): array
    {
        $data = [
            'continent' => null,
            'pays' => null,
            'ville' => null,
        ];

        if ($user) {
            $data['continent'] = $user->continent;
            $data['pays'] = $user->pays;
            $data['ville'] = $user->ville;
        }

        return $data;
    }

    /**
     * Récupère l'ID du tenant actuel
     */
    protected function getCurrentTenantId(): ?string
    {
        if (function_exists('tenant') && tenant() !== null) {
            return tenant('id');
        }

        return null;
    }

    /**
     * Calcule les champs modifiés entre anciennes et nouvelles valeurs
     */
    protected function calculerChampsModifies(array $data): ?array
    {
        if (!isset($data['anciennes_valeurs']) || !isset($data['nouvelles_valeurs'])) {
            return null;
        }

        $modifies = [];
        $anciennes = $data['anciennes_valeurs'];
        $nouvelles = $data['nouvelles_valeurs'];

        foreach ($nouvelles as $key => $value) {
            if (isset($anciennes[$key]) && $anciennes[$key] != $value) {
                $modifies[$key] = [
                    'avant' => $anciennes[$key],
                    'apres' => $value,
                ];
            } elseif (!isset($anciennes[$key])) {
                $modifies[$key] = [
                    'avant' => null,
                    'apres' => $value,
                ];
            }
        }

        return empty($modifies) ? null : $modifies;
    }

    /**
     * Détecte le type d'appareil
     */
    protected function detectDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'mobile')) {
            return 'mobile';
        }
        if (str_contains($userAgent, 'tablet') || str_contains($userAgent, 'ipad')) {
            return 'tablet';
        }

        return 'desktop';
    }

    /**
     * Détecte le navigateur
     */
    protected function detectBrowser(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'chrome')) return 'Chrome';
        if (str_contains($userAgent, 'firefox')) return 'Firefox';
        if (str_contains($userAgent, 'safari')) return 'Safari';
        if (str_contains($userAgent, 'edge')) return 'Edge';
        if (str_contains($userAgent, 'opera')) return 'Opera';

        return 'Other';
    }

    /**
     * Détecte le système d'exploitation
     */
    protected function detectOS(?string $userAgent): ?string
    {
        if (!$userAgent) {
            return null;
        }

        $userAgent = strtolower($userAgent);

        if (str_contains($userAgent, 'windows')) return 'Windows';
        if (str_contains($userAgent, 'mac')) return 'macOS';
        if (str_contains($userAgent, 'linux')) return 'Linux';
        if (str_contains($userAgent, 'android')) return 'Android';
        if (str_contains($userAgent, 'ios') || str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad')) return 'iOS';

        return 'Other';
    }
}
