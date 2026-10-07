<?php

namespace Risistar\Tests\Integration;

use BuildFunctions;

/**
 * Integration tests for the 25000 Gilbert limit per planet.
 */
class GilbertCapTest extends ShipyardTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Gilberts need the lone class
        foreach (['class_explorer', 'class_raider', 'class_miner'] as $field) {
            $GLOBALS['USER'][$field] = 0;
        }
        $GLOBALS['USER']['class_lone'] = 1;
        $GLOBALS['PLANET']['gilbert'] = 24997;
    }

    public function testQueuedGilbertsReserveTheRemainingCapacity(): void
    {
        $GLOBALS['PLANET']['b_hangar_id'] = serialize([[239, 1], [202, 1], [239, 1]]);
        $before = $GLOBALS['PLANET']['metal'];
        $price = BuildFunctions::getElementPrice($GLOBALS['USER'], $GLOBALS['PLANET'], 239, false, 1);
        $this->build([239 => 100]);
        $queue = $this->queue();
        $this->assertEquals([239, 1], end($queue));
        $this->assertEquals($before - $price[901], $GLOBALS['PLANET']['metal']);
    }

    public function testFullyReservedCapacityCannotBePurchasedAgain(): void
    {
        $GLOBALS['PLANET']['b_hangar_id'] = serialize([[239, 3]]);
        $before = $GLOBALS['PLANET'];
        $this->build([239 => 1]);
        $this->assertSame($before, $GLOBALS['PLANET']);
    }

    public function testRepeatedOrdersCannotExceedTheCap(): void
    {
        $this->build([239 => 2]);
        $this->build([239 => 2]);
        $total = $GLOBALS['PLANET']['gilbert'];
        foreach ($this->queue() as [$ship, $count]) {
            if ($ship == 239) {
                $total += $count;
            }
        }
        $this->assertEquals(25000, $total);
    }

    public function testExistingOverCapUnitsCannotCauseANegativeOrder(): void
    {
        $GLOBALS['PLANET']['gilbert'] = 25001;
        $before = $GLOBALS['PLANET'];
        $this->build([239 => 1]);
        $this->assertSame($before, $GLOBALS['PLANET']);
    }

    public function testValidOrderBelowTheCapIsPreserved(): void
    {
        $GLOBALS['PLANET']['gilbert'] = 0;
        $this->build([239 => 10]);
        $this->assertEquals([[239, 10]], $this->queue());
    }
}
