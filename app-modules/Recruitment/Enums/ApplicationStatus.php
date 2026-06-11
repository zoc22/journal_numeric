<?php

declare(strict_types=1);

namespace Modules\Recruitment\Enums;

/**
 * Enum ApplicationStatus
 *
 * Définit les statuts possibles pour une candidature.
 */
enum ApplicationStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case EN_REVUE = 'en_revue';
    case ACCEPTEE = 'acceptee';
    case REJETEE = 'rejetee';

    /**
     * Obtient le label lisible du statut
     */
    public function label(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::EN_REVUE => 'En revue',
            self::ACCEPTEE => 'Acceptée',
            self::REJETEE => 'Rejetée',
        };
    }

    /**
     * Obtient la couleur CSS associée
     */
    public function couleur(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'yellow',
            self::EN_REVUE => 'blue',
            self::ACCEPTEE => 'green',
            self::REJETEE => 'red',
        };
    }

    /**
     * Vérifie si la candidature peut être modifiée
     */
    public function estModifiable(): bool
    {
        return $this === self::EN_ATTENTE;
    }

    /**
     * Vérifie si la candidature est en cours d'examen
     */
    public function estEnCours(): bool
    {
        return in_array($this, [self::EN_ATTENTE, self::EN_REVUE]);
    }
}
