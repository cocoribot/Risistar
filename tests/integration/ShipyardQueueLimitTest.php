<?php

namespace Risistar\Tests\Integration;

use BuildFunctions;
use Config;
use ReflectionClass;
use ReflectionMethod;
use ShowShipyardPage;

/**
 * Integration tests for the shipyard queue limit (max_elements_ships).
 */
class ShipyardQueueLimitTest extends GamePageTestCase
{
    private int $limit = 0;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/classes/class.BuildFunctions.php';
        require_once self::rootPath() . 'includes/pages/game/ShowShipyardPage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->limit = (int) Config::get()->max_elements_ships;
        $this->assertGreaterThan(0, $this->limit, 'The test database needs a shipyard queue limit.');
        // vars.php is loaded inside a function by the bootstrap, so the resource entries are not global.
        $GLOBALS['resource'] += [901 => 'metal', 902 => 'crystal', 903 => 'deuterium', 911 => 'energy', 921 => 'darkmatter'];
        $GLOBALS['reslist']['ressources'] = [901, 902, 903, 911, 921];
        $GLOBALS['reslist']['resstype'] = [1 => [901, 902, 903], 2 => [911], 3 => [921]];
        // High enough to meet the requirements of every ship.
        foreach (['build', 'tech'] as $type) {
            foreach ($GLOBALS['reslist'][$type] as $element) {
                $field = $GLOBALS['resource'][$element];
                if (isset($GLOBALS['PLANET'][$field])) {
                    $GLOBALS['PLANET'][$field] = 50;
                } elseif (isset($GLOBALS['USER'][$field])) {
                    $GLOBALS['USER'][$field] = 50;
                }
            }
        }
        foreach (['metal', 'crystal', 'deuterium'] as $field) {
            $GLOBALS['PLANET'][$field] = 1e15;
        }
        $GLOBALS['PLANET']['b_hangar'] = 0;
    }

    public function testOneRequestCannotFillMoreThanTheFreeSlots(): void
    {
        $GLOBALS['PLANET']['b_hangar_id'] = serialize(array_fill(0, $this->limit - 1, [202, 1]));
        $metal = $GLOBALS['PLANET']['metal'];
        $price = BuildFunctions::getElementPrice($GLOBALS['USER'], $GLOBALS['PLANET'], 202, false, 1);

        $this->build([202 => 1, 203 => 1]);

        $this->assertCount($this->limit, $this->queue());
        $this->assertSame((int) ($metal - $price[901]), (int) $GLOBALS['PLANET']['metal']);
    }

    public function testFullQueueChargesNothing(): void
    {
        $GLOBALS['PLANET']['b_hangar_id'] = serialize(array_fill(0, $this->limit, [202, 1]));
        $before = $GLOBALS['PLANET'];

        $this->build([203 => 1]);

        $this->assertSame($before, $GLOBALS['PLANET']);
    }

    private function build(array $ships): void
    {
        $page = (new ReflectionClass(ShowShipyardPage::class))->newInstanceWithoutConstructor();
        (new ReflectionMethod(ShowShipyardPage::class, 'BuildAuftr'))->invoke($page, $ships);
    }

    private function queue(): array
    {
        return empty($GLOBALS['PLANET']['b_hangar_id']) ? [] : unserialize($GLOBALS['PLANET']['b_hangar_id']);
    }
}
