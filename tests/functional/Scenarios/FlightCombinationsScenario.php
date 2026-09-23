<?php

/**
 * Risistar - Functional Scenario: Flight Combinations Matrix
 */

require_once __DIR__ . '/../ScenarioInterface.php';

class FlightCombinationsScenario implements ScenarioInterface
{
    public function getKey(): string
    {
        return 'flight_combinations';
    }

    public function getName(): string
    {
        return 'Matrice de Combinaisons de Trajets (Planète/Lune/Débris)';
    }

    public function getDescription(): string
    {
        return 'Injecte des vols pour toutes les combinaisons possibles : Planète->Planète, Planète->Lune, Lune->Planète, Lune->Lune, et Planète/Lune->Champ de Débris.';
    }

    public function execute(array $options, bool $isCli): void
    {
        $db = Database::get();

        $eta = isset($options['eta']) ? max(1, (int)$options['eta']) : 5;
        $playerName = $options['user'] ?? 'Admin';

        $player = $db->selectSingle("SELECT id, username FROM %%USERS%% WHERE username = :name;", [
            ':name' => $playerName
        ]);

        if (empty($player)) {
            echo "[ERROR] Joueur '{$playerName}' non trouvé.\n";
            return;
        }

        $pPlanet = $db->selectSingle("SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :pType;", [
            ':ownerId' => $player['id'],
            ':pType' => 1
        ]);

        $pMoon = $db->selectSingle("SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :pType;", [
            ':ownerId' => $player['id'],
            ':pType' => 3
        ]);

        // Cibles d'autres joueurs
        $otherPlanet = $db->selectSingle("SELECT id, id_owner, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner != :ownerId AND planet_type = :pType;", [
            ':ownerId' => $player['id'],
            ':pType' => 1
        ]);

        $otherMoon = $db->selectSingle("SELECT id, id_owner, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner != :ownerId AND planet_type = :pType;", [
            ':ownerId' => $player['id'],
            ':pType' => 3
        ]);

        echo "🛸 Génération de la matrice de combinaisons de trajets pour '{$player['username']}'...\n";

        $now = TIMESTAMP;

        // 1. Planète -> Planète (Attaque)
        if ($pPlanet && $otherPlanet) {
            $t = $now + $eta;
            $s1 = [204 => 100, 206 => 20];
            ScenarioManager::ensureShips($pPlanet['id'], $s1);
            FleetFunctions::sendFleet(
                $s1, 1, $player['id'], $pPlanet['id'], $pPlanet['galaxy'], $pPlanet['system'], $pPlanet['planet'], 1,
                $otherPlanet['id_owner'], $otherPlanet['id'], $otherPlanet['galaxy'], $otherPlanet['system'], $otherPlanet['planet'], 1,
                [901 => 0, 902 => 0, 903 => 0], $t, $t, $t + $eta
            );
            echo "  ➜ [1/5] Planète -> Planète : Injecté (Arrivée dans {$eta}s).\n";
        }

        // 2. Planète -> Lune (Transport)
        if ($pPlanet && $otherMoon) {
            $t = $now + $eta + 5;
            $s2 = [202 => 50, 203 => 20];
            ScenarioManager::ensureShips($pPlanet['id'], $s2);
            FleetFunctions::sendFleet(
                $s2, 3, $player['id'], $pPlanet['id'], $pPlanet['galaxy'], $pPlanet['system'], $pPlanet['planet'], 1,
                $otherMoon['id_owner'], $otherMoon['id'], $otherMoon['galaxy'], $otherMoon['system'], $otherMoon['planet'], 3,
                [901 => 10000, 902 => 5000, 903 => 2000], $t, $t, $t + $eta
            );
            echo "  ➜ [2/5] Planète -> Lune : Injecté (Arrivée dans ".($eta+5)."s).\n";
        }

        // 3. Lune -> Planète (Espionnage)
        if ($pMoon && $otherPlanet) {
            $t = $now + $eta + 10;
            $s3 = [210 => 10];
            ScenarioManager::ensureShips($pMoon['id'], $s3);
            FleetFunctions::sendFleet(
                $s3, 6, $player['id'], $pMoon['id'], $pMoon['galaxy'], $pMoon['system'], $pMoon['planet'], 3,
                $otherPlanet['id_owner'], $otherPlanet['id'], $otherPlanet['galaxy'], $otherPlanet['system'], $otherPlanet['planet'], 1,
                [901 => 0, 902 => 0, 903 => 0], $t, $t, $t + $eta
            );
            echo "  ➜ [3/5] Lune -> Planète : Injecté (Arrivée dans ".($eta+10)."s).\n";
        }

        // 4. Lune -> Lune (Stationnement)
        if ($pMoon && $otherMoon) {
            $t = $now + $eta + 15;
            $s4 = [204 => 200, 207 => 30];
            ScenarioManager::ensureShips($pMoon['id'], $s4);
            FleetFunctions::sendFleet(
                $s4, 4, $player['id'], $pMoon['id'], $pMoon['galaxy'], $pMoon['system'], $pMoon['planet'], 3,
                $otherMoon['id_owner'], $otherMoon['id'], $otherMoon['galaxy'], $otherMoon['system'], $otherMoon['planet'], 3,
                [901 => 0, 902 => 0, 903 => 0], $t, $t, $t + $eta
            );
            echo "  ➜ [4/5] Lune -> Lune : Injecté (Arrivée dans ".($eta+15)."s).\n";
        }

        // 5. Lune -> Champ de Débris (Recyclage, Type 2)
        if ($pMoon && $otherPlanet) {
            $t = $now + $eta + 20;
            $s5 = [209 => 30];
            ScenarioManager::ensureShips($pMoon['id'], $s5);
            FleetFunctions::sendFleet(
                $s5, 8, $player['id'], $pMoon['id'], $pMoon['galaxy'], $pMoon['system'], $pMoon['planet'], 3,
                0, $otherPlanet['id'], $otherPlanet['galaxy'], $otherPlanet['system'], $otherPlanet['planet'], 2, // Type 2 = TF
                [901 => 0, 902 => 0, 903 => 0], $t, $t, $t + $eta
            );
            echo "  ➜ [5/5] Lune -> Champ de Débris (TF) : Injecté (Arrivée dans ".($eta+20)."s).\n";
        }

        echo "✅ Tous les types de combinaisons de vol ont été injectés avec succès !\n";
    }
}
