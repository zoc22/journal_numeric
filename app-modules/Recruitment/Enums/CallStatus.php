<?php

declare(strict_types=1);

namespace Modules\Recruitment\Enums;

/**
 * Enum CallStatus
 *
 * Définit les statuts possibles pour un appel à candidatures.
 */
enum CallStatus: string
{
    case BROUILLON = 'brouillon';
    case OUVERT = 'ouvert';
    case FERME = 'ferme';
    case ANNULE = 'annule';

    /**
     * Obtient le label lisible du statut
     */
    public function label(): string
    {
        return match($this) {
            self::BROUILLON => 'Brouillon',
            self::OUVERT => 'Ouvert',
            self::FERME => 'Fermé',
            self::ANNULE => 'Annulé',
        };
    }

    /**
     * Obtient la couleur CSS associée
     */
    public function couleur(): string
    {
        return match($this) {
            self::BROUILLON => 'gray',
            self::OUVERT => 'green',
            self::FERME => 'blue',
            self::ANNULE => 'red',
        };
    }

    /**
     * Vérifie si l'appel est actif (accepte les candidatures)
     */
    public function estActif(): bool
    {
        return $this === self::OUVERT;
    }

    /**
     * Vérifie si l'appel est modifiable
     */
    public function estModifiable(): bool
    {
        return in_array($this, [self::BROUILLON]);
    }
}
