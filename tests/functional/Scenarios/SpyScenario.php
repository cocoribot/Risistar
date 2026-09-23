<?php

/**
 * Risistar - Functional Scenario: Espionage (Spy / Mission 6)
 */

require_once __DIR__ . '/../ScenarioInterface.php';

class SpyScenario implements ScenarioInterface
{
    public function getKey(): string
    {
        return 'spy';
    }

    public function getName(): string
    {
        return 'Missions d\'Espionnage par Sondes (Mission 6)';
    }

    public function getDescription(): string
    {
        return 'Génère des envois de sondes d\'espionnage. Options: --direction=incoming (défaut) ou --direction=outgoing, --count=5, --interval=3.';
    }

    public function execute(array $options, bool $isCli): void
    {
        $db = Database::get();

        $direction = strtolower($options['direction'] ?? 'incoming'); // incoming | outgoing
        $count = isset($options['count']) ? max(1, (int)$options['count']) : 5;
        $eta = isset($options['eta']) ? max(1, (int)$options['eta']) : 5;
        $interval = isset($options['interval']) ? max(1, (int)$options['interval']) : 3;
        $userName = $options['user'] ?? 'Admin';

        $user = $db->selectSingle("SELECT id, username FROM %%USERS%% WHERE username = :name;", [
            ':name' => $userName
        ]);

        if (empty($user)) {
            echo "[ERROR] Joueur '{$userName}' non trouvé.\n";
            return;
        }

        $targetType = (isset($options['target_type']) && $options['target_type'] === 'moon') ? 3 : 1;

        if ($direction === 'outgoing' || $direction === 'out') {
            // OUTGOING: Le joueur cible espionne des planètes ennemies
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
                echo "[ERROR] Aucun ennemi trouvé pour effectuer les espionnages.\n";
                return;
            }

            echo "📡 🕵️ Injection de {$count} mission(s) d'espionnage sortante(s) depuis '{$user['username']}' [{$startPlanet['galaxy']}:{$startPlanet['system']}:{$startPlanet['planet']}] (Intervalle: {$interval}s)...\n";

            for ($i = 0; $i < $count; $i++) {
                $enemy = $enemies[$i % count($enemies)];
                $targetPlanet = $db->selectSingle(
                    "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :pType;",
                    [':ownerId' => $enemy['id'], ':pType' => $targetType]
                );

                if (empty($targetPlanet)) continue;

                $now = TIMESTAMP;
                $flightEta = $eta + ($i * $interval);
                $arrivalTime = $now + $flightEta;
                $returnTime = $arrivalTime + $flightEta;

                $ships = [
                    210 => mt_rand(1, 15), // Sondes d'espionnage (ID 210)
                ];

                ScenarioManager::ensureShips($startPlanet['id'], $ships);

                FleetFunctions::sendFleet(
                    $ships,
                    6, // Mission 6 = Espionnage
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
                    $returnTime
                );

                echo "  ➜ Espionnage sortant #".($i+1)." vers '{$enemy['username']}' [{$targetPlanet['galaxy']}:{$targetPlanet['system']}:{$targetPlanet['planet']}] | Survol dans {$flightEta}s.\n";
            }

            echo "✅ Missions d'espionnage sortantes injectées avec succès !\n";

        } else {
            // INCOMING (défaut): Des ennemis espionnent la planète/lune du joueur cible
            $targetPlanet = $db->selectSingle(
                "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :pType ORDER BY id ASC;",
                [':ownerId' => $user['id'], ':pType' => $targetType]
            );

            if (empty($targetPlanet)) {
                echo "[ERROR] Aucune planète/lune cible trouvée pour le joueur '{$userName}'.\n";
                return;
            }

            $enemies = $db->select("SELECT id, username FROM %%USERS%% WHERE id != :targetId AND id > 0;", [
                ':targetId' => $user['id']
            ]);

            if (empty($enemies)) {
                echo "[ERROR] Aucun joueur ennemi trouvé pour effectuer les espionnages.\n";
                return;
            }

            echo "⚠️ 📡 Injection de {$count} espionnage(s) entrant(s) sur '{$user['username']}' [{$targetPlanet['galaxy']}:{$targetPlanet['system']}:{$targetPlanet['planet']}] (Intervalle: {$interval}s)...\n";

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
                $returnTime = $arrivalTime + $flightEta;

                $ships = [
                    210 => mt_rand(1, 15), // Sondes d'espionnage (ID 210)
                ];

                ScenarioManager::ensureShips($startPlanet['id'], $ships);

                FleetFunctions::sendFleet(
                    $ships,
                    6, // Mission 6 = Espionnage
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
                    $returnTime
                );

                echo "  ➜ Espionnage entrant #".($i+1)." par '{$enemy['username']}' depuis [{$startPlanet['galaxy']}:{$startPlanet['system']}:{$startPlanet['planet']}] | Survol dans {$flightEta}s.\n";
            }

            echo "✅ Espionnages entrants injectés avec succès !\n";
        }
    }
}
