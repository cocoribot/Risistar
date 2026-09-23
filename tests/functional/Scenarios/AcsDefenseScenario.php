<?php

/**
 * Risistar - Functional Scenario: ACS Defense (Stationnement allié)
 */

require_once __DIR__ . '/../ScenarioInterface.php';

class AcsDefenseScenario implements ScenarioInterface
{
    public function getKey(): string
    {
        return 'acs_defense';
    }

    public function getName(): string
    {
        return 'Défense d\'Alliance ACS (Stationnement chez un allié)';
    }

    public function getDescription(): string
    {
        return 'Envoie une flotte alliée se poser sur la planète/lune du joueur (Mission 5) pour participer à sa défense.';
    }

    public function execute(array $options, bool $isCli): void
    {
        $db = Database::get();

        $eta = isset($options['eta']) ? max(1, (int)$options['eta']) : 5;
        $targetName = $options['user'] ?? 'Admin';

        $targetUser = $db->selectSingle("SELECT id, username FROM %%USERS%% WHERE username = :name;", [
            ':name' => $targetName
        ]);

        if (empty($targetUser)) {
            echo "[ERROR] Joueur cible '{$targetName}' non trouvé.\n";
            return;
        }

        $targetPlanet = $db->selectSingle("SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId ORDER BY id ASC LIMIT 1;", [
            ':ownerId' => $targetUser['id']
        ]);

        $allyUser = $db->selectSingle("SELECT id, username FROM %%USERS%% WHERE id != :targetId ORDER BY RAND() LIMIT 1;", [
            ':targetId' => $targetUser['id']
        ]);

        if (empty($allyUser)) {
            echo "[ERROR] Aucun joueur allié disponible.\n";
            return;
        }

        $allyPlanet = $db->selectSingle("SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId ORDER BY RAND() LIMIT 1;", [
            ':ownerId' => $allyUser['id']
        ]);

        echo "🛡️ Envoi d'une flotte de défense d'alliance par '{$allyUser['username']}' vers '{$targetUser['username']}' [{$targetPlanet['galaxy']}:{$targetPlanet['system']}:{$targetPlanet['planet']}] (ETA: {$eta}s)...\n";

        $now = TIMESTAMP;
        $arrivalTime = $now + $eta;
        $stayTime = $arrivalTime + 3600; // Rest 1h sur place
        $endTime = $stayTime + $eta;

        $ships = [
            204 => 300, // Chasseurs légers
            205 => 100, // Chasseurs lourds
            207 => 50,  // Vaisseaux de bataille
            208 => 1,   // Colonisateur
        ];

        ScenarioManager::ensureShips($allyPlanet['id'], $ships);

        FleetFunctions::sendFleet(
            $ships,
            5, // Stationner chez un allié (Hold)
            $allyUser['id'],
            $allyPlanet['id'],
            $allyPlanet['galaxy'],
            $allyPlanet['system'],
            $allyPlanet['planet'],
            $allyPlanet['planet_type'],
            $targetUser['id'],
            $targetPlanet['id'],
            $targetPlanet['galaxy'],
            $targetPlanet['system'],
            $targetPlanet['planet'],
            $targetPlanet['planet_type'],
            [901 => 0, 902 => 0, 903 => 0],
            $arrivalTime,
            $stayTime,
            $endTime
        );

        echo "✅ Flotte de défense d'alliance (Mission 5) injectée avec succès !\n";
    }
}
