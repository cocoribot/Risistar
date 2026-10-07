<?php

namespace Risistar\Tests\Integration;

use BuildFunctions;
use Config;
use ReflectionProperty;

/**
 * Integration tests for the shipyard queue limit (max_elements_ships).
 */
class ShipyardQueueLimitTest extends ShipyardTestCase
{
    public function testOnePostCannotAddMoreEntriesThanThereAreSlots(): void
    {
        $limit = (int) Config::get()->max_elements_ships;
        $this->assertGreaterThan(0, $limit);
        $GLOBALS['PLANET']['b_hangar_id'] = serialize(array_fill(0, $limit - 1, [202, 1]));
        $before = $GLOBALS['PLANET']['metal'];
        $price = BuildFunctions::getElementPrice($GLOBALS['USER'], $GLOBALS['PLANET'], 202, false, 1);
        $this->build([202 => 1, 203 => 1]);
        $this->assertCount($limit, $this->queue());
        $this->assertEquals($before - $price[901], $GLOBALS['PLANET']['metal']);
    }

    public function testFullQueueDoesNotChargeForAnotherEntry(): void
    {
        $limit = (int) Config::get()->max_elements_ships;
        $GLOBALS['PLANET']['b_hangar_id'] = serialize(array_fill(0, $limit, [202, 1]));
        $before = $GLOBALS['PLANET'];
        $this->build([203 => 1]);
        $this->assertSame($before, $GLOBALS['PLANET']);
    }

    public function testInvalidOrderDoesNotConsumeTheLastSlot(): void
    {
        $limit = (int) Config::get()->max_elements_ships;
        $GLOBALS['PLANET']['b_hangar_id'] = serialize(array_fill(0, $limit - 1, [202, 1]));
        $this->build([999999 => 1, 202 => 0, 203 => 1]);
        $queue = $this->queue();
        $this->assertCount($limit, $queue);
        $this->assertEquals(203, end($queue)[0]);
    }

    public function testZeroLimitKeepsUnlimitedQueues(): void
    {
        $config = Config::get();
        $property = new ReflectionProperty(Config::class, 'configData');
        $original = $property->getValue($config);
        $unlimited = $original;
        $unlimited['max_elements_ships'] = 0;
        $property->setValue($config, $unlimited);
        try {
            $GLOBALS['PLANET']['b_hangar_id'] = serialize(array_fill(0, 20, [202, 1]));
            $this->build([202 => 1, 203 => 1]);
            $this->assertCount(22, $this->queue());
        } finally {
            $property->setValue($config, $original);
        }
    }
}
