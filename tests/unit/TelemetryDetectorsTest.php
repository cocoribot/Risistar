<?php

namespace Risistar\Tests\Unit;

use TelemetryActivity;
use TelemetryDetectors;
use TelemetrySettings;

class TelemetryDetectorsTest extends UnitTestCase
{
    private const NOW = 1789992000;

    public static function setUpBeforeClass(): void
    {
        require_once self::rootPath() . 'includes/classes/TelemetryActivity.class.php';
        require_once self::rootPath() . 'includes/classes/TelemetrySettings.class.php';
        require_once self::rootPath() . 'includes/classes/TelemetryDetectors.class.php';
        require_once self::rootPath() . 'includes/classes/TelemetryStore.class.php';
    }

    public function testUnpaidDeliveryIsFlaggedAfterTheRepaymentDeadline(): void
    {
        $send = $this->delivery(901, self::NOW - 48 * 3600, ['metal' => 4000000]);

        $finding = TelemetryDetectors::pushing([$send], 901, 902, [], TelemetrySettings::defaults(), self::NOW)[0];

        $this->assertSame('pushing.902', $finding['kind']);
        $this->assertSame('moderate', $finding['strength']);
        // 4M metal minus the 25 % allowance, valued at 4 metal for 1 deuterium.
        $this->assertSame(750000.0, $finding['metrics']['balance']['remaining']);
    }

    public function testDeliveryIsNotFlaggedBeforeTheRepaymentDeadline(): void
    {
        $send = $this->delivery(901, self::NOW - 48 * 3600 + 1, ['metal' => 4000000]);

        $this->assertSame([], TelemetryDetectors::pushing([$send], 901, 902, [], TelemetrySettings::defaults(), self::NOW));
    }

    public function testRepaymentInAnotherResourceClosesTheExchange(): void
    {
        $send = $this->delivery(901, self::NOW - 3 * 86400, ['metal' => 4000000]);
        // At 1 crystal for 1 deuterium, 1M crystal is worth more than the unpaid part.
        $repay = $this->delivery(902, self::NOW - 86400, ['crystal' => 1000000]);

        $this->assertSame([], TelemetryDetectors::pushing([$send, $repay], 901, 902, [], TelemetrySettings::defaults(), self::NOW));
    }

    public function testRepaymentOfAnOldDeliveryIsNotAGiftBack(): void
    {
        $send = $this->delivery(901, self::NOW - 7 * 86400 - 3600, ['metal' => 4000000]);
        $repay = $this->delivery(902, self::NOW - 6 * 86400, ['deuterium' => 1300000]);

        $this->assertSame([], TelemetryDetectors::pushing([$send, $repay], 901, 902, [], TelemetrySettings::defaults(), self::NOW));
    }

    public function testFleetSentWithoutArrivingIsNotADelivery(): void
    {
        $send = $this->delivery(901, self::NOW - 3 * 86400, ['metal' => 4000000]);
        $send['kind'] = 'fleet.send';

        $this->assertSame([], TelemetryDetectors::pushing([$send], 901, 902, [], TelemetrySettings::defaults(), self::NOW));
    }

    public function testPartialRepaymentWithinTheAllowanceClosesTheExchange(): void
    {
        $send = $this->delivery(901, self::NOW - 3 * 86400, ['metal' => 4000000]);
        $repay = $this->delivery(902, self::NOW - 2 * 86400, ['deuterium' => 750000]);

        $this->assertSame([], TelemetryDetectors::pushing([$send, $repay], 901, 902, [], TelemetrySettings::defaults(), self::NOW));
    }

    public function testRepaymentOfAnOlderGiftDoesNotCoverANewGift(): void
    {
        $events = [
            $this->delivery(901, self::NOW - 10 * 86400, ['deuterium' => 1000000]),
            $this->delivery(902, self::NOW - 9 * 86400, ['deuterium' => 1000000]),
            $this->delivery(901, self::NOW - 3 * 86400, ['deuterium' => 1000000]),
        ];

        $finding = TelemetryDetectors::pushing($events, 901, 902, [], TelemetrySettings::defaults(), self::NOW)[0];

        $this->assertSame('pushing.902', $finding['kind']);
        $this->assertSame(750000.0, $finding['metrics']['balance']['remaining']);
    }

    public function testTradeWherePartnerPaysFirstIsNotPushing(): void
    {
        $pay = $this->delivery(902, self::NOW - 4 * 86400, ['crystal' => 1500000]);
        $goods = $this->delivery(901, self::NOW - 3 * 86400, ['metal' => 4000000]);

        $this->assertSame([], TelemetryDetectors::pushing([$pay, $goods], 901, 902, [], TelemetrySettings::defaults(), self::NOW));
    }

    public function testSmallRestAfterAPaymentInAdvanceIsWithinTheAllowance(): void
    {
        $advance = $this->delivery(902, self::NOW - 4 * 86400, ['deuterium' => 825000]);
        // Worth 1M at 4:2:1, so 175k more than the advance: less than 25 % of the delivery.
        $goods = $this->delivery(901, self::NOW - 3 * 86400, ['metal' => 4000000]);

        $this->assertSame([], TelemetryDetectors::pushing([$advance, $goods], 901, 902, [], TelemetrySettings::defaults(), self::NOW));
    }

    public function testFairTradeLeavesNoDebtOnEitherSide(): void
    {
        // 4M metal for 1.5M crystal is inside the allowed rates, whoever looks at it.
        $goods = $this->delivery(901, self::NOW - 4 * 86400, ['metal' => 4000000]);
        $pay = $this->delivery(902, self::NOW - 3 * 86400, ['crystal' => 1500000]);

        $this->assertSame([], TelemetryDetectors::pushing([$goods, $pay], 901, 902, [], TelemetrySettings::defaults(), self::NOW));
    }

    public function testStationedShipsCountAtTheirBuildCost(): void
    {
        $ships = $this->delivery(901, self::NOW - 3 * 86400, ['ships' => [202 => 2000], 'ship_value' => ['metal' => 4000000, 'crystal' => 4000000, 'deuterium' => 0]]);

        $finding = TelemetryDetectors::pushing([$ships], 901, 902, [], TelemetrySettings::defaults(), self::NOW)[0];

        // 4M metal and 4M crystal at 4:2:1 are worth 3M deuterium, 2.25M after the allowance.
        $this->assertSame(2250000.0, $finding['metrics']['balance']['remaining']);
    }

    public function testMoonCombatBetweenThePlayersMakesTheCaseUncertain(): void
    {
        // B attacks A with a moon chance, then A pays B on B's own planet.
        $combat = $this->action(902, self::NOW - 3 * 86400, 'combat', ['planet' => 77, 'moon_chance' => 20.0]);
        $payment = $this->delivery(901, self::NOW - 3 * 86400 + 3600, ['metal' => 4000000, 'planet' => 88]);

        $finding = TelemetryDetectors::pushing([$combat, $payment], 901, 902, [], TelemetrySettings::defaults(), self::NOW)[0];

        $this->assertSame('uncertain', $finding['strength']);
    }

    public function testFastHumanClicksAreNotRegularTiming(): void
    {
        // Galaxy browsing at 1 to 8 seconds per click, 100 different random rhythms.
        foreach ([[1, 3], [2, 4], [3, 5], [2, 5], [4, 8]] as [$min, $max]) {
            for ($seed = 0; $seed < 20; $seed++) {
                mt_srand($seed);
                $this->assertSame([], $this->timing($this->randomTimes(35, $min, $max)));
            }
        }
    }

    public function testSlowHumanBrowsingIsNotRegularTiming(): void
    {
        // Reading reports and messages: 10 seconds to 2 minutes per page.
        for ($seed = 0; $seed < 50; $seed++) {
            mt_srand($seed);
            $this->assertSame([], $this->timing($this->randomTimes(200, 10, 120)), "Seed {$seed}");
        }
    }

    public function testReloadingByHandEveryFewMinutesIsNotRegularTiming(): void
    {
        for ($seed = 0; $seed < 50; $seed++) {
            mt_srand($seed);
            $this->assertSame([], $this->timing($this->randomTimes(200, 120, 300)), "Seed {$seed}");
        }
    }

    public function testActionsEveryThirtySecondsAreRegularTiming(): void
    {
        $finding = $this->timing(range(self::NOW - 10000, self::NOW - 10000 + 39 * 30, 30))[0];

        $this->assertSame('timing', $finding['kind']);
        $this->assertSame([30], $finding['metrics']['days'][0]['seconds']);
    }

    public function testLoopWithTwoWaitsIsRegularTiming(): void
    {
        // A script that waits 20 s, then 45 s, again and again.
        $times = [self::NOW - 10000];
        for ($i = 1; $i < 60; $i++) {
            $times[] = end($times) + ($i % 2 ? 20 : 45);
        }

        $finding = $this->timing($times)[0];

        $this->assertEqualsCanonicalizing([20, 45], $finding['metrics']['days'][0]['seconds']);
    }

    public function testNightlyScriptBetweenNormalPlayIsFound(): void
    {
        // 01:00 to 05:00: 12 to 30 loads an hour, never an action. The rest of the day is normal play.
        mt_srand(7);
        $days = [];
        foreach (['2026-09-17', '2026-09-18', '2026-09-19'] as $day) {
            $hours = array_fill(10, 8, $this->hour(40, busy: 12));
            for ($hour = 1; $hour < 5; $hour++) {
                $hours[$hour] = $this->hour(mt_rand(12, 30));
            }
            $days[$day] = $hours;
        }

        $finding = $this->refreshing($days)[0];

        $this->assertSame('refreshing', $finding['kind']);
        $this->assertSame(3, $finding['metrics']['days']);
        $this->assertSame(4, $finding['timeline'][0]['data']['idle_hours']);
    }

    public function testLongerIdleRunIsNeverLessSuspicious(): void
    {
        // Three whole days of loads without any action are one long run.
        $days = array_fill_keys(['2026-09-17', '2026-09-18', '2026-09-19'], array_fill(0, 24, $this->hour(20)));

        $finding = $this->refreshing($days)[0];

        $this->assertSame(3, $finding['metrics']['days']);
    }

    public function testIdleRunShowsTheWaitsBetweenLoads(): void
    {
        $night = [
            1 => $this->hour(20, shortest: 120, longest: 290),
            2 => $this->hour(18, shortest: 125, longest: 300),
            3 => $this->hour(22, shortest: 118, longest: 240),
        ];

        $run = $this->refreshing(array_fill_keys(['2026-09-17', '2026-09-18', '2026-09-19'], $night))[0]['timeline'][0]['data'];

        $this->assertSame([20, 18, 22], $run['per_hour']);
        $this->assertSame(118, $run['shortest_wait']);
        $this->assertSame(180, $run['average_wait']);
        $this->assertSame(300, $run['longest_wait']);
    }

    public function testPlanetCyclingWithoutActionIsIdleRefreshing(): void
    {
        $night = array_fill(1, 3, $this->hour(24, switches: 24));

        $finding = $this->refreshing(array_fill_keys(['2026-09-17', '2026-09-18', '2026-09-19'], $night))[0];

        $this->assertSame('refreshing', $finding['kind']);
        $this->assertSame(72, $finding['timeline'][0]['data']['switches']);
    }

    public function testAllianceWatcherIsFound(): void
    {
        $night = array_fill(1, 3, $this->hour(20, alliance: 18));

        $finding = $this->refreshing(array_fill_keys(['2026-09-17', '2026-09-18', '2026-09-19'], $night))[0];

        $this->assertSame('alliance_watch', $finding['kind']);
    }

    public function testFewAllianceViewsInAReloadRunAreNotAllianceWatching(): void
    {
        $night = array_fill(1, 3, $this->hour(20, alliance: 5));

        $finding = $this->refreshing(array_fill_keys(['2026-09-17', '2026-09-18', '2026-09-19'], $night))[0];

        $this->assertSame('refreshing', $finding['kind']);
    }

    public function testIdleRefreshOnFewerDaysThanRequiredIsNotFlagged(): void
    {
        $night = array_fill(1, 4, $this->hour(20));

        $this->assertSame([], $this->refreshing(['2026-09-18' => $night, '2026-09-19' => $night]));
    }

    public function testIdleRunAcrossMidnightIsOneRun(): void
    {
        $days = [];
        foreach (['2026-09-16', '2026-09-17', '2026-09-18', '2026-09-19'] as $day) {
            $days[$day] = [0 => $this->hour(20), 1 => $this->hour(20), 23 => $this->hour(20)];
        }

        $finding = $this->refreshing($days)[0];

        $this->assertCount(3, $finding['timeline'], '23:00 to 02:00 each night, three nights in a row.');
    }

    public function testPlayerWhoActsEveryHourIsNotIdleRefreshing(): void
    {
        $days = array_fill_keys(['2026-09-17', '2026-09-18', '2026-09-19'], array_fill(0, 20, $this->hour(30, busy: 2)));

        $this->assertSame([], $this->refreshing($days));
    }

    public function testFewQueueReloadsWhileAwayAreNotIdleRefreshing(): void
    {
        $days = array_fill_keys(['2026-09-17', '2026-09-18', '2026-09-19'], array_fill(0, 24, $this->hour(3)));

        $this->assertSame([], $this->refreshing($days));
    }

    public function testHourlyVisitsAreNotAWholeDayOfActivity(): void
    {
        $daily = [];
        foreach ([18, 19, 20, 21] as $day) {
            $start = strtotime("2026-09-{$day} UTC");
            $windows = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $windows[] = [$start + $hour * 3600, $start + $hour * 3600];
            }
            $daily[] = ['windows' => json_encode($windows)];
        }

        $findings = TelemetryDetectors::availability($daily, TelemetrySettings::defaults(), strtotime('2026-09-22 12:00 UTC'));

        $this->assertSame([], $findings);
    }

    public function testTwentyOneActiveHoursOnFourDaysIsFlagged(): void
    {
        $daily = [];
        foreach ([18, 19, 20, 21] as $day) {
            $start = strtotime("2026-09-{$day} UTC");
            $daily[] = ['windows' => json_encode([[$start, $start + 21 * 3600 - 60]])];
        }

        $finding = TelemetryDetectors::availability($daily, TelemetrySettings::defaults(), strtotime('2026-09-22 12:00 UTC'))[0];

        $this->assertSame('moderate', $finding['strength']);
        $this->assertSame(21 * 3600, $finding['metrics']['days'][0]['active_seconds']);
    }

    private function delivery(int $sender, int $at, array $resources): array
    {
        return [
            'actor' => $sender,
            'target' => $sender === 901 ? 902 : 901,
            'at' => $at,
            'kind' => 'delivery',
            'data' => $resources,
            'request_id' => (string) $at,
            'interactive' => false,
        ];
    }

    private function action(int $actor, int $at, string $kind, array $data = []): array
    {
        return ['actor' => $actor, 'at' => $at, 'kind' => $kind, 'data' => $data, 'request_id' => (string) $at, 'interactive' => $kind !== 'combat'];
    }

    /** Runs the timing check on one day built from these request times. */
    private function timing(array $times): array
    {
        [$gaps] = TelemetryActivity::addGaps([], $times, 0);
        $day = ['day' => gmdate('Y-m-d', self::NOW - 10000), 'gaps' => json_encode($gaps, JSON_FORCE_OBJECT)];

        return TelemetryDetectors::automation([$day], TelemetrySettings::defaults(), self::NOW);
    }

    private function randomTimes(int $count, int $min, int $max): array
    {
        $times = [self::NOW - 50000];
        for ($i = 1; $i < $count; $i++) {
            $times[] = end($times) + mt_rand($min, $max);
        }
        return $times;
    }

    /** One hour of a daily row: loads without action and what they were, other requests, and waits. */
    private function hour(int $loads, int $busy = 0, int $switches = 0, int $alliance = 0, int $shortest = 0, int $longest = 0): array
    {
        return [$loads, $switches, $busy, $alliance, $shortest, $longest];
    }

    /** Runs the idle refresh check on days of hourly counts. */
    private function refreshing(array $days): array
    {
        $daily = [];
        foreach ($days as $day => $hours) {
            $daily[] = ['day' => $day, 'hours' => json_encode($hours)];
        }

        return TelemetryDetectors::refreshing($daily, TelemetrySettings::defaults(), strtotime('2026-09-20 12:00 UTC'));
    }
}
