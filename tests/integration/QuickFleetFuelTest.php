<?php

namespace Risistar\Tests\Integration;

use FleetFunctions;
use RuntimeException;
use ShowFleetAjaxPage;

/**
 * Integration tests for the fuel of quick spy and recycling missions sent from the galaxy.
 */
class QuickFleetFuelTest extends GamePageTestCase
{
    private array $target = [];
    private array $response = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowFleetAjaxPage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $other = $this->players(2)[1];
        $GLOBALS['USER']['factor'] = ['FlyTime' => 0, 'FleetSlots' => 0];
        $GLOBALS['USER']['spio_anz'] = 5;
        $GLOBALS['USER']['total_points'] = 100;
        self::$db->update('UPDATE %%STATPOINTS%% SET total_points = 100 WHERE id_owner = :id AND stat_type = 1;',
            [':id' => $other['id']]);
        $this->target = self::$db->selectSingle('SELECT * FROM %%PLANETS%% WHERE id = :id;', [':id' => $other['id_planet']]);
        self::$db->update('UPDATE %%PLANETS%% SET der_metal = 10000, der_crystal = 0 WHERE id = :id;',
            [':id' => $this->target['id']]);
        self::$db->update('UPDATE %%PLANETS%% SET spy_sonde = 10, recycler = 10, deuterium = 100000 WHERE id = :id;',
            [':id' => $GLOBALS['PLANET']['id']]);
        $GLOBALS['PLANET']['spy_sonde'] = 10;
        $GLOBALS['PLANET']['recycler'] = 10;
        $GLOBALS['PLANET'][$GLOBALS['resource'][219]] = 0;
        $GLOBALS['PLANET']['deuterium'] = 100000;
        $GLOBALS['_REQUEST'] = ['planetID' => $this->target['id']];
    }

    public function testQuickSpyPaysFuel(): void
    {
        $this->assertFuelPaid(6, [210 => 5]);
    }

    public function testQuickRecyclePaysFuel(): void
    {
        $this->assertFuelPaid(8, [209 => 1]);
    }

    private function assertFuelPaid(int $mission, array $ships): void
    {
        $before = $GLOBALS['PLANET']['deuterium'];
        $start = [$GLOBALS['PLANET']['galaxy'], $GLOBALS['PLANET']['system'], $GLOBALS['PLANET']['planet']];
        $target = [$this->target['galaxy'], $this->target['system'], $this->target['planet']];
        $distance = FleetFunctions::GetTargetDistance($start, $target);
        $speed = FleetFunctions::GetGameSpeedFactor();
        $duration = FleetFunctions::GetMissionDuration(10, FleetFunctions::GetFleetMaxSpeed($ships, $GLOBALS['USER']),
            $distance, $speed, $GLOBALS['USER']);
        $fuel = FleetFunctions::GetFleetConsumption($ships, $duration, $distance, $GLOBALS['USER'], $speed);
        $this->assertGreaterThan(0, $fuel);
        $GLOBALS['_REQUEST']['mission'] = $mission;
        $this->send();
        $this->assertEquals(600, $this->response['code']);
        $this->assertEquals($before - $fuel, $GLOBALS['PLANET']['deuterium']);
        $fleet = self::$db->selectSingle('SELECT * FROM %%FLEETS%% WHERE fleet_owner = :owner ORDER BY fleet_id DESC LIMIT 1;',
            [':owner' => $GLOBALS['USER']['id']]);
        $this->assertEquals($mission, $fleet['fleet_mission']);
        $this->assertEquals(0, $fleet['fleet_resource_deuterium']);
    }

    private function send(): void
    {
        $page = $this->page(ShowFleetAjaxPage::class, ['sendJSON']);
        $page->method('sendJSON')->willReturnCallback(function ($data) {
            $this->response = $data;
            throw new RuntimeException('page-stopped');
        });
        $this->callPage($page, 'show');
    }
}
