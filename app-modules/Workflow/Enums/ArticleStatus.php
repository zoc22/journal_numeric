<?php

declare(strict_types=1);

namespace Modules\Workflow\Enums;

/**
 * Enum ArticleStatus
 *
 * Définit tous les statuts possibles d'un article dans le workflow éditorial.
 * Centralise les labels, couleurs et règles de transition.
 */
enum ArticleStatus: string
{
    // === Statuts de base ===
    case BROUILLON = 'brouillon';
    case SOUMIS = 'soumis';

    // === Statuts de relecture ===
    case EN_RELECTURE = 'en_relecture';
    case CORRECTION_DEMANDEE = 'correction_demandee';

    // === Statuts de validation progressive ===
    case VALIDE_REVIEWER = 'valide_reviewer';
    case VALIDE_EDITEUR = 'valide_editeur';
    case VALIDE_DIRECTEUR = 'valide_directeur';
    case VALIDE_FINAL = 'valide_final';

    // === Statuts terminaux ===
    case PUBLIE = 'publie';
    case REJETE = 'rejete';
    case ARCHIVE = 'archive';

    /**
     * Obtient le label lisible du statut
     */
    public function label(): string
    {
        return match($this) {
            self::BROUILLON => 'Brouillon',
            self::SOUMIS => 'Soumis à relecture',
            self::EN_RELECTURE => 'En relecture',
            self::CORRECTION_DEMANDEE => 'Corrections demandées',
            self::VALIDE_REVIEWER => 'Validé par le reviewer',
            self::VALIDE_EDITEUR => 'Validé par l\'éditeur associé',
            self::VALIDE_DIRECTEUR => 'Validé par le directeur de collection',
            self::VALIDE_FINAL => 'Validation finale obtenue',
            self::PUBLIE => 'Publié',
            self::REJETE => 'Rejeté',
            self::ARCHIVE => 'Archivé',
        };
    }

    /**
     * Obtient la couleur CSS associée au statut
     */
    public function couleur(): string
    {
        return match($this) {
            self::BROUILLON => 'gray',
            self::SOUMIS => 'blue',
            self::EN_RELECTURE => 'indigo',
            self::CORRECTION_DEMANDEE => 'orange',
            self::VALIDE_REVIEWER, self::VALIDE_EDITEUR, self::VALIDE_DIRECTEUR, self::VALIDE_FINAL => 'green',
            self::PUBLIE => 'emerald',
            self::REJETE => 'red',
            self::ARCHIVE => 'slate',
        };
    }

    /**
     * Obtient l'icône associée au statut
     */
    public function icone(): string
    {
        return match($this) {
            self::BROUILLON => 'pencil',
            self::SOUMIS => 'send',
            self::EN_RELECTURE => 'eye',
            self::CORRECTION_DEMANDEE => 'edit',
            self::VALIDE_REVIEWER, self::VALIDE_EDITEUR, self::VALIDE_DIRECTEUR, self::VALIDE_FINAL => 'check-circle',
            self::PUBLIE => 'globe',
            self::REJETE => 'x-circle',
            self::ARCHIVE => 'archive',
        };
    }

    /**
     * Obtient le niveau hiérarchique du statut (1-10)
     */
    public function niveau(): int
    {
        return match($this) {
            self::BROUILLON => 1,
            self::SOUMIS => 2,
            self::EN_RELECTURE => 3,
            self::CORRECTION_DEMANDEE => 4,
            self::VALIDE_REVIEWER => 5,
            self::VALIDE_EDITEUR => 6,
            self::VALIDE_DIRECTEUR => 7,
            self::VALIDE_FINAL => 8,
            self::PUBLIE => 9,
            self::REJETE => 10,
            self::ARCHIVE => 10,
        };
    }

    /**
     * Vérifie si le statut est terminal (fin du workflow)
     */
    public function estTerminal(): bool
    {
        return in_array($this, [self::PUBLIE, self::REJETE, self::ARCHIVE]);
    }

    /**
     * Vérifie si le statut est un statut de validation
     */
    public function estStatutValidation(): bool
    {
        return in_array($this, [
            self::VALIDE_REVIEWER,
            self::VALIDE_EDITEUR,
            self::VALIDE_DIRECTEUR,
            self::VALIDE_FINAL
        ]);
    }

    /**
     * Vérifie si l'article est modifiable dans ce statut
     */
    public function estModifiable(): bool
    {
        return in_array($this, [self::BROUILLON, self::CORRECTION_DEMANDEE]);
    }

    /**
     * Vérifie si une transition est autorisée
     */
    public function transitionAutorisee(ArticleStatus $nouveauStatut, int $roleNiveau): bool
    {
        // Règles de transition principales
        $transitionsAutorisees = [
            self::BROUILLON->value => [self::SOUMIS->value],
            self::SOUMIS->value => [self::EN_RELECTURE->value, self::CORRECTION_DEMANDEE->value, self::REJETE->value],
            self::EN_RELECTURE->value => [self::CORRECTION_DEMANDEE->value, self::VALIDE_REVIEWER->value, self::REJETE->value],
            self::CORRECTION_DEMANDEE->value => [self::SOUMIS->value, self::REJETE->value],
            self::VALIDE_REVIEWER->value => [self::VALIDE_EDITEUR->value, self::CORRECTION_DEMANDEE->value, self::REJETE->value],
            self::VALIDE_EDITEUR->value => [self::VALIDE_DIRECTEUR->value, self::CORRECTION_DEMANDEE->value, self::REJETE->value],
            self::VALIDE_DIRECTEUR->value => [self::VALIDE_FINAL->value, self::CORRECTION_DEMANDEE->value, self::REJETE->value],
            self::VALIDE_FINAL->value => [self::PUBLIE->value, self::CORRECTION_DEMANDEE->value, self::REJETE->value],
        ];

        // Vérifie la transition de base
        if (!isset($transitionsAutorisees[$this->value]) ||
            !in_array($nouveauStatut->value, $transitionsAutorisees[$this->value])) {
            return false;
        }

        // Pour les validations, vérifier que le rôle a le niveau suffisant
        if ($nouveauStatut->estStatutValidation()) {
            $niveauRequis = $this->niveauValidationRequis($nouveauStatut);
            return $roleNiveau >= $niveauRequis;
        }

        return true;
    }

    /**
     * Obtient le niveau de rôle requis pour une validation
     */
    public function niveauValidationRequis(ArticleStatus $statutValidation): int
    {
        return match($statutValidation) {
            self::VALIDE_REVIEWER => RoleLevel::REVIEWER->value,
            self::VALIDE_EDITEUR => RoleLevel::EDITEUR_ASSOCIE->value,
            self::VALIDE_DIRECTEUR => RoleLevel::DIRECTEUR_COLLECTION->value,
            self::VALIDE_FINAL => RoleLevel::EDITEUR_CHEF->value,
            default => 0,
        };
    }

    /**
     * Obtient tous les statuts possibles
     */
    public static function tous(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Obtient les statuts pour l'affichage public
     */
    public static function statutsPublics(): array
    {
        return [
            self::PUBLIE->value,
            self::ARCHIVE->value,
        ];
    }

    /**
     * Crée une instance depuis une valeur string
     */
    public static function fromString(string $statut): self
    {
        return match($statut) {
            'brouillon' => self::BROUILLON,
            'soumis' => self::SOUMIS,
            'en_relecture' => self::EN_RELECTURE,
            'correction_demandee' => self::CORRECTION_DEMANDEE,
            'valide_reviewer' => self::VALIDE_REVIEWER,
            'valide_editeur' => self::VALIDE_EDITEUR,
            'valide_directeur' => self::VALIDE_DIRECTEUR,
            'valide_final' => self::VALIDE_FINAL,
            'publie' => self::PUBLIE,
            'rejete' => self::REJETE,
            'archive' => self::ARCHIVE,
            default => throw new \InvalidArgumentException("Statut invalide: {$statut}"),
        };
    }
}
