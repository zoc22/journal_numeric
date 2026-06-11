<?php

declare(strict_types=1);

namespace Modules\Security\Listeners;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Security\Events\SecurityAlert;

/**
 * Listener SendSecurityAlert
 *
 * Envoie des alertes de sécurité via les canaux configurés.
 */
class SendSecurityAlert
{
    /**
     * Handle the event.
     */
    public function handle(SecurityAlert $event): void
    {
        if (!config('security.alerts.enabled', true)) {
            return;
        }

        $channels = config('security.alerts.channels', ['log']);

        foreach ($channels as $channel) {
            $method = 'sendVia' . ucfirst($channel);
            if (method_exists($this, $method)) {
                $this->$method($event);
            }
        }
    }

    /**
     * Envoie via le canal log
     */
    protected function sendViaLog(SecurityAlert $event): void
    {
        $level = match($event->level) {
            SecurityAlert::LEVEL_CRITICAL => 'critical',
            SecurityAlert::LEVEL_HIGH => 'error',
            SecurityAlert::LEVEL_MEDIUM => 'warning',
            default => 'info',
        };

        Log::channel('security')->$level('Alerte de sécurité', [
            'type' => $event->type,
            'type_label' => $event->getTypeLabel(),
            'level' => $event->level,
            'level_label' => $event->getLevelLabel(),
            'message' => $event->message,
            'data' => $event->data,
        ]);
    }

    /**
     * Envoie via le canal email
     */
    protected function sendViaEmail(SecurityAlert $event): void
    {
        $adminEmail = config('security.alerts.admin_email');

        if (!$adminEmail) {
            return;
        }

        // Seules les alertes de niveau élevé ou critique sont envoyées par email
        if (!$event->isHigh() && !$event->isCritical()) {
            return;
        }

        try {
            // Ici, implémenter l'envoi d'email
            // Mail::to($adminEmail)->send(new SecurityAlertMail($event));

            Log::info('Alerte de sécurité envoyée par email', [
                'to' => $adminEmail,
                'type' => $event->type,
                'level' => $event->level,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'alerte de sécurité par email', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoie via le canal Slack (à implémenter)
     */
    protected function sendViaSlack(SecurityAlert $event): void
    {
        // Implémentation pour Slack
        // Notification Slack uniquement pour les alertes critiques
        if (!$event->isCritical()) {
            return;
        }

        Log::info('Alerte de sécurité à envoyer via Slack', [
            'type' => $event->type,
            'message' => $event->message,
        ]);
    }

    /**
     * Envoie via le canal Webhook (à implémenter)
     */
    protected function sendViaWebhook(SecurityAlert $event): void
    {
        // Implémentation pour Webhook externe
        Log::info('Alerte de sécurité à envoyer via webhook', [
            'type' => $event->type,
            'message' => $event->message,
        ]);
    }
}
