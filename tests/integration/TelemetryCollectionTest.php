<?php

namespace Risistar\Tests\Integration;

use Config;
use FleetFunctions;
use GameRequest;
use MissionCaseStay;
use MissionCaseTransport;
use PlayerTelemetry;
use TelemetryConnection;
use TelemetryReview;
use TelemetryStore;

require_once __DIR__ . '/TelemetryTestCase.php';

/**
 * Integration tests for telemetry collection from real game code: page loads,
 * transports between players and requests that roll back.
 */
class TelemetryCollectionTest extends TelemetryTestCase
{
    private const CARGO_METAL = 4000000.0;
    private const CARGO_CRYSTAL = 1500000.0;

    private static int $senderId = 0;
    private static int $recipientId = 0;
    private static array $senderPlanets = [];
    private static array $recipientPlanet = [];

    /** @var int[] */
    private array $createdFleetIds = [];
    private array $planetSnapshots = [];
    private int $lastMessageId = 0;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$db === null) {
            return;
        }

        try {
            require_once self::rootPath() . 'includes/classes/class.MissionFunctions.php';
            require_once self::rootPath() . 'includes/classes/missions/Mission.interface.php';
            require_once self::rootPath() . 'includes/classes/missions/MissionCaseTransport.class.php';
            require_once self::rootPath() . 'includes/classes/missions/MissionCaseStay.class.php';
            self::seedPlayers();
        } catch (\Throwable $e) {
            self::$bootstrapError = $e->getMessage();
            self::$db = null;
        }
    }

    private static function seedPlayers(): void
    {
        $users = self::$db->select('SELECT id FROM %%USERS%% WHERE universe = 1 ORDER BY id ASC LIMIT 2;');
        if (count($users) < 2) {
            throw new \RuntimeException('Need at least two users in the database.');
        }

        self::$senderId = (int) $users[0]['id'];
        self::$recipientId = (int) $users[1]['id'];
        self::$senderPlanets = self::$db->select(
            'SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :type ORDER BY id ASC LIMIT 2;',
            [':ownerId' => self::$senderId, ':type' => 1]
        );
        self::$recipientPlanet = self::$db->selectSingle(
            'SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :type ORDER BY id ASC LIMIT 1;',
            [':ownerId' => self::$recipientId, ':type' => 1]
        );

        if (count(self::$senderPlanets) < 2 || empty(self::$recipientPlanet)) {
            throw new \RuntimeException('Sender needs two planets and recipient one planet.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->actors = [900001, self::$senderId, self::$recipientId];
        $GLOBALS['USER'] = null;
        $_GET = [];
        $_POST = [];
        $this->lastMessageId = (int) self::$db->selectSingle('SELECT MAX(message_id) AS id FROM %%MESSAGES%%;', [], 'id');
    }

    protected function tearDown(): void
    {
        if (self::$db !== null) {
            if (self::$db->getTransactionDepth() > 0) {
                self::$db->rollBackAll();
            }
            foreach ($this->createdFleetIds as $fleetId) {
                self::$db->delete('DELETE FROM %%FLEETS_EVENT%% WHERE fleetID = :fleetId;', [':fleetId' => $fleetId]);
                self::$db->delete('DELETE FROM %%LOG_FLEETS%% WHERE fleet_id = :fleetId;', [':fleetId' => $fleetId]);
                self::$db->delete('DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;', [':fleetId' => $fleetId]);
            }
            foreach ($this->planetSnapshots as $planet) {
                self::$db->update(
                    'UPDATE %%PLANETS%% SET metal = :metal, crystal = :crystal, deuterium = :deuterium, small_ship_cargo = :ships WHERE id = :id;',
                    [
                        ':metal' => $planet['metal'],
                        ':crystal' => $planet['crystal'],
                        ':deuterium' => $planet['deuterium'],
                        ':ships' => $planet['small_ship_cargo'],
                        ':id' => $planet['id'],
                    ]
                );
            }
            self::$db->delete('DELETE FROM %%MESSAGES%% WHERE message_id > :id;', [':id' => $this->lastMessageId]);
        }
        $_GET = [];
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $_SERVER['REQUEST_METHOD']);

        parent::tearDown();
    }

    public function testPageLoadCountsAsActivity(): void
    {
        $_GET = ['page' => 'overview'];

        PlayerTelemetry::interaction(self::$senderId, 1);
        PlayerTelemetry::flush();

        $this->assertCount(1, $this->store->daily(1, self::$senderId, time() - 60));
    }

    public function testQueueReloadIsCountedButIsNotActivity(): void
    {
        // buildlist.js reloads the page when a build finishes, even if the player is away.
        $_GET = ['page' => 'buildings', 'passive_reload' => 'queue'];

        PlayerTelemetry::interaction(self::$senderId, 1);
        PlayerTelemetry::flush();

        $this->assertSame('[]', $this->store->daily(1, self::$senderId, time() - 60)[0]['windows']);
        $this->assertSame(['passive' => 1], $this->store->actionCounts(1, self::$senderId, time() - 60, time()));
    }

    public function testSamePageAgainIsAReload(): void
    {
        $_GET = ['page' => 'overview'];

        $page = PlayerTelemetry::interaction(self::$senderId, 1, 5);
        PlayerTelemetry::interaction(self::$senderId, 1, 5, $page);
        PlayerTelemetry::flush();

        $this->assertEquals(['interaction' => 1, 'reload' => 1], $this->store->actionCounts(1, self::$senderId, time() - 60, time()));
    }

    public function testSamePageOnAnotherPlanetIsAPlanetSwitch(): void
    {
        // Each planet shown gets its activity (*), so cycling planets keeps all of them active.
        $_GET = ['page' => 'overview'];

        $page = PlayerTelemetry::interaction(self::$senderId, 1, 5);
        PlayerTelemetry::interaction(self::$senderId, 1, 6, $page);
        PlayerTelemetry::flush();

        $this->assertEquals(['interaction' => 1, 'planet.switch' => 1], $this->store->actionCounts(1, self::$senderId, time() - 60, time()));
    }

    public function testAlliancePageIsCountedAsAllianceView(): void
    {
        // The alliance page shows the fleets coming at every member.
        $_GET = ['page' => 'alliance'];

        PlayerTelemetry::interaction(self::$senderId, 1, 5);
        PlayerTelemetry::flush();

        $this->assertSame(['alliance.view' => 1], $this->store->actionCounts(1, self::$senderId, time() - 60, time()));
    }

    public function testFormSentToTheSamePageIsStillAReload(): void
    {
        // A script can send forms too; real actions are recorded by the game code that runs them.
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['page' => 'overview'];

        PlayerTelemetry::interaction(self::$senderId, 1, 5, '5:overview:');
        PlayerTelemetry::flush();

        $this->assertSame(['reload' => 1], $this->store->actionCounts(1, self::$senderId, time() - 60, time()));
    }

    public function testAjaxCallToTheSamePageIsStillAReload(): void
    {
        $_GET = ['page' => 'overview', 'ajax' => 1];

        $page = PlayerTelemetry::interaction(self::$senderId, 1, 5, '5:overview:');
        PlayerTelemetry::flush();

        $this->assertSame(['reload' => 1], $this->store->actionCounts(1, self::$senderId, time() - 60, time()));
        $this->assertSame('5:overview:', $page);
    }

    public function testGameActionOnAReloadedPageMakesTheHourBusy(): void
    {
        $GLOBALS['USER'] = ['id' => self::$senderId, 'universe' => 1];
        $_GET = ['page' => 'buildings'];

        PlayerTelemetry::interaction(self::$senderId, 1, 5, '5:buildings:');
        PlayerTelemetry::action('queue.buildings');
        PlayerTelemetry::flush();

        $hours = json_decode($this->store->daily(1, self::$senderId, time() - 60)[0]['hours'], true);
        [$loads, , $busy] = current($hours);
        $this->assertSame([1, 1], [$loads, $busy]);
    }

    public function testSameGalaxySystemAgainIsNotAnAction(): void
    {
        $GLOBALS['USER'] = ['id' => self::$senderId, 'universe' => 1];

        PlayerTelemetry::galaxyView(1, 5, '1:5');
        PlayerTelemetry::flush();

        $this->assertSame([], $this->store->daily(1, self::$senderId, time() - 60));
    }

    public function testAnotherGalaxySystemIsAnAction(): void
    {
        $GLOBALS['USER'] = ['id' => self::$senderId, 'universe' => 1];

        $shown = PlayerTelemetry::galaxyView(1, 6, '1:5');
        PlayerTelemetry::flush();

        $this->assertSame(['galaxy.view' => 1], $this->store->actionCounts(1, self::$senderId, time() - 60, time()));
        $this->assertSame('1:6', $shown);
    }

    public function testListInPageNameIsIgnored(): void
    {
        $_GET = ['page' => 'overview', 'mode' => ['x']];

        $this->assertSame('5:overview:', PlayerTelemetry::interaction(self::$senderId, 1, 5));
    }

    public function testLockedDailyRowDoesNotPauseCollection(): void
    {
        $this->request(900001, 'interaction', time());
        $other = TelemetryConnection::open();
        $other->beginTransaction();
        $other->query('SELECT * FROM ' . TelemetryConnection::tables()['%%TELEMETRY_DAILY%%'] . ' WHERE actor=900001 FOR UPDATE')->fetchAll();

        try {
            $this->request(900001, 'interaction', time());
        } finally {
            $other->rollBack();
        }

        $this->assertNull(TelemetryStore::health()['retry_after'] ?? null);
        $this->request(900001, 'interaction', time());
        $this->assertSame(2, $this->store->actionCounts(1, 900001, time() - 60, time())['interaction']);
    }

    public function testShortestAndLongestWaitAreStoredPerHour(): void
    {
        $hour = strtotime('today UTC') + 2 * 3600;
        foreach ([0, 120, 400, 520] as $offset) {
            $this->request(900001, 'reload', $hour + $offset);
        }

        $hours = json_decode($this->store->daily(1, 900001, $hour)[0]['hours'], true);

        $this->assertSame([2 => [4, 0, 0, 0, 120, 280]], $hours);
    }

    public function testFakeQueueReloadEveryMinuteIsFoundAsTiming(): void
    {
        // The reload marker comes from the browser, so a script can send it too.
        $start = strtotime('today UTC') + 3600;
        for ($i = 0; $i < 40; $i++) {
            $this->resetCollector();
            PlayerTelemetry::record(900001, 1, 'passive', 0, 0, [], false, $start + $i * 60);
            PlayerTelemetry::flush();
        }

        $accountFindings = new \ReflectionMethod(TelemetryReview::class, 'accountFindings');
        [$findings] = $accountFindings->invoke(new TelemetryReview($this->store), 1, 900001, $this->settings, $start + 3600);

        $this->assertSame(['timing'], array_column($findings, 'kind'));
        $this->assertSame([60], $findings[0]['metrics']['days'][0]['seconds']);
    }

    public function testDisabledModuleRecordsNothing(): void
    {
        $config = Config::get(1);
        $modules = explode(';', $config->moduls);
        $modules[MODULE_TELEMETRY] = 0;
        $config->moduls = implode(';', $modules);
        $config->save();
        $GLOBALS['USER'] = ['id' => self::$senderId, 'universe' => 1];

        PlayerTelemetry::interaction(self::$senderId, 1);
        PlayerTelemetry::action('fleet.send');
        PlayerTelemetry::flush();

        $this->assertSame([], $this->store->daily(1, self::$senderId, time() - 60));
    }

    public function testNightlyScriptBetweenNormalPlayIsFound(): void
    {
        mt_srand(3);
        for ($day = 3; $day >= 1; $day--) {
            $midnight = strtotime('today UTC') - $day * 86400;
            // 01:00 to 04:30: faked queue reloads every 2 to 5 minutes.
            for ($at = $midnight + 3600; $at < $midnight + 4.5 * 3600; $at += mt_rand(120, 300)) {
                $this->request(900001, 'passive', $at);
            }
            // Evening: two hours of normal play.
            for ($at = $midnight + 19 * 3600; $at < $midnight + 21 * 3600; $at += mt_rand(5, 90)) {
                $this->request(900001, mt_rand(0, 3) ? 'interaction' : 'galaxy.view', $at);
            }
        }

        $accountFindings = new \ReflectionMethod(TelemetryReview::class, 'accountFindings');
        [$findings] = $accountFindings->invoke(new TelemetryReview($this->store), 1, 900001, $this->settings, time());

        $this->assertSame(['refreshing'], array_column($findings, 'kind'));
    }

    public function testPageLoadsFromOneAddressAreStoredAsOneNetworkRow(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0';

        for ($request = 0; $request < 3; $request++) {
            $this->resetCollector();
            PlayerTelemetry::interaction(self::$senderId, 1);
            PlayerTelemetry::flush();
        }

        $network = $this->store->network(1, self::$senderId, time() - 60, time());
        $this->assertCount(1, $network['rows']);
        $this->assertSame(3, (int) $network['rows'][0]['requests']);
        $this->assertSame('Firefox · Linux · desktop', $network['rows'][0]['client']);
        $this->assertSame([], $this->storedEvents(self::$senderId), 'Page loads are counted, not stored one by one.');
    }

    public function testTenThousandActionsInOneHourAreCountedWithoutRows(): void
    {
        $hour = time() - time() % 3600;
        for ($request = 0; $request < 40; $request++) {
            $this->resetCollector();
            for ($i = 0; $i < 250; $i++) {
                PlayerTelemetry::record(900001, 1, 'galaxy.view', 0, 0, [], true, $hour + $request * 80 + $i % 80);
            }
            PlayerTelemetry::flush();
        }

        $this->assertSame(10000, $this->store->actionCounts(1, 900001, $hour, $hour + 3599)['galaxy.view']);
        $this->assertSame([], $this->storedEvents(900001));
        $windows = json_decode($this->store->daily(1, 900001, $hour)[0]['windows'], true);
        $this->assertSame([[$hour, $hour + 39 * 80 + 79]], $windows, 'Activity time is still exact.');
    }

    public function testOneRequestCannotQueueMoreThanTheActionLimit(): void
    {
        for ($i = 0; $i < 300; $i++) {
            PlayerTelemetry::record(900001, 1, 'galaxy.view', 0, 0, ['galaxy' => 1, 'system' => $i], true);
        }
        PlayerTelemetry::flush();

        $this->assertSame(256, $this->store->actionCounts(1, 900001, time() - 60, time())['galaxy.view']);
    }

    public function testFleetBacklogIsNotLimitedLikePlayerActions(): void
    {
        // One page load can process hundreds of arrivals after a quiet night.
        for ($i = 0; $i < 300; $i++) {
            PlayerTelemetry::record(900001, 1, 'delivery', self::$recipientId, $i, ['metal' => 1000]);
        }
        PlayerTelemetry::flush();

        $this->assertCount(300, $this->storedEvents(900001, 'delivery'));
    }

    public function testRolledBackActionLeavesNoTrace(): void
    {
        self::$db->beginTransaction();
        PlayerTelemetry::record(self::$senderId, 1, 'fleet.send', self::$recipientId, 0, [], true);
        self::$db->rollBack();
        PlayerTelemetry::flush();

        $this->assertSame([], $this->store->daily(1, self::$senderId, time() - 60));
    }

    public function testTelemetryDatabaseDownWaitsBeforeTryingAgain(): void
    {
        $configuration = TelemetryConnection::configuration();
        require (defined('DATABASE_CONFIG_FILE') ? DATABASE_CONFIG_FILE : ROOT_PATH . 'includes/config.php');
        TelemetryConnection::configure($database, array_replace($configuration, ['userpw' => 'invalid-test-password']));

        try {
            PlayerTelemetry::record(900001, 1, 'login', 0, 0, [], true);
            PlayerTelemetry::flush();
            $this->assertGreaterThan(time(), TelemetryStore::health()['retry_after']);

            PlayerTelemetry::record(900001, 1, 'login', 0, 0, [], true);
            $start = microtime(true);
            PlayerTelemetry::flush();
            $this->assertLessThan(0.3, microtime(true) - $start, 'While waiting, no new connection is tried.');
        } finally {
            TelemetryConnection::configure($database, $telemetry ?? []);
        }

        $this->assertSame([], $this->store->daily(1, 900001, 0));
    }

    public function testTransportToAnotherPlayerIsRecordedAsDelivery(): void
    {
        $fleet = $this->sendFleet(3, self::$senderPlanets[0], self::$recipientPlanet, self::$recipientId);

        self::$db->beginTransaction();
        (new MissionCaseTransport($fleet))->TargetEvent();
        self::$db->commit();
        PlayerTelemetry::flush();

        $deliveries = $this->storedEvents(self::$senderId, 'delivery');
        $this->assertCount(1, $deliveries);
        $this->assertSame(self::$recipientId, (int) $deliveries[0]['target']);
        $this->assertSame((int) $fleet['fleet_id'], $deliveries[0]['data']['fleet']);
        $this->assertSame((int) $fleet['fleet_start_time'], (int) $deliveries[0]['at']);
        $this->assertEquals(self::CARGO_METAL, $deliveries[0]['data']['metal']);
        $this->assertEquals(self::CARGO_CRYSTAL, $deliveries[0]['data']['crystal']);
        $this->assertSame((int) self::$recipientPlanet['id'], $deliveries[0]['data']['planet']);
    }

    public function testMerchantFleetStationedOnAnotherPlayerIsADelivery(): void
    {
        global $pricelist;
        $fleet = $this->sendFleet(4, self::$senderPlanets[0], self::$recipientPlanet, self::$recipientId);

        self::$db->beginTransaction();
        (new MissionCaseStay($fleet))->TargetEvent();
        self::$db->commit();
        PlayerTelemetry::flush();

        $deliveries = $this->storedEvents(self::$senderId, 'delivery');
        $this->assertCount(1, $deliveries);
        $this->assertSame([202 => 10], $deliveries[0]['data']['ships']);
        $this->assertEquals(10 * $pricelist[202]['cost'][901], $deliveries[0]['data']['ship_value']['metal']);
        $this->assertEquals(self::CARGO_METAL, $deliveries[0]['data']['metal']);
    }

    public function testTransportBetweenOwnPlanetsIsNotADelivery(): void
    {
        $fleet = $this->sendFleet(3, self::$senderPlanets[0], self::$senderPlanets[1], self::$senderId);

        self::$db->beginTransaction();
        (new MissionCaseTransport($fleet))->TargetEvent();
        self::$db->commit();
        PlayerTelemetry::flush();

        $this->assertSame([], $this->storedEvents(self::$senderId, 'delivery'));
    }

    public function testFailedArrivalLeavesNoDelivery(): void
    {
        $fleet = $this->sendFleet(3, self::$senderPlanets[0], self::$recipientPlanet, self::$recipientId);
        $metalBefore = $this->planetMetal((int) self::$recipientPlanet['id']);

        // Arrivals run inside a player's page request; when that request rolls back, the deposit is gone too.
        self::$db->beginTransaction();
        (new MissionCaseTransport($fleet))->TargetEvent();
        self::$db->rollBackAll();
        PlayerTelemetry::flush();

        $this->assertEqualsWithDelta($metalBefore, $this->planetMetal((int) self::$recipientPlanet['id']), 0.1);
        $this->assertSame([], $this->storedEvents(self::$senderId, 'delivery'), 'Evidence must match what the game kept.');
    }

    public function testRequestStoppingInsideNestedTransactionDropsItsTelemetry(): void
    {
        // Same shape as common.php: request transaction, then a nested section that never closes.
        self::$db->beginTransaction();
        self::$db->beginTransaction();
        PlayerTelemetry::record(self::$senderId, 1, 'fleet.send', self::$recipientId, 0, [], true);

        GameRequest::finish();

        $this->assertSame(0, self::$db->getTransactionDepth());
        $this->assertTrue(self::$db->wasRequestRolledBack());
        $this->assertSame([], $this->store->daily(1, self::$senderId, time() - 60));
    }

    private function sendFleet(int $mission, array $from, array $to, int $targetOwner): array
    {
        global $resource;

        foreach ([$from, $to] as $planet) {
            $this->planetSnapshots[$planet['id']] ??= self::$db->selectSingle(
                'SELECT id, metal, crystal, deuterium, small_ship_cargo FROM %%PLANETS%% WHERE id = :id;',
                [':id' => $planet['id']]
            );
        }

        // sendFleet takes the ships from the planet; give them back so the planet is unchanged.
        $shipColumn = $resource[202];
        self::$db->update(
            "UPDATE %%PLANETS%% SET {$shipColumn} = {$shipColumn} + 10 WHERE id = :planetId;",
            [':planetId' => $from['id']]
        );

        $arrival = TIMESTAMP - 60;
        FleetFunctions::sendFleet(
            [202 => 10],
            $mission,
            self::$senderId,
            $from['id'],
            $from['galaxy'],
            $from['system'],
            $from['planet'],
            $from['planet_type'],
            $targetOwner,
            $to['id'],
            $to['galaxy'],
            $to['system'],
            $to['planet'],
            $to['planet_type'],
            [901 => self::CARGO_METAL, 902 => self::CARGO_CRYSTAL, 903 => 0],
            $arrival,
            $arrival,
            $arrival + 3600
        );

        $fleet = self::$db->selectSingle(
            'SELECT * FROM %%FLEETS%% WHERE fleet_owner = :ownerId ORDER BY fleet_id DESC LIMIT 1;',
            [':ownerId' => self::$senderId]
        );
        $this->createdFleetIds[] = (int) $fleet['fleet_id'];

        return $fleet;
    }

    private function planetMetal(int $planetId): float
    {
        return (float) self::$db->selectSingle('SELECT metal FROM %%PLANETS%% WHERE id = :id;', [':id' => $planetId], 'metal');
    }

    /** One request through the collector, as if the player loaded one page at that time. */
    private function request(int $actor, string $kind, int $at): void
    {
        $this->resetCollector();
        PlayerTelemetry::record($actor, 1, $kind, 0, 0, [], $kind !== 'passive', $at);
        PlayerTelemetry::flush();
    }
}
