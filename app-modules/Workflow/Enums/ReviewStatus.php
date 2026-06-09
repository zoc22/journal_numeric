<?php

declare(strict_types=1);

namespace Modules\Workflow\Enums;

/**
 * Enum ReviewStatus
 *
 * Définit tous les statuts possibles pour une assignation de review.
 */
enum ReviewStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case ACCEPTE = 'accepte';
    case REFUSE = 'refuse';
    case EN_COURS = 'en_cours';
    case RETOUR_ENVOYE = 'retour_envoye';
    case TERMINE = 'termine';
    case EXPIRE = 'expire';

    /**
     * Obtient le label lisible
     */
    public function label(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::ACCEPTE => 'Accepté',
            self::REFUSE => 'Refusé',
            self::EN_COURS => 'En cours de relecture',
            self::RETOUR_ENVOYE => 'Retour envoyé',
            self::TERMINE => 'Terminé',
            self::EXPIRE => 'Expiré',
        };
    }

    /**
     * Obtient la couleur CSS
     */
    public function couleur(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'yellow',
            self::ACCEPTE => 'blue',
            self::REFUSE => 'red',
            self::EN_COURS => 'indigo',
            self::RETOUR_ENVOYE => 'purple',
            self::TERMINE => 'green',
            self::EXPIRE => 'gray',
        };
    }

    /**
     * Vérifie si le statut est actif
     */
    public function estActif(): bool
    {
        return in_array($this, [self::EN_ATTENTE, self::ACCEPTE, self::EN_COURS]);
    }

    /**
     * Vérifie si le statut est terminal
     */
    public function estTerminal(): bool
    {
        return in_array($this, [self::TERMINE, self::EXPIRE]);
    }
}
