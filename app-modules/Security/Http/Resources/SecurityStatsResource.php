<?php

declare(strict_types=1);

namespace Modules\Security\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource SecurityStatsResource
 *
 * Transforme les statistiques de sécurité en tableau JSON.
 */
class SecurityStatsResource extends JsonResource
{
    /**
     * Transforme la resource en tableau
     */
    public function toArray($request): array
    {
        return [
            'period' => [
                'start' => $this['period_start'] ?? null,
                'end' => $this['period_end'] ?? null,
            ],
            'logins' => [
                'total' => $this['total_logins'] ?? 0,
                'successful' => $this['successful_logins'] ?? 0,
                'failed' => $this['failed_logins'] ?? 0,
                'success_rate' => isset($this['total_logins']) && $this['total_logins'] > 0
                    ? round(($this['successful_logins'] / $this['total_logins']) * 100, 2)
                    : 0,
            ],
            'audit' => [
                'total_actions' => $this['total_actions'] ?? 0,
                'by_level' => [
                    'info' => $this['info_actions'] ?? 0,
                    'warning' => $this['warning_actions'] ?? 0,
                    'critical' => $this['critical_actions'] ?? 0,
                ],
                'top_modules' => $this['top_modules'] ?? [],
                'top_actions' => $this['top_actions'] ?? [],
            ],
            'sessions' => [
                'active' => $this['active_sessions'] ?? 0,
                'unique_ips' => $this['unique_ips'] ?? 0,
            ],
            'threats' => [
                'suspicious_activities' => $this['suspicious_activities'] ?? 0,
                'blocked_ips' => $this['blocked_ips'] ?? 0,
                'failed_attempts_today' => $this['failed_attempts_today'] ?? 0,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
