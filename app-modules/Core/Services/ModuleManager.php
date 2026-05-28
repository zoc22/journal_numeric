<?php
// app-modules/Core/Services/ModuleManager.php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

/**
 * Gère la découverte et l'état des modules de l'application.
 * Il scanne le dossier app-modules et lit les fichiers composer.json de chaque module.
 */
class ModuleManager
{
    /**
     * Collection de tous les modules trouvés.
     *
     * @var Collection
     */
    protected Collection $modules;

    /**
     * Constructeur : initialise la collection et charge les modules.
     */
    public function __construct()
    {
        $this->modules = collect();
        $this->loadModules();
    }

    /**
     * Scanne le dossier app-modules et charge les informations de chaque module.
     *
     * @return void
     */
    protected function loadModules(): void
    {
        $modulesPath = base_path('app-modules');

        if (!File::isDirectory($modulesPath)) {
            return;
        }

        // On parcourt tous les sous-dossiers de app-modules
        foreach (File::directories($modulesPath) as $moduleDir) {
            $moduleName = basename($moduleDir);
            $composerFile = $moduleDir . '/composer.json';

            $config = [];
            if (File::exists($composerFile)) {
                $config = json_decode(File::get($composerFile), true);
            }

            // Si pas de composer.json, on définit des valeurs par défaut
            $this->modules->push([
                'name' => $moduleName,
                'active' => $config['extra']['active'] ?? true,
                'version' => $config['version'] ?? '1.0.0',
                'description' => $config['description'] ?? '',
                'dependencies' => $config['extra']['dependencies'] ?? [],
            ]);
        }
    }

    /**
     * Vérifie si un module est activé.
     *
     * @param string $moduleName
     * @return bool
     */
    public function isEnabled(string $moduleName): bool
    {
        $module = $this->modules->firstWhere('name', $moduleName);
        return $module && $module['active'];
    }

    /**
     * Retourne la liste des modules activés.
     *
     * @return Collection
     */
    public function getEnabledModules(): Collection
    {
        return $this->modules->where('active', true);
    }

    /**
     * Retourne tous les modules.
     *
     * @return Collection
     */
    public function getAllModules(): Collection
    {
        return $this->modules;
    }
}
