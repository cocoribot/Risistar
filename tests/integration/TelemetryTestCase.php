<?php

namespace Risistar\Tests\Integration;

use Config;
use PlayerTelemetry;
use TelemetryConnection;
use TelemetrySettings;
use TelemetryStore;

/**
 * Shared setup for telemetry tests: module enabled on universe 1, empty collector,
 * clean health state. Everything is restored after each test.
 */
abstract class TelemetryTestCase extends IntegrationTestCase
{
    protected TelemetryStore $store;
    protected array $settings;

    /** @var int[] Accounts whose telemetry rows are deleted after each test. */
    protected array $actors = [900001, 900002];

    private array $originalHealth = [];
    private string $originalSettings = '{}';
    private string $originalModules = '';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/classes/TelemetryDetectors.class.php';
        require_once self::rootPath() . 'includes/classes/TelemetryReview.class.php';
        require_once self::rootPath() . 'includes/classes/cronjob/TelemetryCronjob.class.php';
    }

    protected function setUp(): void
    {
        $this->requireDatabase();

        $this->store = new TelemetryStore(TelemetryConnection::open());
        $this->settings = ['enabled' => 1] + TelemetrySettings::defaults();
        $this->originalHealth = TelemetryStore::health();
        TelemetryStore::health(array_fill_keys(array_keys($this->originalHealth), null) + ['retry_after' => null]);

        $config = Config::get(1);
        $this->originalSettings = $config->telemetry_settings;
        $this->originalModules = $config->moduls;
        $modules = array_pad(explode(';', $config->moduls), MODULE_AMOUNT, 1);
        $modules[MODULE_TELEMETRY] = 1;
        $config->moduls = implode(';', $modules);
        $config->telemetry_settings = json_encode(TelemetrySettings::defaults());
        $config->save();

        $this->resetCollector();
    }

    protected function tearDown(): void
    {
        if (!isset($this->store)) {
            return;
        }

        $db = self::$db;
        if ($db->getTransactionDepth() > 0) {
            $db->rollBackAll();
        }
        (new \ReflectionProperty($db, 'requestRolledBack'))->setValue($db, false);

        $actors = implode(',', array_map('intval', $this->actors));
        foreach (['EVENTS', 'DAILY', 'NETWORK', 'WARNINGS'] as $table) {
            $this->store->query("DELETE FROM %%TELEMETRY_{$table}%% WHERE actor IN ({$actors})");
        }
        $this->store->query("DELETE FROM %%TELEMETRY_PAIRS%% WHERE pair_a IN ({$actors}) OR pair_b IN ({$actors})");

        $config = Config::get(1);
        $config->telemetry_settings = $this->originalSettings;
        $config->moduls = $this->originalModules;
        $config->save();

        TelemetryStore::health($this->originalHealth + array_fill_keys(array_keys(TelemetryStore::health()), null));
        $this->resetCollector();
    }

    /** The collector keeps settings and events in memory for one request; each test starts a new one. */
    protected function resetCollector(): void
    {
        $state = ['settings' => [], 'ready' => [], 'request' => null, 'queued' => 0];
        foreach ($state as $name => $value) {
            (new \ReflectionProperty(PlayerTelemetry::class, $name))->setValue(null, $value);
        }
    }

    protected function event(int $actor, int $at, string $kind, int $target = 0, array $data = []): array
    {
        return [
            'request_id' => bin2hex(random_bytes(16)),
            'universe' => 1,
            'actor' => $actor,
            'target' => $target,
            'at' => $at,
            'kind' => $kind,
            'data' => $data,
            'interactive' => !in_array($kind, ['delivery', 'combat', 'passive'], true),
            'ip' => null,
            'client' => null,
        ];
    }

    protected function storedEvents(int $actor, ?string $kind = null): array
    {
        $events = $this->store->events(1, $actor, 0, 1000);
        if ($kind === null) {
            return $events;
        }

        return array_values(array_filter($events, static fn($event) => $event['kind'] === $kind));
    }
}
