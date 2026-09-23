<?php

/**
 * Risistar - Functional Scenario: ACS Group Attack
 */

require_once __DIR__ . '/../ScenarioInterface.php';

class AcsAttackScenario implements ScenarioInterface
{
    public function getKey(): string
    {
        return 'acs_attack';
    }

    public function getName(): string
    {
        return 'Attaque Groupée ACS (AG d\'alliance hostile ou amie)';
    }

    public function getDescription(): string
    {
        return 'Crée une Attaque Groupée (ACS) composée d\'une flotte chef de file et de plusieurs flottes rattachées arrivant en même temps.';
    }

    public function execute(array $options, bool $isCli): void
    {
        $db = Database::get();

        $count = isset($options['count']) ? max(2, (int)$options['count']) : 3;
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

        // Attaquants (différents de la cible)
        $attackers = $db->select("SELECT id, username FROM %%USERS%% WHERE id != :targetId ORDER BY RAND() LIMIT :limit;", [
            ':targetId' => $targetUser['id'],
            ':limit' => $count
        ]);

        if (count($attackers) < 2) {
            echo "[ERROR] Pas assez d'utilisateurs disponibles pour créer une Attaque Groupée.\n";
            return;
        }

        $leadAttacker = $attackers[0];
        $leadPlanet = $db->selectSingle("SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId ORDER BY RAND() LIMIT 1;", [
            ':ownerId' => $leadAttacker['id']
        ]);

        $now = TIMESTAMP;
        $arrivalTime = $now + $eta;
        $endTime = $arrivalTime + $eta;

        // 1. Créer le groupe AKS
        $acsName = 'AG_' . mt_rand(1000, 9999);
        $db->insert("INSERT INTO %%AKS%% SET name = :name, ankunft = :time, target = :targetId;", [
            ':name' => $acsName,
            ':time' => $arrivalTime,
            ':targetId' => $targetPlanet['id'],
        ]);
        $acsId = $db->lastInsertId();

        // Inscrire le chef de file à l'AKS
        $db->insert("INSERT INTO %%USERS_ACS%% SET acsID = :acsId, userID = :userId;", [
            ':acsId' => $acsId,
            ':userId' => $leadAttacker['id'],
        ]);

        echo "🛡️⚔️ Création de l'Attaque Groupée '{$acsName}' (ACS ID: {$acsId}) ciblée sur [{$targetPlanet['galaxy']}:{$targetPlanet['system']}:{$targetPlanet['planet']}] (Arrivée: {$eta}s)...\n";

        // 2. Envoyer la flotte chef de file (Mission 2 = ACS Attack)
        $leadShips = [204 => 400, 206 => 80, 207 => 40];
        ScenarioManager::ensureShips($leadPlanet['id'], $leadShips);
        FleetFunctions::sendFleet(
            $leadShips,
            2, // ACS Attack
            $leadAttacker['id'],
            $leadPlanet['id'],
            $leadPlanet['galaxy'],
            $leadPlanet['system'],
            $leadPlanet['planet'],
            $leadPlanet['planet_type'],
            $targetUser['id'],
            $targetPlanet['id'],
            $targetPlanet['galaxy'],
            $targetPlanet['system'],
            $targetPlanet['planet'],
            $targetPlanet['planet_type'],
            [901 => 0, 902 => 0, 903 => 0],
            $arrivalTime,
            $arrivalTime,
            $endTime,
            $acsId
        );

        echo "  ➜ Flotte Chef de file par '{$leadAttacker['username']}' injectée dans l'AG.\n";

        // 3. Envoyer les flottes qui rejoignent l'AG
        for ($i = 1; $i < count($attackers); $i++) {
            $joinAttacker = $attackers[$i];
            $joinPlanet = $db->selectSingle("SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId ORDER BY RAND() LIMIT 1;", [
                ':ownerId' => $joinAttacker['id']
            ]);

            if (empty($joinPlanet)) continue;

            $db->insert("INSERT INTO %%USERS_ACS%% SET acsID = :acsId, userID = :userId;", [
                ':acsId' => $acsId,
                ':userId' => $joinAttacker['id'],
            ]);

            $joinShips = [204 => 200, 205 => 50, 215 => 15];
            ScenarioManager::ensureShips($joinPlanet['id'], $joinShips);
            FleetFunctions::sendFleet(
                $joinShips,
                2, // ACS Attack
                $joinAttacker['id'],
                $joinPlanet['id'],
                $joinPlanet['galaxy'],
                $joinPlanet['system'],
                $joinPlanet['planet'],
                $joinPlanet['planet_type'],
                $targetUser['id'],
                $targetPlanet['id'],
                $targetPlanet['galaxy'],
                $targetPlanet['system'],
                $targetPlanet['planet'],
                $targetPlanet['planet_type'],
                [901 => 0, 902 => 0, 903 => 0],
                $arrivalTime,
                $arrivalTime,
                $endTime,
                $acsId
            );

            echo "  ➜ Flotte alliée #{$i} par '{$joinAttacker['username']}' a rejoint l'AG (ACS ID: {$acsId}).\n";
        }

        echo "✅ Scénario Attaque Groupée (ACS) injecté avec succès !\n";
    }
}
