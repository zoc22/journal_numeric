<?php

declare(strict_types=1);

namespace Modules\Security\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement SecurityAlert
 *
 * Déclenché lorsqu'une alerte de sécurité est générée.
 * Utilisé pour notifier les administrateurs de menaces potentielles.
 */
class SecurityAlert
{
    use Dispatchable, SerializesModels;

    /**
     * Niveaux d'alerte
     */
    public const LEVEL_LOW = 'low';
    public const LEVEL_MEDIUM = 'medium';
    public const LEVEL_HIGH = 'high';
    public const LEVEL_CRITICAL = 'critical';

    /**
     * Types d'alerte
     */
    public const TYPE_BRUTE_FORCE = 'brute_force';
    public const TYPE_SUSPICIOUS_IP = 'suspicious_ip';
    public const TYPE_UNUSUAL_HOURS = 'unusual_hours';
    public const TYPE_MULTIPLE_FAILURES = 'multiple_failures';
    public const TYPE_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    public const TYPE_SENSITIVE_ACTION = 'sensitive_action';

    /**
     * Constructeur
     *
     * @param string $type Type d'alerte
     * @param string $level Niveau de criticité
     * @param string $message Message d'alerte
     * @param array $data Données contextuelles
     */
    public function __construct(
        public string $type,
        public string $level,
        public string $message,
        public array $data = []
    ) {}

    /**
     * Vérifie si l'alerte est de niveau critique
     */
    public function isCritical(): bool
    {
        return $this->level === self::LEVEL_CRITICAL;
    }

    /**
     * Vérifie si l'alerte est de niveau haut
     */
    public function isHigh(): bool
    {
        return $this->level === self::LEVEL_HIGH;
    }

    /**
     * Obtient le label du niveau
     */
    public function getLevelLabel(): string
    {
        return match($this->level) {
            self::LEVEL_LOW => 'Faible',
            self::LEVEL_MEDIUM => 'Moyen',
            self::LEVEL_HIGH => 'Élevé',
            self::LEVEL_CRITICAL => 'Critique',
            default => 'Inconnu',
        };
    }

    /**
     * Obtient le label du type
     */
    public function getTypeLabel(): string
    {
        return match($this->type) {
            self::TYPE_BRUTE_FORCE => 'Attaque par force brute',
            self::TYPE_SUSPICIOUS_IP => 'IP suspecte',
            self::TYPE_UNUSUAL_HOURS => 'Activité à heure inhabituelle',
            self::TYPE_MULTIPLE_FAILURES => 'Multiples échecs',
            self::TYPE_UNAUTHORIZED_ACCESS => 'Tentative d\'accès non autorisé',
            self::TYPE_SENSITIVE_ACTION => 'Action sensible détectée',
            default => 'Alerte de sécurité',
        };
    }

    /**
     * Obtient la couleur associée au niveau
     */
    public function getLevelColor(): string
    {
        return match($this->level) {
            self::LEVEL_LOW => 'blue',
            self::LEVEL_MEDIUM => 'orange',
            self::LEVEL_HIGH => 'red',
            self::LEVEL_CRITICAL => 'darkred',
            default => 'gray',
        };
    }
}
