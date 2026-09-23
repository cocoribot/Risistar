<?php

namespace Risistar\Tests\Integration;

use FleetFunctions;

/**
 * Integration tests for ACS bash counter behaviour.
 * Runs against the isolated test DB (2moons_test) via phpunit.integration.xml bootstrap.
 */
class AcsBashRecallTest extends IntegrationTestCase
{
    private static int $attackerId = 0;
    private static int $defenderId = 0;
    private static array $attackerPlanet = [];
    private static array $defenderPlanet = [];

    /** @var int[] */
    private static array $createdFleetIds = [];
    /** @var int[] */
    private static array $createdAcsIds = [];

    public static function tearDownAfterClass(): void
    {
        if (self::$db === null) {
            return;
        }

        foreach (self::$createdFleetIds as $fleetId) {
            self::cleanupFleet($fleetId);
        }
        foreach (self::$createdAcsIds as $acsId) {
            self::cleanupAcs($acsId);
        }
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$db === null) {
            return;
        }

        try {
            require_once self::rootPath() . 'includes/pages/game/AbstractGamePage.class.php';
            require_once self::rootPath() . 'includes/pages/game/ShowFleetTablePage.class.php';
            self::seedPlayers();
        } catch (\Throwable $e) {
            self::$bootstrapError = $e->getMessage();
            self::$db = null;
        }
    }

    private static function trackFleet(int $fleetId): void
    {
        if ($fleetId > 0) {
            self::$createdFleetIds[] = $fleetId;
        }
    }

    private static function trackAcs(int $acsId): void
    {
        if ($acsId > 0) {
            self::$createdAcsIds[] = $acsId;
        }
    }

    private static function seedPlayers(): void
    {
        global $resource;

        $users = self::$db->select(
            'SELECT id, username FROM %%USERS%% WHERE urlaubs_modus = 0 ORDER BY id ASC LIMIT 2;'
        );

        if (count($users) < 2) {
            throw new \RuntimeException('Need at least two active users in the database.');
        }

        self::$attackerId = (int) $users[0]['id'];
        self::$defenderId = (int) $users[1]['id'];

        self::$attackerPlanet = self::$db->selectSingle(
            'SELECT id, galaxy, system, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId ORDER BY id ASC LIMIT 1;',
            [':ownerId' => self::$attackerId]
        );
        self::$defenderPlanet = self::$db->selectSingle(
            'SELECT id, galaxy, system, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId ORDER BY id ASC LIMIT 1;',
            [':ownerId' => self::$defenderId]
        );

        if (empty(self::$attackerPlanet) || empty(self::$defenderPlanet)) {
            throw new \RuntimeException('Attacker or defender has no planet.');
        }

        self::ensureGameGlobals();

        $shipCol = $resource[204];
        self::$db->update(
            "UPDATE %%PLANETS%% SET {$shipCol} = {$shipCol} + 1000 WHERE id = :planetId;",
            [':planetId' => self::$attackerPlanet['id']]
        );
    }

    private function sendAttackFleet(int $fleetGroup = 0): int
    {
        $now = TIMESTAMP;
        $arrival = $now + 3600;
        $end = $arrival + 3600;

        FleetFunctions::sendFleet(
            [204 => 10],
            1,
            self::$attackerId,
            self::$attackerPlanet['id'],
            self::$attackerPlanet['galaxy'],
            self::$attackerPlanet['system'],
            self::$attackerPlanet['planet'],
            self::$attackerPlanet['planet_type'],
            self::$defenderId,
            self::$defenderPlanet['id'],
            self::$defenderPlanet['galaxy'],
            self::$defenderPlanet['system'],
            self::$defenderPlanet['planet'],
            self::$defenderPlanet['planet_type'],
            [901 => 0, 902 => 0, 903 => 0],
            $arrival,
            $arrival,
            $end,
            $fleetGroup
        );

        $fleetId = (int) self::$db->selectSingle(
            'SELECT fleet_id FROM %%FLEETS%% WHERE fleet_owner = :ownerId ORDER BY fleet_id DESC LIMIT 1;',
            [':ownerId' => self::$attackerId],
            'fleet_id'
        );
        self::trackFleet($fleetId);

        return $fleetId;
    }

    private function createAcsForFleet(int $fleetId): int
    {
        $fleetData = self::$db->selectSingle(
            'SELECT fleet_start_time, fleet_end_id FROM %%FLEETS%% WHERE fleet_id = :fleetId;',
            [':fleetId' => $fleetId]
        );

        $GLOBALS['USER'] = ['id' => self::$attackerId];

        $ref = new \ReflectionClass(\ShowFleetTablePage::class);
        $page = $ref->newInstanceWithoutConstructor();
        $acsData = $page->createACS($fleetId, $fleetData);
        $acsId = (int) $acsData['id'];
        self::trackAcs($acsId);

        return $acsId;
    }

    private static function cleanupFleet(int $fleetId): void
    {
        self::$db->delete('DELETE FROM %%FLEETS_EVENT%% WHERE fleetID = :fleetId;', [':fleetId' => $fleetId]);
        self::$db->delete('DELETE FROM %%LOG_FLEETS%% WHERE fleet_id = :fleetId;', [':fleetId' => $fleetId]);
        self::$db->delete('DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;', [':fleetId' => $fleetId]);
    }

    private static function cleanupAcs(int $acsId): void
    {
        self::$db->delete('DELETE FROM %%USERS_ACS%% WHERE acsID = :acsId;', [':acsId' => $acsId]);
        self::$db->delete('DELETE FROM %%AKS%% WHERE id = :acsId;', [':acsId' => $acsId]);
    }

    public function testCreateAcsSyncsLogFleetsFleetGroup(): void
    {
        $this->requireDatabase();

        $fleetId = $this->sendAttackFleet();
        $acsId = $this->createAcsForFleet($fleetId);

        $fleetGroup = self::$db->selectSingle(
            'SELECT fleet_group FROM %%FLEETS%% WHERE fleet_id = :fleetId;',
            [':fleetId' => $fleetId],
            'fleet_group'
        );
        $logFleetGroup = self::$db->selectSingle(
            'SELECT fleet_group FROM %%LOG_FLEETS%% WHERE fleet_id = :fleetId;',
            [':fleetId' => $fleetId],
            'fleet_group'
        );

        $this->assertEquals($acsId, (int) $fleetGroup);
        $this->assertEquals($acsId, (int) $logFleetGroup);
    }

    public function testAcsRecallMarksCreatorLogFleetAsCanceled(): void
    {
        $this->requireDatabase();

        $fleetId = $this->sendAttackFleet();
        $acsId = $this->createAcsForFleet($fleetId);

        FleetFunctions::SendFleetBack(['id' => self::$attackerId], $fleetId);

        $hasCanceled = self::$db->selectSingle(
            'SELECT hasCanceled FROM %%LOG_FLEETS%% WHERE fleet_id = :fleetId;',
            [':fleetId' => $fleetId],
            'hasCanceled'
        );

        $this->assertEquals(1, (int) $hasCanceled);
    }

    public function testCheckBashCountsAcsWavesAsSingleHit(): void
    {
        $this->requireDatabase();

        // Isolate from fleets left by earlier tests in this class.
        self::$db->update(
            'UPDATE %%LOG_FLEETS%% SET hasCanceled = 1
            WHERE fleet_owner = :owner AND fleet_target_owner = :target;',
            [
                ':owner' => self::$attackerId,
                ':target' => self::$defenderId,
            ]
        );

        $GLOBALS['USER'] = ['id' => self::$attackerId];
        $acsId = 999001;

        // 6 fleets in the same ACS wave: COUNT(*) would bash (>= 6), DISTINCT must not.
        for ($i = 0; $i < 6; $i++) {
            $this->sendAttackFleet($acsId);
        }

        $this->assertFalse(
            FleetFunctions::CheckBash(self::$defenderId),
            'Six fleets in one ACS wave must count as a single bash hit.'
        );

        // 5 additional solo attacks → 1 ACS + 5 solos = 6 → bash limit reached.
        for ($i = 0; $i < 5; $i++) {
            $this->sendAttackFleet();
        }

        $this->assertTrue(
            FleetFunctions::CheckBash(self::$defenderId),
            'One ACS wave plus five solo attacks must reach the bash limit.'
        );
    }
}
