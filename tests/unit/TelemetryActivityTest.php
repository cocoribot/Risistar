<?php

namespace Risistar\Tests\Unit;

use TelemetryActivity;

class TelemetryActivityTest extends UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once self::rootPath() . 'includes/classes/TelemetryActivity.class.php';
    }

    public function testOneActionCountsAsOneMinute(): void
    {
        $at = strtotime('2026-09-18 14:00 UTC');

        $this->assertSame([[$at, $at + 60]], TelemetryActivity::intervals([[$at, $at]], $at, $at + 3600));
    }

    public function testManyClicksInTenMinutesCountAsTenMinutes(): void
    {
        $at = strtotime('2026-09-18 14:00 UTC');
        $clicks = [];
        for ($i = 0; $i < 120; $i++) {
            $second = $at + (int) round($i * 600 / 119);
            $clicks[] = [$second, $second];
        }

        $intervals = TelemetryActivity::intervals(TelemetryActivity::merge(array_reverse($clicks)), $at, $at + 3600);

        $this->assertSame([[$at, $at + 660]], $intervals, '120 clicks must not count as 120 minutes.');
    }

    public function testPauseLongerThanFiveMinutesStartsANewActivePeriod(): void
    {
        $at = strtotime('2026-09-18 14:00 UTC');

        $merged = TelemetryActivity::merge([[$at + 601, $at + 601], [$at, $at], [$at + 300, $at + 300]]);

        $this->assertSame([[$at, $at + 300], [$at + 601, $at + 601]], $merged);
    }

    public function testCloseGapsShareOneBucketAndLongPausesAreIgnored(): void
    {
        $at = strtotime('2026-09-18 14:00 UTC');

        [$gaps, $last] = TelemetryActivity::addGaps([], [$at + 121, $at, $at + 60, $at + 4000], 0);

        $this->assertCount(1, $gaps, '60 s and 61 s are the same rhythm.');
        $this->assertSame(2, array_sum($gaps), 'A pause of more than an hour is a break.');
        $this->assertSame($at + 4000, $last);
    }

    public function testEachHourKeepsItsShortestAndLongestWait(): void
    {
        $at = strtotime('2026-09-18 14:00 UTC');

        [, , $waits] = TelemetryActivity::addGaps([], [$at + 10, $at + 130, $at + 430, $at + 3700, $at + 3760], $at - 50);

        $this->assertSame([14 => [60, 300], 15 => [60, 3270]], $waits, 'The long pause ends at 15:01, so it belongs to 15:00.');
    }
}
