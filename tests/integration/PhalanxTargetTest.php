<?php

namespace Risistar\Tests\Integration;

use ReflectionProperty;
use ShowPhalanxPage;
use template;

/**
 * Integration tests for the target of a phalanx scan.
 */
class PhalanxTargetTest extends GamePageTestCase
{
    private array $target = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowPhalanxPage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        // IntegrationTestCase::setUpBeforeClass() loads vars.php inside a function, so its resource entries are not global.
        $GLOBALS['resource'] += [901 => 'metal', 902 => 'crystal', 903 => 'deuterium', 911 => 'energy', 921 => 'darkmatter'];
        $GLOBALS['reslist']['ressources'] = [901, 902, 903, 911, 921];
        $GLOBALS['reslist']['resstype'] = [1 => [901, 902, 903], 2 => [911], 3 => [921]];
        $this->target = self::$db->selectSingle('SELECT * FROM %%PLANETS%% WHERE id = :id;', [':id' => $this->players(2)[1]['id_planet']]);
        // The scanner is in range of the target, with a phalanx and enough fuel
        $GLOBALS['PLANET']['galaxy'] = $this->target['galaxy'];
        $GLOBALS['PLANET']['system'] = $this->target['system'];
        $GLOBALS['PLANET']['phalanx'] = 2;
        $GLOBALS['PLANET']['deuterium'] = PHALANX_DEUTERIUM * 2;
        self::$db->update('UPDATE %%PLANETS%% SET deuterium = :fuel WHERE id = :id;', [':fuel' => PHALANX_DEUTERIUM * 2, ':id' => $GLOBALS['PLANET']['id']]);
        $GLOBALS['_REQUEST'] = ['galaxy' => $this->target['galaxy'], 'system' => $this->target['system'], 'planet' => $this->target['planet']];
    }

    public function testPlanetIsScannedAndFuelIsPaid(): void
    {
        $before = $this->fuel();

        $this->scan();

        $this->assertSame($this->target['name'], $this->assigned['name']);
        $this->assertEquals($before - PHALANX_DEUTERIUM, $this->fuel());
    }

    public function testEmptyPositionCostsNoFuel(): void
    {
        $GLOBALS['_REQUEST']['planet'] = 999;
        $before = $this->fuel();

        $this->scan();

        $this->assertArrayNotHasKey('name', $this->assigned);
        $this->assertEquals($before, $this->fuel());
    }

    public function testMoonIsNotScanned(): void
    {
        self::$db->update('UPDATE %%PLANETS%% SET planet_type = :type WHERE id = :id;', [':type' => '3', ':id' => $this->target['id']]);
        $before = $this->fuel();

        $this->scan();

        $this->assertArrayNotHasKey('name', $this->assigned);
        $this->assertEquals($before, $this->fuel());
    }

    private function scan(): void
    {
        $page = $this->page(ShowPhalanxPage::class, ['initTemplate', 'setWindow']);
        $tpl = $this->getMockBuilder(template::class)->disableOriginalConstructor()->onlyMethods(['loadscript'])->getMock();
        (new ReflectionProperty(ShowPhalanxPage::class, 'tplObj'))->setValue($page, $tpl);
        $this->callPage($page, 'show');
    }

    private function fuel(): float
    {
        return (float) self::$db->selectSingle('SELECT deuterium FROM %%PLANETS%% WHERE id = :id;', [':id' => $GLOBALS['PLANET']['id']], 'deuterium');
    }
}
