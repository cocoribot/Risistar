<?php

namespace Risistar\Tests\Integration;

use PlayerTelemetry;
use TelemetryConnection;
use TelemetryCronjob;
use TelemetryDetectors;
use TelemetryReview;
use TelemetrySettings;
use TelemetryStore;

require_once __DIR__ . '/TelemetryTestCase.php';

/**
 * Integration tests for telemetry storage: schema upgrade, retention, quota and the analysis cron.
 */
class TelemetryStoreTest extends TelemetryTestCase
{
    public function testMigrationCreatesPrefixedTablesAndRegistersTheCron(): void
    {
        $pdo = self::$db->getHandle();
        $prefix = 'telemetry_upgrade_' . bin2hex(random_bytes(4)) . '_';

        try {
            $pdo->exec('CREATE TABLE `' . $prefix . 'config` (uni INT PRIMARY KEY) ENGINE=InnoDB');
            $pdo->exec('INSERT INTO `' . $prefix . 'config` VALUES (1)');
            $pdo->exec('CREATE TABLE `' . $prefix . 'cronjobs` LIKE `' . DB_PREFIX . 'cronjobs`');
            $sql = str_replace('%PREFIX%', $prefix, file_get_contents(ROOT_PATH . 'install/migrations/migration_8.sql'));
            foreach (array_filter(explode(";\n", $sql)) as $query) {
                $pdo->exec(trim($query));
            }

            $this->assertSame('{}', $pdo->query('SELECT telemetry_settings FROM `' . $prefix . 'config`')->fetchColumn());
            $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM `' . $prefix . "cronjobs` WHERE class='TelemetryCronjob'")->fetchColumn());
            foreach (['daily', 'events', 'network', 'pairs', 'warnings', 'audit'] as $table) {
                $this->assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM `' . $prefix . 'telemetry_' . $table . '`')->fetchColumn());
            }
        } finally {
            foreach (['telemetry_audit', 'telemetry_warnings', 'telemetry_pairs', 'telemetry_network', 'telemetry_events', 'telemetry_daily', 'cronjobs', 'config'] as $table) {
                $pdo->exec('DROP TABLE IF EXISTS `' . $prefix . $table . '`');
            }
        }
    }

    public function testSharedDatabaseUsesItsOwnConnectionOutsideGameTransactions(): void
    {
        require (defined('DATABASE_CONFIG_FILE') ? DATABASE_CONFIG_FILE : ROOT_PATH . 'includes/config.php');
        TelemetryConnection::configure($database, []);

        try {
            $pdo = TelemetryConnection::open();

            $this->assertNotSame(self::$db->getHandle(), $pdo);
            $this->assertSame($database['databasename'], $pdo->query('SELECT DATABASE()')->fetchColumn());
            $this->assertStringContainsString($database['tableprefix'] . 'telemetry_events', TelemetryConnection::tables()['%%TELEMETRY_EVENTS%%']);

            // A telemetry rollback must never touch the game transaction.
            self::$db->beginTransaction();
            $pdo->beginTransaction();
            $pdo->rollBack();
            $this->assertTrue(self::$db->inTransaction());
            self::$db->rollBack();
        } finally {
            TelemetryConnection::configure($database, $telemetry ?? []);
        }
    }

    public function testOnlyPlayerActionsCountAsActivityTime(): void
    {
        $at = strtotime('today UTC') - 1;
        $login = ['ip' => '192.0.2.55', 'client' => 'Firefox · Linux · desktop'] + $this->event(900001, $at, 'login');
        $send = ['ip' => '2001:db8::55', 'client' => 'Safari · iOS · mobile'] + $this->event(900001, $at + 2, 'fleet.send');
        $delivery = $this->event(900001, $at, 'delivery', 900002, ['metal' => 1000]);

        $this->store->write([$login, $send, $delivery]);

        // The two actions are on both sides of midnight: one activity row per day,
        // and none for the player who received the delivery.
        $this->assertCount(2, $this->store->daily(1, 900001, $at - 3600));
        $this->assertSame([], $this->store->daily(1, 900002, 0));

        $network = $this->store->network(1, 900001, $at - 3600, time());
        $this->assertSame(2, (int) $network['ips']);
        $this->assertSame(2, (int) $network['clients']);
    }

    public function testCleanupKeepsDeliveriesForThreePushPeriods(): void
    {
        $now = time();
        $this->store->write([
            $this->event(900001, $now - 22 * 86400, 'delivery', 900002, ['metal' => 1000]),
            $this->event(900001, $now - 8 * 86400, 'delivery', 900002, ['metal' => 4000000]),
            $this->event(900002, $now - 6 * 86400, 'delivery', 900001, ['deuterium' => 1300000]),
        ]);

        $health = $this->store->maintenance(TelemetrySettings::deliveryDays($this->settings), 72, $now);

        $this->assertGreaterThan(0, $health['allocated_mb']);
        // The delivery of 8 days ago is still there, so its repayment two days later is still seen.
        $pair = $this->store->events(1, 900001, 0, 100, 900002);
        $this->assertCount(2, $pair);
        $this->assertSame([], TelemetryDetectors::pushing($pair, 900001, 900002, [], $this->settings, $now));
    }

    public function testFullSharedDatabaseStopsCollection(): void
    {
        $this->store->maintenance(TelemetrySettings::deliveryDays($this->settings), 72, time());
        TelemetryStore::health(['allocated_mb' => TelemetrySettings::BUDGET_MB, 'suspended' => true]);

        $written = $this->store->write([
            $this->event(900001, time(), 'login'),
            $this->event(900001, time(), 'delivery', 900002, ['metal' => 1000]),
        ]);

        // A dedicated database keeps activity time; a shared one leaves the space to the game.
        $this->assertSame(!TelemetryConnection::shared(), $written);
        $this->assertSame([], $this->storedEvents(900001));
        $this->assertCount(TelemetryConnection::shared() ? 0 : 1, $this->store->daily(1, 900001, 0));
    }

    public function testNextCleanupResumesCollectionOnceThereIsRoomAgain(): void
    {
        TelemetryStore::health(['allocated_mb' => TelemetrySettings::BUDGET_MB, 'suspended' => true]);

        $this->store->maintenance(TelemetrySettings::deliveryDays($this->settings), 72, time());

        $this->assertFalse(TelemetryStore::health()['suspended']);
    }

    public function testCronStoresWhereTheAnalysisStopped(): void
    {
        (new TelemetryCronjob())->run();

        $analysis = TelemetryStore::health()['analysis_1'];

        $this->assertEqualsWithDelta(time(), $analysis['at'], 5);
        $this->assertArrayHasKey('cursor', $analysis);
    }

    public function testFailedAnalysisDoesNotPauseCollection(): void
    {
        $table = trim(TelemetryConnection::tables()['%%TELEMETRY_WARNINGS%%'], '`');
        $this->store->query("RENAME TABLE `{$table}` TO `{$table}_off`");
        try {
            (new TelemetryCronjob())->run();
            $this->fail('The analysis should fail without its table.');
        } catch (\PDOException $e) {
        } finally {
            $this->store->query("RENAME TABLE `{$table}_off` TO `{$table}`");
        }

        $this->resetCollector();
        PlayerTelemetry::record(900001, 1, 'interaction', 0, 0, [], true);
        PlayerTelemetry::flush();

        $this->assertSame(['interaction' => 1], $this->store->actionCounts(1, 900001, time() - 60, time()));
    }

    public function testAnalysisResumesAfterTheLastCheckedAccount(): void
    {
        if (self::$db->selectSingle('SELECT COUNT(*) AS total FROM %%USERS%% WHERE universe = 1;', [], 'total') < 2) {
            $this->markTestSkipped('Need at least two users in the database.');
        }
        $review = new TelemetryReview($this->store);
        $review->batch = 1;

        $first = $review->evaluate(1, $this->settings, time());
        $second = $review->evaluate(1, $this->settings, time(), $first['cursor'], $first['pair_a'], $first['pair_b']);

        $this->assertTrue($first['incomplete']);
        $this->assertGreaterThan($first['cursor'], $second['cursor']);
    }

    public function testRepaidExchangeKeepsItsOpeningEvidenceAndModeratorDecision(): void
    {
        $now = time();
        $review = new TelemetryReview($this->store);
        $this->store->write([$this->event(900001, $now - 3 * 86400, 'delivery', 900002, ['metal' => 4000000])]);
        $review->evaluate(1, $this->settings, $now, 900000, 900000);
        $id = (int) $this->store->query('SELECT id FROM %%TELEMETRY_WARNINGS%% WHERE actor = 900001 AND other = 900002')->fetchColumn();
        $this->assertGreaterThan(0, $id, 'Four million metal sent three days ago and never repaid opens a case.');

        $review->decide(1, 1, $id, 'follow_up', 'Asked both players', $now);
        $opening = $review->evidence(1, $id)['evidence'];
        $this->store->write([$this->event(900002, $now, 'delivery', 900001, ['deuterium' => 1000000])]);
        $review->evaluate(1, $this->settings, $now + 1, 900000, 900000);

        $case = $review->evidence(1, $id);
        $this->assertFalse($case['latest_evidence']['matches']);
        $this->assertSame($opening, $case['evidence']);
        $this->assertSame('follow_up', $case['status']);

        $this->store->query('DELETE FROM %%TELEMETRY_AUDIT%% WHERE warning_id = ?', [$id]);
    }

    public function testWarnedPairIsStillCheckedAfterItsDeliveriesExpire(): void
    {
        $now = time();
        $review = new TelemetryReview($this->store);
        $this->store->write([$this->event(900001, $now - 3 * 86400, 'delivery', 900002, ['metal' => 4000000])]);
        $review->evaluate(1, $this->settings, $now, 900000, 900000);
        $id = (int) $this->store->query('SELECT id FROM %%TELEMETRY_WARNINGS%% WHERE actor = 900001 AND other = 900002')->fetchColumn();

        // One month later this pair has no recent delivery, but its open case must still be checked.
        $later = $now + 30 * 86400;
        $review->evaluate(1, $this->settings, $later, 900000, 900000);

        $this->assertSame($later, $review->evidence(1, $id)['latest_evidence']['evaluated_at']);
    }

    public function testDismissedCaseOpensAgainWhenANewGiftAppears(): void
    {
        $now = time();
        $review = new TelemetryReview($this->store);
        $this->store->write([$this->event(900001, $now - 20 * 86400, 'delivery', 900002, ['metal' => 4000000])]);
        $review->evaluate(1, $this->settings, $now - 17 * 86400, 900000, 900000);
        $id = (int) $this->store->query('SELECT id FROM %%TELEMETRY_WARNINGS%% WHERE actor = 900001 AND other = 900002')->fetchColumn();
        $review->decide(1, 1, $id, 'dismissed', 'Approved war loan', $now - 17 * 86400);
        // A week later the loan is too old to count: the case no longer matches.
        $review->evaluate(1, $this->settings, $now - 10 * 86400, 900000, 900000);

        $this->store->write([$this->event(900001, $now - 3 * 86400, 'delivery', 900002, ['metal' => 4000000])]);
        $review->evaluate(1, $this->settings, $now, 900000, 900000);

        $case = $review->evidence(1, $id);
        $this->assertSame('open', $case['status']);
        $reopening = json_decode(end($case['review_history'])['data'], true);
        $this->assertSame(['dismissed', 'open'], [$reopening['before'], $reopening['after']]);
    }

    public function testAllTimeExchangeOutlivesTheDeliveries(): void
    {
        $now = time();
        $review = new TelemetryReview($this->store);
        $this->store->write([
            $this->event(900002, $now - 60 * 86400, 'delivery', 900001, ['deuterium' => 500000]),
            $this->event(900001, $now - 3 * 86400, 'delivery', 900002, ['metal' => 4000000]),
        ]);
        // Old deliveries are removed; the totals per pair are not.
        $this->store->maintenance(TelemetrySettings::deliveryDays($this->settings), 72, $now);

        $review->evaluate(1, $this->settings, $now, 900000, 900000);

        $id = (int) $this->store->query('SELECT id FROM %%TELEMETRY_WARNINGS%% WHERE actor = 900001 AND other = 900002')->fetchColumn();
        $lifetime = $review->evidence(1, $id)['evidence']['metrics']['lifetime'];
        $this->assertEquals(4000000, $lifetime['sent']['metal']);
        $this->assertEquals(500000, $lifetime['returned']['deuterium']);
        $this->assertSame($now - 60 * 86400, $lifetime['since']);
    }

    public function testAddressesAreKeptForTheConfiguredHours(): void
    {
        $now = time();
        $old = ['ip' => '192.0.2.1'] + $this->event(900001, $now - 73 * 3600, 'interaction');
        $recent = ['ip' => '192.0.2.2'] + $this->event(900001, $now - 3600, 'interaction');
        $this->store->write([$old, $recent]);

        $this->store->maintenance(TelemetrySettings::deliveryDays($this->settings), 72, $now);

        $network = $this->store->network(1, 900001, $now - 5 * 86400, $now);
        $this->assertSame(['192.0.2.2'], array_column($network['rows'], 'ip'));
    }

    public function testRecentDecisionKeepsAnOldCase(): void
    {
        $id = $this->caseSeenLongAgo();
        (new TelemetryReview($this->store))->decide(1, 1, $id, 'dismissed', 'Old alliance loan', time());

        $this->store->maintenance(TelemetrySettings::deliveryDays($this->settings), 72, time());

        $case = (new TelemetryReview($this->store))->evidence(1, $id);
        $this->assertSame('dismissed', $case['status']);
        $this->assertCount(1, $case['review_history']);
    }

    public function testOldDismissedCaseIsRemoved(): void
    {
        $id = $this->caseSeenLongAgo();
        (new TelemetryReview($this->store))->decide(1, 1, $id, 'dismissed', 'Old alliance loan', time() - 95 * 86400);

        $this->store->maintenance(TelemetrySettings::deliveryDays($this->settings), 72, time());

        $this->assertFalse($this->store->query('SELECT id FROM %%TELEMETRY_WARNINGS%% WHERE id = ?', [$id])->fetchColumn());
    }

    public function testBrokenStatusFileKeepsCollectionPausedUntilTheNextMeasure(): void
    {
        $directory = (defined('CACHE_PATH') ? CACHE_PATH : ROOT_PATH . 'cache/') . 'telemetry/';
        file_put_contents($directory . 'health.json', '{"suspended":tr');

        $this->assertTrue(TelemetryStore::health()['suspended']);
        $this->store->write([$this->event(900001, time(), 'delivery', 900002, ['metal' => 1000])]);
        $this->assertSame([], $this->storedEvents(900001));

        $this->store->maintenance(TelemetrySettings::deliveryDays($this->settings), 72, time());
        $this->assertFalse(TelemetryStore::health()['suspended']);
    }

    /** A pushing case whose evidence was last seen 100 days ago. */
    private function caseSeenLongAgo(): int
    {
        $then = time() - 100 * 86400;
        $this->store->write([$this->event(900001, $then - 3 * 86400, 'delivery', 900002, ['metal' => 4000000])]);
        (new TelemetryReview($this->store))->evaluate(1, $this->settings, $then, 900000, 900000);

        return (int) $this->store->query('SELECT id FROM %%TELEMETRY_WARNINGS%% WHERE actor = 900001 AND other = 900002')->fetchColumn();
    }
}
