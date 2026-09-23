<?php

/**
 * Risistar - Functional Scenario: Interplanetary Missiles (MIP / Mission 10)
 */

require_once __DIR__ . '/../ScenarioInterface.php';

class MipScenario implements ScenarioInterface
{
    public function getKey(): string
    {
        return 'mip';
    }

    public function getName(): string
    {
        return 'Tirs de Missiles Interplanétaires (MIP / Mission 10)';
    }

    public function getDescription(): string
    {
        return 'Génère des tirs de Missiles Interplanétaires (MIP). Options: --direction=incoming (défaut) ou --direction=outgoing, --count=3, --interval=3, --target_obj=0 (0=toutes défenses, 401-410=cibles spécifiques).';
    }

    public function execute(array $options, bool $isCli): void
    {
        $db = Database::get();

        $direction = strtolower($options['direction'] ?? 'incoming'); // incoming | outgoing
        $count = isset($options['count']) ? max(1, (int)$options['count']) : 3;
        $eta = isset($options['eta']) ? max(1, (int)$options['eta']) : 5;
        $interval = isset($options['interval']) ? max(1, (int)$options['interval']) : 3;
        $targetObj = isset($options['target_obj']) ? (int)$options['target_obj'] : 0; // 0 = Toutes défenses
        $userName = $options['user'] ?? 'Admin';

        $user = $db->selectSingle("SELECT id, username FROM %%USERS%% WHERE username = :name;", [
            ':name' => $userName
        ]);

        if (empty($user)) {
            echo "[ERROR] Joueur '{$userName}' non trouvé.\n";
            return;
        }

        if ($direction === 'outgoing' || $direction === 'out') {
            // OUTGOING: Le joueur cible tire des MIPs vers une planète ennemie
            $startPlanet = $db->selectSingle(
                "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = 1 ORDER BY id ASC;",
                [':ownerId' => $user['id']]
            );

            if (empty($startPlanet)) {
                echo "[ERROR] Aucune planète de départ trouvée pour le joueur '{$userName}'.\n";
                return;
            }

            $enemies = $db->select("SELECT id, username FROM %%USERS%% WHERE id != :userId AND id > 0;", [
                ':userId' => $user['id']
            ]);

            if (empty($enemies)) {
                echo "[ERROR] Aucun ennemi trouvé pour recevoir les tirs de MIP.\n";
                return;
            }

            echo "🚀 🚀 Injection de {$count} salve(s) de Missiles Interplanétaires (MIP) tirée(s) par '{$user['username']}' [{$startPlanet['galaxy']}:{$startPlanet['system']}:{$startPlanet['planet']}] (Intervalle: {$interval}s, Cible défense: {$targetObj})...\n";

            for ($i = 0; $i < $count; $i++) {
                $enemy = $enemies[$i % count($enemies)];
                $targetPlanet = $db->selectSingle(
                    "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = 1;",
                    [':ownerId' => $enemy['id']]
                );

                if (empty($targetPlanet)) continue;

                $now = TIMESTAMP;
                $flightEta = $eta + ($i * $interval);
                $arrivalTime = $now + $flightEta;

                $ships = [
                    502 => mt_rand(10, 30), // Missiles Interplanétaires (MIP / ID 502)
                ];

                ScenarioManager::ensureShips($startPlanet['id'], $ships);

                FleetFunctions::sendFleet(
                    $ships,
                    10, // Mission 10 = MIP
                    $user['id'],
                    $startPlanet['id'],
                    $startPlanet['galaxy'],
                    $startPlanet['system'],
                    $startPlanet['planet'],
                    $startPlanet['planet_type'],
                    $enemy['id'],
                    $targetPlanet['id'],
                    $targetPlanet['galaxy'],
                    $targetPlanet['system'],
                    $targetPlanet['planet'],
                    $targetPlanet['planet_type'],
                    [901 => 0, 902 => 0, 903 => 0],
                    $arrivalTime,
                    $arrivalTime,
                    $arrivalTime,
                    0,
                    $targetObj
                );

                echo "  ➜ Salve de MIP #".($i+1)." vers '{$enemy['username']}' [{$targetPlanet['galaxy']}:{$targetPlanet['system']}:{$targetPlanet['planet']}] | Impact dans {$flightEta}s.\n";
            }

            echo "✅ Salves de MIP sortantes injectées avec succès !\n";

        } else {
            // INCOMING (défaut): Des ennemis tirent des MIPs vers le joueur cible
            $targetPlanet = $db->selectSingle(
                "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = 1 ORDER BY id ASC;",
                [':ownerId' => $user['id']]
            );

            if (empty($targetPlanet)) {
                echo "[ERROR] Aucune planète cible trouvée pour le joueur '{$userName}'.\n";
                return;
            }

            $enemies = $db->select("SELECT id, username FROM %%USERS%% WHERE id != :targetId AND id > 0;", [
                ':targetId' => $user['id']
            ]);

            if (empty($enemies)) {
                echo "[ERROR] Aucun joueur ennemi trouvé pour effectuer les tirs de MIP.\n";
                return;
            }

            echo "⚠️ 🚀 Injection de {$count} attaque(s) de Missiles Interplanétaires (MIP) sur '{$user['username']}' [{$targetPlanet['galaxy']}:{$targetPlanet['system']}:{$targetPlanet['planet']}] (Intervalle: {$interval}s, Cible défense: {$targetObj})...\n";

            for ($i = 0; $i < $count; $i++) {
                $enemy = $enemies[$i % count($enemies)];
                $startPlanet = $db->selectSingle(
                    "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = 1;",
                    [':ownerId' => $enemy['id']]
                );

                if (empty($startPlanet)) continue;

                $now = TIMESTAMP;
                $flightEta = $eta + ($i * $interval);
                $arrivalTime = $now + $flightEta;

                $ships = [
                    502 => mt_rand(10, 30), // Missiles Interplanétaires (MIP / ID 502)
                ];

                ScenarioManager::ensureShips($startPlanet['id'], $ships);

                FleetFunctions::sendFleet(
                    $ships,
                    10, // Mission 10 = MIP
                    $enemy['id'],
                    $startPlanet['id'],
                    $startPlanet['galaxy'],
                    $startPlanet['system'],
                    $startPlanet['planet'],
                    $startPlanet['planet_type'],
                    $user['id'],
                    $targetPlanet['id'],
                    $targetPlanet['galaxy'],
                    $targetPlanet['system'],
                    $targetPlanet['planet'],
                    $targetPlanet['planet_type'],
                    [901 => 0, 902 => 0, 903 => 0],
                    $arrivalTime,
                    $arrivalTime,
                    $arrivalTime,
                    0,
                    $targetObj
                );

                echo "  ➜ Salve de MIP entrante #".($i+1)." par '{$enemy['username']}' depuis [{$startPlanet['galaxy']}:{$startPlanet['system']}:{$startPlanet['planet']}] | Impact dans {$flightEta}s.\n";
            }

            echo "✅ Salves de MIP entrantes injectées avec succès !\n";
        }
    }
}
