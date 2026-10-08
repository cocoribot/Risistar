<?php

namespace Risistar\Tests\Integration;

use ReflectionProperty;
use ShowFleetStep3Page;
use stdClass;
use template;

/**
 * Base class for tests that send a fleet through fleet step 3, from the first player's main planet.
 */
abstract class FleetDispatchTestCase extends GamePageTestCase
{
    protected array $target = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowFleetStep3Page.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        // IntegrationTestCase::setUpBeforeClass() loads vars.php inside a function, so its resource entries are not global.
        $GLOBALS['resource'] += [901 => 'metal', 902 => 'crystal', 903 => 'deuterium', 911 => 'energy', 921 => 'darkmatter'];
        $GLOBALS['reslist']['ressources'] = [901, 902, 903, 911, 921];
        $GLOBALS['reslist']['resstype'] = [1 => [901, 902, 903], 2 => [911], 3 => [921]];
        $GLOBALS['USER']['factor'] = ['ShipStorage' => 0, 'FlyTime' => 0, 'FleetSlots' => 10];
        $GLOBALS['USER']['PLANETS'] = [$GLOBALS['PLANET']];
        $target = self::$db->selectSingle('SELECT * FROM %%PLANETS%% WHERE id_owner = :owner AND id != :id AND planet_type = :type AND destruyed = 0 LIMIT 1;',
            [':owner' => $GLOBALS['USER']['id'], ':id' => $GLOBALS['PLANET']['id'], ':type' => '1']);
        if (empty($target)) {
            $this->markTestSkipped('The test database needs a player with two planets.');
        }
        $this->target = $target;
        $GLOBALS['PLANET']['small_ship_cargo'] = 100;
        $GLOBALS['PLANET']['deuterium'] = 1000000;
        self::$db->update('UPDATE %%PLANETS%% SET small_ship_cargo = 100, deuterium = 1000000 WHERE id = :id;', [':id' => $GLOBALS['PLANET']['id']]);
        $GLOBALS['_REQUEST'] = ['token' => 'dispatch-token', 'mission' => 4];
        $GLOBALS['_SESSION']['fleet']['dispatch-token'] = [
            'time' => TIMESTAMP, 'fleet' => [202 => 1], 'fleetRoom' => 5000, 'distance' => 5,
            'targetGalaxy' => $this->target['galaxy'], 'targetSystem' => $this->target['system'],
            'targetPlanet' => $this->target['planet'], 'targetType' => 1, 'fleetGroup' => 0, 'fleetSpeed' => 10,
        ];
    }

    protected function sendFleet(): void
    {
        $page = $this->page(ShowFleetStep3Page::class, ['save']);
        (new ReflectionProperty(ShowFleetStep3Page::class, 'ecoObj'))->setValue($page, new stdClass());
        $tpl = $this->getMockBuilder(template::class)->disableOriginalConstructor()->onlyMethods(['gotoside'])->getMock();
        (new ReflectionProperty(ShowFleetStep3Page::class, 'tplObj'))->setValue($page, $tpl);
        $this->callPage($page, 'show');
    }

    protected function latestFleet(): array
    {
        return self::$db->selectSingle('SELECT * FROM %%FLEETS%% WHERE fleet_owner = :owner ORDER BY fleet_id DESC LIMIT 1;',
            [':owner' => $GLOBALS['USER']['id']]);
    }
}
