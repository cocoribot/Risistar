<?php

/**
 * Manual integration test for ACS bash recall (isolated DB: 2moons_test).
 * Usage: docker exec -w /app risistar-web-1 php scratch/test_acs_bash_recall.php
 *
 * Functional scenarios visible in the browser use populate_universe.php (2moons).
 */

define('MODE', 'TEST');
define('DATABASE_VERSION', 'OLD');
define('ROOT_PATH', './');
define('DATABASE_CONFIG_FILE', ROOT_PATH . 'includes/config.test.php');

require_once ROOT_PATH . 'includes/common.php';
require_once ROOT_PATH . 'includes/vars.php';
require_once ROOT_PATH . 'includes/pages/game/AbstractGamePage.class.php';
require_once ROOT_PATH . 'includes/pages/game/ShowFleetTablePage.class.php';

$db = Database::get();

$users = $db->select('SELECT id, username FROM %%USERS%% WHERE urlaubs_modus = 0 ORDER BY id ASC LIMIT 2;');
if (count($users) < 2) {
    echo "ERROR: need at least two active users.\n";
    exit(1);
}

$attackerId = (int) $users[0]['id'];
$defenderId = (int) $users[1]['id'];

$attackerPlanet = $db->selectSingle(
    'SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId ORDER BY id ASC LIMIT 1;',
    [':ownerId' => $attackerId]
);
$defenderPlanet = $db->selectSingle(
    'SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId ORDER BY id ASC LIMIT 1;',
    [':ownerId' => $defenderId]
);

$db->update(
    "UPDATE %%PLANETS%% SET {$resource[204]} = {$resource[204]} + 1000 WHERE id = :planetId;",
    [':planetId' => $attackerPlanet['id']]
);

$now = TIMESTAMP;
$arrival = $now + 3600;
$end = $arrival + 3600;

FleetFunctions::sendFleet(
    [204 => 10],
    1,
    $attackerId,
    $attackerPlanet['id'],
    $attackerPlanet['galaxy'],
    $attackerPlanet['system'],
    $attackerPlanet['planet'],
    $attackerPlanet['planet_type'],
    $defenderId,
    $defenderPlanet['id'],
    $defenderPlanet['galaxy'],
    $defenderPlanet['system'],
    $defenderPlanet['planet'],
    $defenderPlanet['planet_type'],
    [901 => 0, 902 => 0, 903 => 0],
    $arrival,
    $arrival,
    $end,
    0
);

$fleetId = (int) $db->selectSingle(
    'SELECT fleet_id FROM %%FLEETS%% WHERE fleet_owner = :ownerId ORDER BY fleet_id DESC LIMIT 1;',
    [':ownerId' => $attackerId],
    'fleet_id'
);
echo "Sent attack fleet #{$fleetId} from {$users[0]['username']} to {$users[1]['username']}\n";

$fleetData = $db->selectSingle(
    'SELECT fleet_start_time, fleet_end_id FROM %%FLEETS%% WHERE fleet_id = :fleetId;',
    [':fleetId' => $fleetId]
);

$GLOBALS['USER'] = ['id' => $attackerId];
$ref = new ReflectionClass(ShowFleetTablePage::class);
$page = $ref->newInstanceWithoutConstructor();
$acsData = $page->createACS($fleetId, $fleetData);
$acsId = (int) $acsData['id'];

$logGroupBefore = $db->selectSingle(
    'SELECT fleet_group, hasCanceled FROM %%LOG_FLEETS%% WHERE fleet_id = :fleetId;',
    [':fleetId' => $fleetId]
);
echo "After createACS: LOG_FLEETS fleet_group={$logGroupBefore['fleet_group']} (expected {$acsId})\n";

FleetFunctions::SendFleetBack(['id' => $attackerId], $fleetId);

$logAfterRecall = $db->selectSingle(
    'SELECT fleet_group, hasCanceled FROM %%LOG_FLEETS%% WHERE fleet_id = :fleetId;',
    [':fleetId' => $fleetId]
);
echo "After recall: LOG_FLEETS hasCanceled={$logAfterRecall['hasCanceled']} (expected 1)\n";

if ((int) $logGroupBefore['fleet_group'] === $acsId && (int) $logAfterRecall['hasCanceled'] === 1) {
    echo "PASS: ACS creator bash log is correctly synced and canceled on recall.\n";
    $exitCode = 0;
} else {
    echo "FAIL: unexpected LOG_FLEETS state.\n";
    $exitCode = 1;
}

$db->delete('DELETE FROM %%FLEETS_EVENT%% WHERE fleetID = :fleetId;', [':fleetId' => $fleetId]);
$db->delete('DELETE FROM %%LOG_FLEETS%% WHERE fleet_id = :fleetId;', [':fleetId' => $fleetId]);
$db->delete('DELETE FROM %%FLEETS%% WHERE fleet_id = :fleetId;', [':fleetId' => $fleetId]);
$db->delete('DELETE FROM %%USERS_ACS%% WHERE acsID = :acsId;', [':acsId' => $acsId]);
$db->delete('DELETE FROM %%AKS%% WHERE id = :acsId;', [':acsId' => $acsId]);
echo "Cleanup: removed test fleet #{$fleetId} and ACS #{$acsId}.\n";

exit($exitCode);
