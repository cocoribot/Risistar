<?php

namespace Risistar\Tests\Unit;

use DateTimeZone;
use PlayerTelemetry;
use TelemetryPresentation;

class TelemetryPresentationTest extends UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        foreach (['TelemetryActivity', 'TelemetrySettings', 'TelemetryPresentation', 'PlayerTelemetry'] as $class) {
            require_once self::rootPath() . 'includes/classes/' . $class . '.class.php';
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['LNG']);
    }

    public function testActivityAcrossMidnightIsSplitBetweenBothDays(): void
    {
        $view = new TelemetryPresentation(new DateTimeZone('Europe/Paris'));
        $from = strtotime('2026-09-18 00:00 Europe/Paris');
        $at = $from + 86370;

        $activity = $view->activity([['windows' => json_encode([[$at, $at]])]], $from, $from + 2 * 86400 - 1);

        $this->assertSame(60, $activity['total_seconds']);
        foreach ($activity['days'] as $day) {
            $this->assertSame(30, $day['seconds']);
            $this->assertEqualsWithDelta(30 / 86400 * 100, $day['bars'][0]['width'], 0.000001);
        }
    }

    public function testClockChangeDoesNotShiftTheHeatmap(): void
    {
        $view = new TelemetryPresentation(new DateTimeZone('Europe/Paris'));
        // 25 October 2026: 03:00 summer time becomes 02:00 winter time.
        $at = strtotime('2026-10-25 00:15 UTC');

        $activity = $view->activity(
            [['windows' => json_encode([[$at, $at], [$at + 3600, $at + 3600]])]],
            strtotime('2026-10-25 00:00 Europe/Paris'),
            strtotime('2026-10-25 23:59 Europe/Paris')
        );

        $bars = $activity['days']['2026-10-25']['bars'];
        $this->assertSame(120, $activity['total_seconds']);
        $this->assertCount(2, $bars);
        $this->assertSame($bars[0]['start'], $bars[1]['start'], 'Both actions happened at 02:15 local time.');
        $this->assertEqualsWithDelta(60 / 86400 * 100, $bars[0]['width'], 0.000001);
    }

    public function testDurationsAreWrittenInHoursMinutesAndSeconds(): void
    {
        $this->assertSame('20 h 29 min', TelemetryPresentation::duration(73740));
        $this->assertSame('1 min 5 s', TelemetryPresentation::duration(65));
        $this->assertSame('0 s', TelemetryPresentation::duration(0));
    }

    public function testIdleRunIsShownInReadingOrder(): void
    {
        $LNG = [];
        require self::rootPath() . 'language/en/ADMIN.php';
        $GLOBALS['LNG'] = $LNG;
        $view = new TelemetryPresentation(new DateTimeZone('UTC'));
        $from = strtotime('2026-09-18 01:00 UTC');
        // Key order as the JSON column gives it back.
        $run = ['to' => $from + 7200, 'from' => $from, 'loads' => 40, 'per_hour' => [21, 19], 'longest_wait' => 300, 'shortest_wait' => 118];

        $rows = $view->rows($run);

        $this->assertSame(['Start', 'End', 'Loads without any action', 'Loads per hour', 'Shortest wait between loads', 'Longest wait between loads'], array_column($rows, 'label'));
        $this->assertSame('21 · 19', $rows[3]['value']);
        $this->assertSame('1 min 58 s', $rows[4]['value']);
    }

    public function testClientProfileIsStoredAsCodesAndTranslatedWhenShown(): void
    {
        $agent = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36';
        $LNG = [];
        require self::rootPath() . 'language/fr/ADMIN.php';
        $GLOBALS['LNG'] = $LNG;

        $this->assertSame('Chrome · Android · tablet', PlayerTelemetry::client($agent));
        $this->assertSame('other · other · unknown', PlayerTelemetry::client('curl/8.5'));
        $this->assertSame('Chrome · Android · tablette', TelemetryPresentation::client(PlayerTelemetry::client($agent)));
    }
}
