<?php

namespace Risistar\Tests\Integration;

/**
 * Integration tests for units a planet can only have once.
 */
class ShipyardUniqueUnitTest extends ShipyardTestCase
{
    public function testExistingUniqueUnitCannotBePurchasedAgain(): void
    {
        $GLOBALS['PLANET']['space_shipyard'] = 1;
        $before = $GLOBALS['PLANET'];
        $this->build([231 => 1]);
        $this->assertSame($before, $GLOBALS['PLANET']);
    }

    public function testQueuedUniqueUnitCannotBePurchasedAgain(): void
    {
        $GLOBALS['PLANET']['space_shipyard'] = 0;
        $GLOBALS['PLANET']['b_hangar_id'] = serialize([[231, 1]]);
        $before = $GLOBALS['PLANET'];
        $this->build([231 => 1]);
        $this->assertSame($before, $GLOBALS['PLANET']);
    }

    public function testFirstPurchaseQueuesOnlyOneUniqueUnit(): void
    {
        $GLOBALS['PLANET']['space_shipyard'] = 0;
        $this->build([231 => 100]);
        $this->assertEquals([[231, 1]], $this->queue());
    }
}
