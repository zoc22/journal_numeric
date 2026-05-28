<?php
// app-modules/Core/Contracts/ModuleInterface.php

namespace Modules\Core\Contracts;

/**
 * Interface que chaque module doit implémenter (via son ServiceProvider principal).
 */
interface ModuleInterface
{
    /**
     * Retourne le nom du module.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Retourne la version du module.
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Liste des modules dont celui-ci dépend.
     *
     * @return array
     */
    public function getDependencies(): array;

    /**
     * Vérifie si le module est activé.
     *
     * @return bool
     */
    public function isEnabled(): bool;
}
