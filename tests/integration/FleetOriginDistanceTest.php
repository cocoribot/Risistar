<?php

namespace Risistar\Tests\Integration;

use FleetFunctions;

/**
 * Integration tests for the distance used when a fleet is sent.
 */
class FleetOriginDistanceTest extends FleetDispatchTestCase
{
    public function testDistanceIsCalculatedFromTheCurrentPlanet(): void
    {
        $distance = FleetFunctions::GetTargetDistance(
            [$GLOBALS['PLANET']['galaxy'], $GLOBALS['PLANET']['system'], $GLOBALS['PLANET']['planet']],
            [$this->target['galaxy'], $this->target['system'], $this->target['planet']]
        );
        // The session still has the distance from another planet
        $this->assertNotEquals(5, $distance);
        $speed = FleetFunctions::GetGameSpeedFactor();
        $duration = FleetFunctions::GetMissionDuration(10, FleetFunctions::GetFleetMaxSpeed([202 => 1], $GLOBALS['USER']), $distance, $speed, $GLOBALS['USER']);
        $fuel = FleetFunctions::GetFleetConsumption([202 => 1], $duration, $distance, $GLOBALS['USER'], $speed);
        $before = $GLOBALS['PLANET']['deuterium'];

        $this->sendFleet();

        $this->assertEqualsWithDelta(TIMESTAMP + $duration, $this->latestFleet()['fleet_start_time'], 1);
        $this->assertEquals($before - $fuel, $GLOBALS['PLANET']['deuterium']);
    }
}
