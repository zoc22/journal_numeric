<?php

namespace Modules\Core\Tests\Unit\Services;

use Tests\TestCase;
use Modules\Core\Services\ModuleManager;

class ModuleManagerTest extends TestCase
{
    /** @test */
    public function it_loads_modules_and_checks_enabled()
    {
        $manager = new ModuleManager();
        $all = $manager->getAllModules();
        $this->assertGreaterThan(0, $all->count(), "Aucun module trouvé !");

        $coreModule = $all->firstWhere('name', 'Core');
        $this->assertNotNull($coreModule, "Le module Core n’est pas chargé.");
        $this->assertTrue($manager->isEnabled('Core'));
    }

    /** @test */
    public function it_returns_enabled_modules()
    {
        $manager = new ModuleManager();
        $enabled = $manager->getEnabledModules();
        $this->assertTrue($enabled->contains('name', 'Core'));
    }
}
