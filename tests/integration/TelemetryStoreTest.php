<?php

namespace Risistar\Tests\Integration;

use Config;
use PDO;
use PlayerTelemetry;
use TelemetryConnection;
use TelemetryCronjob;
use TelemetryDetectors;
use TelemetryReview;
use TelemetrySettings;
use TelemetryStore;

final class TelemetryStoreTest extends IntegrationTestCase
{
    private TelemetryStore $store;
    private array $settings;
    private array $health;
    private string $originalSettings;
    private string $originalModules;
    private array $collectorState = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        foreach (['TelemetryDetectors', 'TelemetryReview', 'cronjob/TelemetryCronjob'] as $class) {
            require_once self::rootPath() . 'includes/classes/' . $class . '.class.php';
        }
    }

    protected function setUp(): void
    {
        $this->requireDatabase();
        $this->store = new TelemetryStore(TelemetryConnection::open());
        $this->settings = ['enabled' => 1] + TelemetrySettings::defaults();
        $this->health = TelemetryStore::health();
        TelemetryStore::health(static fn($state) => array_fill_keys(array_keys($state), null) + ['gaps' => []]);
        TelemetryStore::health(['gaps' => [], 'retry_after' => 0]);
        $config = Config::get(1);
        $this->originalSettings = $config->telemetry_settings;
        $this->originalModules = $config->moduls;
        $modules = array_pad(explode(';', $config->moduls), MODULE_AMOUNT, 1);
        $modules[MODULE_TELEMETRY] = 1;
        $config->moduls = implode(';', $modules);
        $config->telemetry_settings = json_encode($this->settings);
        $config->save();
        foreach (['settings' => [1 => $this->settings], 'ready' => [], 'request' => null, 'queued' => 0, 'sequence' => 0] as $name => $value) {
            $property = new \ReflectionProperty(PlayerTelemetry::class, $name);
            $this->collectorState[$name] = $property->getValue();
            $property->setValue(null, $value);
        }
    }

    protected function tearDown(): void
    {
        if (!isset($this->store)) {
            return;
        }
        foreach (['events', 'daily', 'warnings'] as $table) {
            $this->store->query('DELETE FROM %%TELEMETRY_' . strtoupper($table) . '%% WHERE actor IN (900001,900002)');
        }
        Config::get(1)->telemetry_settings = $this->originalSettings;
        Config::get(1)->moduls = $this->originalModules;
        Config::get(1)->save();
        $original = $this->health;
        TelemetryStore::health(static fn($state) => $original + array_fill_keys(array_keys($state), null));
        foreach ($this->collectorState as $name => $value) {
            (new \ReflectionProperty(PlayerTelemetry::class, $name))->setValue(null, $value);
        }
    }

    private function event(int $actor, int $at, string $kind, int $target = 0, array $data = []): array
    {
        $id = bin2hex(random_bytes(16));
        return ['event_key' => $id, 'request_id' => $id, 'universe' => 1,
            'actor' => $actor, 'target' => $target, 'at' => $at, 'kind' => $kind,
            'data' => $data, 'fleet_id' => 0, 'result' => 'success',
            'interactive' => !in_array($kind, ['delivery', 'combat'], true), 'ip' => null];
    }

    public function testSqlMigrationCreatesPrefixedTablesAndDefaults(): void
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
            $this->assertSame(1, (int)$pdo->query("SELECT COUNT(*) FROM `" . $prefix . "cronjobs` WHERE class='TelemetryCronjob'")->fetchColumn());
            foreach (['daily', 'events', 'warnings', 'audit'] as $table) {
                $this->assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM `' . $prefix . 'telemetry_' . $table . '`')->fetchColumn());
            }
        } finally {
            foreach (['telemetry_audit', 'telemetry_warnings', 'telemetry_events', 'telemetry_daily', 'cronjobs', 'config'] as $table) {
                $pdo->exec('DROP TABLE IF EXISTS `' . $prefix . $table . '`');
            }
        }
    }

    public function testSharedConnectionIsIndependentOfGameTransactions(): void
    {
        require (defined('DATABASE_CONFIG_FILE') ? DATABASE_CONFIG_FILE : ROOT_PATH . 'includes/config.php');
        TelemetryConnection::configure($database, []);
        try {
            $pdo = TelemetryConnection::open();
            $this->assertNotSame(self::$db->getHandle(), $pdo);
            $this->assertSame($database['databasename'], $pdo->query('SELECT DATABASE()')->fetchColumn());
            $this->assertStringContainsString($database['tableprefix'] . 'telemetry_events', TelemetryConnection::tables()['%%TELEMETRY_EVENTS%%']);
            self::$db->beginTransaction();
            $pdo->beginTransaction();
            $pdo->rollBack();
            $this->assertTrue(self::$db->inTransaction());
            self::$db->rollBack();
        } finally {
            TelemetryConnection::configure($database, $telemetry ?? []);
        }
    }

    public function testDailyWindowsAndNetworkUseOnlyInteractions(): void
    {
        $at = strtotime('today UTC') - 1;
        $first = $this->event(900001, $at, 'login', 0, ['client' => 'Desktop']);
        $first['ip'] = '192.0.2.55';
        $second = $this->event(900001, $at + 2, 'fleet.send', 0, ['client' => 'Mobile']);
        $second['ip'] = '2001:db8::55';
        $delivery = $this->event(900001, $at, 'delivery', 900002, ['metal' => 1000]);
        $this->store->write([$first, $second, $delivery], [1 => $this->settings]);
        $this->assertCount(2, $this->store->daily(1, 900001, $at - 3600));
        $this->assertSame([], $this->store->daily(1, 900002, 0));
        $network = $this->store->network(1, 900001, $at - 3600, time());
        $this->assertSame(2, (int)$network['ips']);
        $this->assertSame(2, (int)$network['clients']);
    }

    public function testCompletedBacklogExceedsInteractiveLimitWithoutLosingDeliveries(): void
    {
        for ($i = 0; $i < 300; ++$i) {
            PlayerTelemetry::record(900001, 1, 'delivery', 900002, $i, ['metal' => 1000]);
        }
        PlayerTelemetry::flush();
        $this->assertSame(300, (int)$this->store->query("SELECT COUNT(*) FROM %%TELEMETRY_EVENTS%% WHERE actor=900001 AND kind='delivery'")->fetchColumn());
    }

    public function testRollbackDiscardsEvidenceAndOutageBackoffSkipsNextWrite(): void
    {
        self::$db->beginTransaction();
        PlayerTelemetry::record(900001, 1, 'fleet.send', 0, 0, [], true);
        self::$db->rollBack();
        PlayerTelemetry::flush();
        $this->assertSame([], $this->store->events(1, 900001, 0, 10));
        $config = TelemetryConnection::configuration();
        require (defined('DATABASE_CONFIG_FILE') ? DATABASE_CONFIG_FILE : ROOT_PATH . 'includes/config.php');
        TelemetryConnection::configure($database, array_replace($config, ['userpw' => 'invalid-test-password']));
        try {
            PlayerTelemetry::record(900001, 1, 'login', 0, 0, [], true);
            PlayerTelemetry::flush();
            $this->assertGreaterThan(time(), TelemetryStore::health()['retry_after']);
            PlayerTelemetry::record(900001, 1, 'login', 0, 0, [], true);
            $start = microtime(true);
            PlayerTelemetry::flush();
            $this->assertLessThan(0.3, microtime(true) - $start);
        } finally {
            TelemetryConnection::configure($database, $telemetry ?? []);
        }
        $this->assertSame([], $this->store->events(1, 900001, 0, 10));
    }

    public function testCleanupRetainsOlderDeliveryContext(): void
    {
        $now = time();
        $rows = [
            $this->event(900001, $now - 8 * 86400, 'galaxy.view'),
            $this->event(900001, $now - 8 * 86400, 'delivery', 900002, ['metal' => 4000000]),
            $this->event(900002, $now - 6 * 86400, 'delivery', 900001, ['deuterium' => 1300000]),
        ];
        $this->store->write($rows, [1 => $this->settings]);
        $health = $this->store->maintenance($this->settings, $now);
        $this->assertGreaterThan(0, $health['allocated_mb']);
        $events = $this->store->events(1, 900001, 0, 100, 900002);
        $this->assertCount(2, $events);
        $this->assertSame([], TelemetryDetectors::pushing($events, 900001, 900002, [], $this->settings, $now));
        $this->assertCount(1, $this->store->events(1, 900001, 0, 100));
    }

    public function testCronAdvancesAndUpdatesResolvedPairsWithoutAdminVisits(): void
    {
        $settings = $this->settings;
        $settings['analysis_accounts'] = 1;
        Config::get(1)->telemetry_settings = json_encode($settings);
        Config::get(1)->save();
        $cron = new TelemetryCronjob();
        $cron->run();
        $first = TelemetryStore::health()['analysis_1'];
        $cron->run();
        $second = TelemetryStore::health()['analysis_1'];
        $this->assertTrue($first['incomplete']);
        $this->assertGreaterThan($first['cursor'], $second['cursor']);
        $now = time();
        $send = $this->event(900001, $now - 3 * 86400, 'delivery', 900002, ['metal' => 4000000]);
        $this->store->write([$send], [1 => $settings]);
        $review = new TelemetryReview($this->store);
        $review->evaluate(1, $settings, $now, 900000, 900000);
        $id = (int)$this->store->query('SELECT id FROM %%TELEMETRY_WARNINGS%% WHERE actor=900001 AND other=900002')->fetchColumn();
        $this->assertGreaterThan(0, $id);
        $review->decide(1, 1, $id, 'follow_up', 'Retain the decision', $now);
        $original = $review->evidence(1, $id)['evidence'];
        $this->store->write([$this->event(900002, $now, 'delivery', 900001, ['deuterium' => 1000000])], [1 => $settings]);
        $review->evaluate(1, $settings, $now + 1, 900000, 900000);
        $latest = $review->evidence(1, $id);
        $this->assertFalse($latest['latest_evidence']['matches']);
        $this->assertSame($original, $latest['evidence']);
        $this->assertSame('follow_up', $latest['status']);
        $review->evaluate(1, $settings, $now + 30 * 86400, 900000, 900000);
        $this->assertSame($now + 30 * 86400, $review->evidence(1, $id)['latest_evidence']['evaluated_at']);
        $this->store->query('DELETE FROM %%TELEMETRY_AUDIT%% WHERE warning_id=?', [$id]);
    }

    public function testQuotaStopsSharedCollectionAndPreservesGameSpace(): void
    {
        $this->store->maintenance($this->settings, time());
        TelemetryStore::health(['allocated_mb' => $this->settings['budget_mb'], 'suspended' => true]);
        $result = $this->store->write([$this->event(900001, time(), 'login')], [1 => $this->settings]);
        $this->assertSame(!TelemetryConnection::shared(), $result);
        $this->assertSame([], $this->store->events(1, 900001, 0, 10));
        $this->assertCount(TelemetryConnection::shared() ? 0 : 1, $this->store->daily(1, 900001, 0));
        if (TelemetryConnection::shared()) {
            $reserved = TelemetryStore::health()['estimated_new_bytes'];
            PlayerTelemetry::record(900001, 1, 'login', 0, 0, [], true);
            PlayerTelemetry::flush();
            $this->assertSame($reserved, TelemetryStore::health()['estimated_new_bytes']);
        }
    }
}
