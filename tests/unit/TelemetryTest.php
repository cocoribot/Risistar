<?php

namespace Risistar\Tests\Unit;

use DateTimeZone;
use TelemetryActivity;
use TelemetryDetectors;
use TelemetryPresentation;
use TelemetrySettings;

final class TelemetryTest extends UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        foreach (['TelemetryActivity', 'TelemetrySettings', 'TelemetryDetectors', 'TelemetryPresentation'] as $class) {
            require_once self::rootPath() . 'includes/classes/' . $class . '.class.php';
        }
    }

    private function event(int $actor, int $at, string $kind, array $data = []): array
    {
        return ['actor' => $actor, 'at' => $at, 'kind' => $kind, 'data' => $data,
            'request_id' => (string)$at, 'interactive' => $kind !== 'delivery'];
    }

    public function testSettingsAndAdminLabelsAreTranslatedInFrenchAndEnglish(): void
    {
        global $LNG;
        $original = $LNG;
        try {
            foreach (['fr', 'en'] as $language) {
                $LNG = [];
                require self::rootPath() . 'language/' . $language . '/ADMIN.php';
                $this->assertArrayHasKey('modul_43', $LNG);
                foreach (TelemetrySettings::definitions() as $key => $definition) {
                    $this->assertNotSame($key, $definition[5]);
                    $this->assertNotEmpty($definition[5]);
                }
                $this->assertSame(TelemetrySettings::defaults(), TelemetrySettings::validate(TelemetrySettings::defaults()));
                $bad = TelemetrySettings::defaults();
                $bad['rate_metal_min'] = 5;
                try {
                    TelemetrySettings::validate($bad);
                    $this->fail('Reversed rate range was accepted.');
                } catch (\InvalidArgumentException $e) {
                    $this->assertSame($LNG['telemetry_invalid_rates'], $e->getMessage());
                }
            }
        } finally {
            $LNG = $original;
        }
    }

    public function testStorageUsesAllUniversesAndKeepsTheirLongestHistory(): void
    {
        $first = TelemetrySettings::defaults();
        $second = array_replace($first, ['budget_mb' => 1200, 'reserve_mb' => 500, 'push_days' => 20, 'event_days' => 30, 'cleanup_rows' => 500]);
        $combined = TelemetrySettings::storage([$first, $second]);
        $this->assertSame(1200, $combined['budget_mb']);
        $this->assertSame(500, $combined['reserve_mb']);
        $this->assertSame(30, $combined['event_days']);
        $this->assertSame(500, $combined['cleanup_rows']);
        $this->assertSame(40, TelemetrySettings::deliveryDays($combined));
    }

    public function testActivityMergesTimeRatherThanAddingActions(): void
    {
        $at = strtotime('2026-09-18 14:00 UTC');
        $this->assertSame([[$at, $at + 60]], TelemetryActivity::intervals([[$at, $at]], $at, $at + 3600));
        $burst = [];
        for ($i = 0; $i < 120; ++$i) {
            $t = $at + (int)round($i * 600 / 119);
            $burst[] = [$t, $t];
        }
        $this->assertSame([[$at, $at + 660]], TelemetryActivity::intervals(TelemetryActivity::merge(array_reverse($burst)), $at, $at + 3600));
        $this->assertSame([[$at, $at + 300], [$at + 601, $at + 601]], TelemetryActivity::merge([[$at + 601, $at + 601], [$at, $at], [$at + 300, $at + 300]]));
    }

    public function testIndependentRateCornersAndOldRepaymentContext(): void
    {
        $now = 1789992000;
        $s = TelemetrySettings::defaults();
        $send = $this->event(901, $now - 3 * 86400, 'delivery', ['metal' => 4000000]);
        $repay = $this->event(902, $now - 86400, 'delivery', ['crystal' => 1000000]);
        $this->assertSame([], TelemetryDetectors::pushing([$send, $repay], 901, 902, [], $s, $now));
        $send['at'] = $now - 7 * 86400 - 3600;
        $repay['at'] = $now - 6 * 86400;
        $repay['data'] = ['deuterium' => 1300000];
        $this->assertSame([], TelemetryDetectors::pushing([$send, $repay], 901, 902, [], $s, $now));
    }

    public function testOnlyOverdueUnbalancedDeliveriesCreateWarnings(): void
    {
        $now = 1789992000;
        $s = TelemetrySettings::defaults();
        $send = $this->event(901, $now - 48 * 3600 + 1, 'delivery', ['metal' => 4000000]);
        $this->assertSame([], TelemetryDetectors::pushing([$send], 901, 902, [], $s, $now));
        --$send['at'];
        $finding = TelemetryDetectors::pushing([$send], 901, 902, [], $s, $now)[0];
        $this->assertSame('moderate', $finding['strength']);
        $this->assertSame(750000.0, $finding['metrics']['balance']['remaining']);
        $repay = $this->event(902, $now - 1, 'delivery', ['deuterium' => 750000]);
        $this->assertSame([], TelemetryDetectors::pushing([$send, $repay], 901, 902, [], $s, $now));
        $send['kind'] = 'fleet.send';
        $this->assertSame([], TelemetryDetectors::pushing([$send], 901, 902, [], $s, $now));
    }

    public function testFastClicksAndIrregularSpyRoundsDoNotSuggestAutomation(): void
    {
        $now = 1789992000;
        $s = TelemetrySettings::defaults();
        foreach ([[1, 3], [2, 4], [3, 5], [2, 5], [4, 8]] as [$min, $max]) {
            for ($seed = 0; $seed < 20; ++$seed) {
                mt_srand($seed);
                $at = $now - 50000;
                $events = [];
                for ($i = 0; $i < 35; ++$i) {
                    $events[] = $this->event(1, $at, 'galaxy.view');
                    $at += mt_rand($min, $max);
                }
                $this->assertNotContains('timing', array_column(TelemetryDetectors::automation($events, $s, $now), 'kind'));
            }
        }
        $events = [];
        $at = $now - 50000;
        for ($i = 0; $i < 40; ++$i) {
            $events[] = $this->event(1, $at, $i % 2 ? 'fleet.send' : 'galaxy.view', ['mission' => 6]);
            $at += [23, 71, 399, 51, 193, 127, 283][$i % 7];
        }
        $this->assertNotContains('workflow', array_column(TelemetryDetectors::automation($events, $s, $now), 'kind'));
        foreach ($events as $i => &$event) {
            $event['at'] = $now - 10000 + $i * 30;
        }
        unset($event);
        $kinds = array_column(TelemetryDetectors::automation($events, $s, $now), 'kind');
        $this->assertContains('timing', $kinds);
        $this->assertContains('workflow', $kinds);
    }

    public function testHourlyRefreshesAreNotWholeDaysOfActivity(): void
    {
        $now = strtotime('2026-09-22 12:00 UTC');
        $daily = [];
        for ($day = 18; $day <= 21; ++$day) {
            $at = strtotime('2026-09-' . $day . ' UTC');
            $windows = [];
            for ($hour = 0; $hour < 24; ++$hour) {
                $windows[] = [$at + $hour * 3600, $at + $hour * 3600];
            }
            $daily[] = ['windows' => json_encode($windows)];
        }
        $this->assertSame([], TelemetryDetectors::availability($daily, TelemetrySettings::defaults(), $now));
        foreach ($daily as $i => &$day) {
            $at = strtotime('2026-09-' . (18 + $i) . ' UTC');
            $day['windows'] = json_encode([[$at, $at + 21 * 3600 - 60]]);
        }
        unset($day);
        $finding = TelemetryDetectors::availability($daily, TelemetrySettings::defaults(), $now)[0];
        $this->assertSame('moderate', $finding['strength']);
        $this->assertSame(21 * 3600, $finding['metrics']['days'][0]['active_seconds']);
    }

    public function testHeatmapUsesExactMinutesIncludingMidnightAndClockChanges(): void
    {
        $view = new TelemetryPresentation(new DateTimeZone('Europe/Paris'));
        $from = strtotime('2026-09-18 00:00 Europe/Paris');
        $at = $from + 86370;
        $activity = $view->activity([['windows' => json_encode([[$at, $at]])]], $from, $from + 2 * 86400 - 1);
        $this->assertSame(60, $activity['total_seconds']);
        foreach ($activity['days'] as $day) {
            $this->assertSame(30, $day['seconds']);
            $this->assertEqualsWithDelta(30 / 864, $day['bars'][0]['width'], 0.000001);
        }
        $at = strtotime('2026-10-25 00:15 UTC');
        $activity = $view->activity([['windows' => json_encode([[$at, $at], [$at + 3600, $at + 3600]])]],
            strtotime('2026-10-25 00:00 Europe/Paris'), strtotime('2026-10-25 23:59 Europe/Paris'));
        $this->assertSame(120, $activity['total_seconds']);
        $bars = $activity['days']['2026-10-25']['bars'];
        $this->assertCount(2, $bars);
        $this->assertSame($bars[0]['start'], $bars[1]['start']);
        $this->assertEqualsWithDelta(60 / 864, $bars[0]['width'], 0.000001);
        $this->assertSame('20 h 29 min', TelemetryPresentation::duration(73740));
        $this->assertSame('1 min 5 s', TelemetryPresentation::duration(65));
        $this->assertSame('0 s', TelemetryPresentation::duration(0));
    }
}
