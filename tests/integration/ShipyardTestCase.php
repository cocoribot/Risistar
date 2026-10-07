<?php

namespace Risistar\Tests\Integration;

use ReflectionClass;
use ReflectionMethod;
use ShowShipyardPage;

/**
 * Base class for tests that add orders to the shipyard queue.
 */
abstract class ShipyardTestCase extends GamePageTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/classes/class.BuildFunctions.php';
        require_once self::rootPath() . 'includes/pages/game/ShowShipyardPage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        // High enough to meet every requirement
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
        $GLOBALS['USER']['darkmatter'] = 1e12;
        $GLOBALS['PLANET']['b_hangar'] = 0;
        $GLOBALS['PLANET']['b_hangar_id'] = '';
    }

    protected function build(array $ships): void
    {
        $page = (new ReflectionClass(ShowShipyardPage::class))->newInstanceWithoutConstructor();
        (new ReflectionMethod(ShowShipyardPage::class, 'BuildAuftr'))->invoke($page, $ships);
    }

    protected function queue(): array
    {
        return empty($GLOBALS['PLANET']['b_hangar_id']) ? [] : unserialize($GLOBALS['PLANET']['b_hangar_id']);
    }
}
