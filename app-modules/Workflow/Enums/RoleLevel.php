<?php

declare(strict_types=1);

namespace Modules\Workflow\Enums;

/**
 * Enum RoleLevel
 *
 * Définit la hiérarchie des rôles avec leurs niveaux.
 * Un niveau plus élevé indique plus de privilèges.
 */
enum RoleLevel: int
{
    case LECTEUR = 1;
    case JOURNALISTE = 2;
    case REVIEWER = 3;
    case EDITEUR_ASSOCIE = 4;
    case DIRECTEUR_COLLECTION = 5;
    case EDITEUR_CHEF = 6;
    case ADMIN_PLATEFORME = 7;
    case SUPER_ADMIN = 8;

    /**
     * Obtient le label lisible du rôle
     */
    public function label(): string
    {
        return match($this) {
            self::LECTEUR => 'Lecteur',
            self::JOURNALISTE => 'Journaliste',
            self::REVIEWER => 'Reviewer',
            self::EDITEUR_ASSOCIE => 'Éditeur Associé',
            self::DIRECTEUR_COLLECTION => 'Directeur de Collection',
            self::EDITEUR_CHEF => 'Éditeur en Chef',
            self::ADMIN_PLATEFORME => 'Administrateur Plateforme',
            self::SUPER_ADMIN => 'Super Administrateur',
        };
    }

    /**
     * Vérifie si ce rôle est supérieur ou égal à un autre
     */
    public function estSuperieurOuEgal(RoleLevel $autre): bool
    {
        return $this->value >= $autre->value;
    }

    /**
     * Obtient le niveau depuis le nom du rôle
     */
    public static function fromNom(string $nom): self
    {
        return match($nom) {
            'lecteur' => self::LECTEUR,
            'journaliste' => self::JOURNALISTE,
            'reviewer' => self::REVIEWER,
            'editeur_associe' => self::EDITEUR_ASSOCIE,
            'directeur_collection' => self::DIRECTEUR_COLLECTION,
            'editeur_chef' => self::EDITEUR_CHEF,
            'admin_plateforme' => self::ADMIN_PLATEFORME,
            'super_admin' => self::SUPER_ADMIN,
            default => self::LECTEUR,
        };
    }

    /**
     * Obtient tous les niveaux inférieurs
     */
    public function niveauxInferieurs(): array
    {
        $result = [];
        foreach (self::cases() as $role) {
            if ($role->value < $this->value) {
                $result[] = $role;
            }
        }
        return $result;
    }

    /**
     * Obtient tous les niveaux supérieurs
     */
    public function niveauxSuperieurs(): array
    {
        $result = [];
        foreach (self::cases() as $role) {
            if ($role->value > $this->value) {
                $result[] = $role;
            }
        }
        return $result;
    }

    /**
     * Vérifie si le rôle peut valider à un niveau donné
     */
    public function peutValiderNiveau(int $niveauValidation): bool
    {
        // Le niveau de validation correspond au niveau de rôle requis
        return $this->value >= $niveauValidation;
    }
}
