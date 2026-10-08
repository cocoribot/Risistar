<?php

namespace Risistar\Tests\Integration;

use FleetFunctions;
use ReflectionProperty;
use ShowFleetStep1Page;
use template;

/**
 * Integration tests for ships that cannot fly (no speed) on fleet step 1.
 */
class FleetMovableShipsTest extends GamePageTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowFleetStep1Page.class.php';
    }

    public function testShipsWithoutSpeedAreNotAddedToTheFleet(): void
    {
        $GLOBALS['USER']['factor'] = ['ShipStorage' => 0, 'FlyTime' => 0, 'FleetSlots' => 0];
        $GLOBALS['USER']['PLANETS'] = [$GLOBALS['PLANET']];
        $GLOBALS['_SESSION']['fleet'] = [];
        $GLOBALS['_REQUEST']['ship202'] = 2;
        $GLOBALS['_REQUEST']['ship239'] = 1;
        $this->assertSame(0, (int) FleetFunctions::GetFleetMaxSpeed(239, $GLOBALS['USER']));

        $page = $this->page(ShowFleetStep1Page::class);
        $tpl = $this->getMockBuilder(template::class)->disableOriginalConstructor()->onlyMethods(['loadscript', 'execscript'])->getMock();
        (new ReflectionProperty(ShowFleetStep1Page::class, 'tplObj'))->setValue($page, $tpl);
        $this->callPage($page, 'show');

        $this->assertSame([202 => 2.0], $GLOBALS['_SESSION']['fleet'][$this->assigned['token']]['fleet']);
        $this->assertGreaterThan(0, $this->assigned['fleetdata']['maxspeed']);
    }
}
