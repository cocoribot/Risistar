<?php

namespace Risistar\Tests\Unit;

use ResourceUpdate;

class ResourceUpdateTest extends UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootConstants();
        require_once self::rootPath() . 'includes/classes/class.PlanetRessUpdate.php';
    }

    public function testResourceUpdateInstanceCreation()
    {
        $updater = new ResourceUpdate(true, true);
        $this->assertInstanceOf(ResourceUpdate::class, $updater);
    }

    public function testResourceUpdateDataGetSet()
    {
        $updater = new ResourceUpdate();
        $dummyUser = [
            'id' => 1,
            'universe' => 1,
            'urlaubs_modus' => 0,
            'b_tech' => 0,
            'factor' => ['Resource' => 1, 'Energy' => 1],
        ];
        $dummyPlanet = [
            'id' => 1,
            'planet_type' => 1,
            'last_update' => TIMESTAMP - 3600,
            'metal' => 1000,
            'crystal' => 500,
            'deuterium' => 100,
            'metal_perhour' => 1000,
            'crystal_perhour' => 500,
            'deuterium_perhour' => 200,
            'metal_max' => 100000,
            'crystal_max' => 100000,
            'deuterium_max' => 100000,
            'b_building' => 0,
            'b_hangar' => 0,
        ];

        $updater->setData($dummyUser, $dummyPlanet);
        list($retUser, $retPlanet) = $updater->getData();

        $this->assertEquals(1, $retUser['id']);
        $this->assertEquals(1000, $retPlanet['metal']);
    }

    public function testCreateHashChangesWhenGilbertCountChanges(): void
    {
        $updater = $this->hashUpdater();

        $planet = $this->hashPlanet(['gilbert' => 10]);
        $updater->setData($this->hashUser(), $planet);
        $withGilberts = $updater->CreateHash();

        $planet['gilbert'] = 0;
        $updater->setData($this->hashUser(), $planet);
        $withoutGilberts = $updater->CreateHash();

        $this->assertNotSame($withGilberts, $withoutGilberts);
    }

    public function testCreateHashIgnoresUnrelatedShipCounts(): void
    {
        $updater = $this->hashUpdater();

        $planet = $this->hashPlanet(['small_ship_cargo' => 5]);
        $updater->setData($this->hashUser(), $planet);
        $hashBefore = $updater->CreateHash();

        $planet['small_ship_cargo'] = 0;
        $updater->setData($this->hashUser(), $planet);
        $hashAfter = $updater->CreateHash();

        $this->assertSame($hashBefore, $hashAfter);
    }

    private function hashUpdater(): ResourceUpdate
    {
        $GLOBALS['reslist'] = [
            'prod' => [1],
            'resstype' => [
                1 => [901],
                2 => [911],
            ],
        ];
        $GLOBALS['resource'] = [
            1 => 'metal_mine',
            22 => 'metal_store',
            23 => 'crystal_store',
            24 => 'deuterium_store',
            131 => 'metal_proc_tech',
            132 => 'crystal_proc_tech',
            133 => 'deuterium_proc_tech',
            239 => 'gilbert',
            901 => 'metal',
            911 => 'energy',
        ];

        $updater = new ResourceUpdate(false, false);
        $config = (object) [
            'metal_basic_income' => 20,
            'energy_basic_income' => 0,
            'resource_multiplier' => 1,
            'storage_multiplier' => 1,
            'energySpeed' => 1,
        ];

        $configProperty = new \ReflectionProperty(ResourceUpdate::class, 'config');
        $configProperty->setAccessible(true);
        $configProperty->setValue($updater, $config);

        return $updater;
    }

    /**
     * @param array<string, int> $overrides
     * @return array<string, mixed>
     */
    private function hashPlanet(array $overrides = []): array
    {
        return array_merge([
            'metal_mine' => 10,
            'metal_mine_porcent' => 10,
            'metal_store' => 5,
            'crystal_store' => 5,
            'deuterium_store' => 5,
            'gilbert' => 0,
            'small_ship_cargo' => 0,
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function hashUser(): array
    {
        return [
            'factor' => ['Resource' => 0, 'Energy' => 0],
            'metal_proc_tech' => 0,
            'crystal_proc_tech' => 0,
            'deuterium_proc_tech' => 0,
        ];
    }
}
