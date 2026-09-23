<?php

namespace Risistar\Tests\Integration;

use FlyingFleetHandler;

/**
 * Integration tests for fleet_event processing locks / stale lock recovery.
 */
class FleetEventLockTest extends IntegrationTestCase
{
    /** @var int[] */
    private static array $createdFleetIds = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$db === null) {
            return;
        }

        require_once self::rootPath() . 'includes/classes/class.FlyingFleetHandler.php';
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$db === null) {
            return;
        }

        foreach (self::$createdFleetIds as $fleetId) {
            self::$db->delete('DELETE FROM %%FLEETS_EVENT%% WHERE fleetID = :fleetId;', [':fleetId' => $fleetId]);
            self::$db->delete('DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;', [':fleetId' => $fleetId]);
        }
    }

    protected function tearDown(): void
    {
        if (self::$db === null) {
            return;
        }

        foreach (self::$createdFleetIds as $fleetId) {
            self::$db->delete('DELETE FROM %%FLEETS_EVENT%% WHERE fleetID = :fleetId;', [':fleetId' => $fleetId]);
            self::$db->delete('DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;', [':fleetId' => $fleetId]);
        }
        self::$createdFleetIds = [];
    }

    public function testIsAutoReleasableLockAcceptsMd5Only(): void
    {
        $this->requireDatabase();

        $this->assertTrue(FlyingFleetHandler::isAutoReleasableLock('0ef06b9f24c20d201b8123226b3ce676'));
        $this->assertFalse(FlyingFleetHandler::isAutoReleasableLock('ADM_LOCK'));
        $this->assertFalse(FlyingFleetHandler::isAutoReleasableLock('CUSTOM_LOCK'));
        $this->assertFalse(FlyingFleetHandler::isAutoReleasableLock('abc'));
        $this->assertFalse(FlyingFleetHandler::isAutoReleasableLock(null));
    }

    public function testRecentHashLockIsKept(): void
    {
        $this->requireDatabase();

        $fleetId = $this->insertLockedFleetEvent(
            md5('recent_fleet_lock'),
            TIMESTAMP - 120
        );

        FlyingFleetHandler::clearStaleLocks(300);

        $lock = self::$db->selectSingle(
            'SELECT `lock` FROM %%FLEETS_EVENT%% WHERE fleetID = :fleetId;',
            [':fleetId' => $fleetId],
            'lock'
        );

        $this->assertSame(md5('recent_fleet_lock'), $lock);
    }

    public function testStaleHashLockIsCleared(): void
    {
        $this->requireDatabase();

        $token = md5('stale_fleet_lock');
        $fleetId = $this->insertLockedFleetEvent($token, TIMESTAMP - 400);

        FlyingFleetHandler::clearStaleLocks(300);

        $lock = self::$db->selectSingle(
            'SELECT `lock` FROM %%FLEETS_EVENT%% WHERE fleetID = :fleetId;',
            [':fleetId' => $fleetId],
            'lock'
        );

        $this->assertNull($lock);
    }

    public function testOrphanHashLockWithoutLockedAtIsCleared(): void
    {
        $this->requireDatabase();

        $token = md5('orphan_fleet_lock');
        $fleetId = $this->insertLockedFleetEvent($token, null);

        FlyingFleetHandler::clearStaleLocks(300);

        $lock = self::$db->selectSingle(
            'SELECT `lock` FROM %%FLEETS_EVENT%% WHERE fleetID = :fleetId;',
            [':fleetId' => $fleetId],
            'lock'
        );

        $this->assertNull($lock);
    }

    public function testAdmLockIsNeverAutoCleared(): void
    {
        $this->requireDatabase();

        $fleetId = $this->insertLockedFleetEvent('ADM_LOCK', TIMESTAMP - 3600);

        FlyingFleetHandler::clearStaleLocks(300);

        $lock = self::$db->selectSingle(
            'SELECT `lock` FROM %%FLEETS_EVENT%% WHERE fleetID = :fleetId;',
            [':fleetId' => $fleetId],
            'lock'
        );

        $this->assertSame('ADM_LOCK', $lock);
    }

    public function testNonHashLockIsNeverAutoCleared(): void
    {
        $this->requireDatabase();

        $fleetId = $this->insertLockedFleetEvent('CUSTOM_LOCK', null);

        FlyingFleetHandler::clearStaleLocks(300);

        $lock = self::$db->selectSingle(
            'SELECT `lock` FROM %%FLEETS_EVENT%% WHERE fleetID = :fleetId;',
            [':fleetId' => $fleetId],
            'lock'
        );

        $this->assertSame('CUSTOM_LOCK', $lock);
    }

    public function testProcessDueEventsReleasesLockAfterException(): void
    {
        $this->requireDatabase();

        $token = md5('throwing_fleet_process');
        $fleetId = $this->insertLockedFleetEvent($token, TIMESTAMP);

        $handler = new class extends FlyingFleetHandler {
            public function run()
            {
                throw new \RuntimeException('Simulated fleet processing failure');
            }
        };
        $handler::processDueEvents($token);

        $lock = self::$db->selectSingle(
            'SELECT `lock` FROM %%FLEETS_EVENT%% WHERE fleetID = :fleetId;',
            [':fleetId' => $fleetId],
            'lock'
        );

        $this->assertNull($lock, 'finally must release the processing token after a Throwable');
    }

    private function insertLockedFleetEvent(string $lock, ?int $lockedAt): int
    {
        $users = self::$db->select('SELECT id FROM %%USERS%% ORDER BY id ASC LIMIT 1;');
        if (empty($users)) {
            $this->markTestSkipped('No users in test database.');
        }

        $ownerId = (int) $users[0]['id'];
        $planet = self::$db->selectSingle(
            'SELECT id, galaxy, system, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId ORDER BY id ASC LIMIT 1;',
            [':ownerId' => $ownerId]
        );
        if (empty($planet)) {
            $this->markTestSkipped('No planet in test database.');
        }

        $eventTime = TIMESTAMP - 60;

        self::$db->insert(
            'INSERT INTO %%FLEETS%% SET
            fleet_owner = :owner,
            fleet_mission = :mission,
            fleet_amount = 1,
            fleet_array = :fleetArray,
            fleet_universe = 1,
            fleet_start_time = :eventTime,
            fleet_start_id = :planetId,
            fleet_start_galaxy = :galaxy,
            fleet_start_system = :system,
            fleet_start_planet = :planet,
            fleet_start_type = :planetType,
            fleet_end_time = :endTime,
            fleet_end_stay = :eventTime,
            fleet_end_id = :planetId,
            fleet_end_galaxy = :galaxy,
            fleet_end_system = :system,
            fleet_end_planet = :planet,
            fleet_end_type = :planetType,
            fleet_target_owner = :owner,
            fleet_group = 0,
            fleet_mess = 0,
            start_time = :startTime,
            fleet_busy = 0;',
            [
                ':owner' => $ownerId,
                ':mission' => 3,
                ':fleetArray' => '202,1',
                ':eventTime' => $eventTime,
                ':endTime' => $eventTime + 100,
                ':planetId' => $planet['id'],
                ':galaxy' => $planet['galaxy'],
                ':system' => $planet['system'],
                ':planet' => $planet['planet'],
                ':planetType' => $planet['planet_type'],
                ':startTime' => $eventTime - 100,
            ]
        );

        $fleetId = (int) self::$db->lastInsertId();
        self::$createdFleetIds[] = $fleetId;

        if ($lockedAt === null) {
            self::$db->insert(
                'INSERT INTO %%FLEETS_EVENT%% SET fleetID = :fleetId, `time` = :eventTime, `lock` = :lock, lockedAt = NULL;',
                [
                    ':fleetId' => $fleetId,
                    ':eventTime' => $eventTime,
                    ':lock' => $lock,
                ]
            );
        } else {
            self::$db->insert(
                'INSERT INTO %%FLEETS_EVENT%% SET fleetID = :fleetId, `time` = :eventTime, `lock` = :lock, lockedAt = :lockedAt;',
                [
                    ':fleetId' => $fleetId,
                    ':eventTime' => $eventTime,
                    ':lock' => $lock,
                    ':lockedAt' => $lockedAt,
                ]
            );
        }

        return $fleetId;
    }
}
