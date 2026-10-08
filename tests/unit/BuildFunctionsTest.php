<?php

namespace Risistar\Tests\Unit;

use BuildFunctions;

class BuildFunctionsTest extends UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootConstants();
        require_once self::rootPath() . 'includes/classes/class.BuildFunctions.php';

        $GLOBALS['resource'] = [
            901 => 'metal',
            902 => 'crystal',
            903 => 'deuterium',
            921 => 'darkmatter',
            231 => 'space_shipyard',
            239 => 'gilbert',
        ];
        $GLOBALS['reslist']['one'] = [231];
    }

    public function testGetBonusListReturnsArray()
    {
        $bonuses = BuildFunctions::getBonusList();
        $this->assertIsArray($bonuses);
        $this->assertContainsEquals('Attack', $bonuses);
        $this->assertContainsEquals('Defensive', $bonuses);
        $this->assertContainsEquals('Shield', $bonuses);
        $this->assertContainsEquals('Resource', $bonuses);
        $this->assertContainsEquals('FlyTime', $bonuses);
    }

    public function testGetRestPriceCalculatesMissingResources()
    {
        $dummyUser = ['darkmatter' => 0];
        $dummyPlanet = [
            'metal' => 500,
            'crystal' => 200,
            'deuterium' => 0,
        ];
        $elementPrice = [
            901 => 1000,
            902 => 100,
            903 => 50,
        ];

        $restPrice = BuildFunctions::getRestPrice($dummyUser, $dummyPlanet, 1, $elementPrice);

        $this->assertEquals(500, $restPrice[901]);
        $this->assertEquals(0, $restPrice[902]);
        $this->assertEquals(50, $restPrice[903]);
    }

    public function testDestroyQueueShowsTheLevelBeingDemolished(): void
    {
        $this->assertSame(10, BuildFunctions::displayedBuildingQueueLevel(11, 'destroy'));
        $this->assertSame(9, BuildFunctions::buildingLevelAfterQueueEntry(11, 'destroy'));
    }

    public function testBuildQueueKeepsTheTargetLevel(): void
    {
        $this->assertSame(11, BuildFunctions::displayedBuildingQueueLevel(11, 'build'));
        $this->assertSame(11, BuildFunctions::buildingLevelAfterQueueEntry(11, 'build'));
    }

    public function testQueuedGilbertsCountTowardTheLimit(): void
    {
        $planet = $this->planet(['gilbert' => 24997, 'b_hangar_id' => serialize([[239, 1], [202, 1], [239, 1]])]);

        $this->assertSame(1, $this->maxElements($planet, 239));
    }

    public function testPlanetOverTheGilbertLimitGetsNoOrder(): void
    {
        $this->assertSame(0, $this->maxElements($this->planet(['gilbert' => 25001]), 239));
    }

    public function testUniqueUnitOnThePlanetGetsNoOrder(): void
    {
        $this->assertSame(0, $this->maxElements($this->planet(['space_shipyard' => 1]), 231));
    }

    private function planet(array $fields): array
    {
        return $fields + ['metal' => 1000000, 'gilbert' => 0, 'space_shipyard' => 0, 'b_hangar' => 0, 'b_hangar_id' => ''];
    }

    private function maxElements(array $planet, int $element): int
    {
        return (int) BuildFunctions::getMaxConstructibleElements(['darkmatter' => 0], $planet, $element, [901 => 1]);
    }
}
