<?php

/**
 * Risistar - Functional Scenario: Attack (Incoming / Outgoing)
 */

require_once __DIR__ . '/../ScenarioInterface.php';

class AttackScenario implements ScenarioInterface
{
    public function getKey(): string
    {
        return 'attack';
    }

    public function getName(): string
    {
        return 'Attaque de Flotte (Entrante / Sortante)';
    }

    public function getDescription(): string
    {
        return 'Génère un raid d\'attaque. Options: --direction=incoming (défaut, attaques ennemies vers vous) ou --direction=outgoing (votre raid vers un ennemi).';
    }

    public function execute(array $options, bool $isCli): void
    {
        $db = Database::get();

        $direction = strtolower($options['direction'] ?? 'incoming'); // incoming | outgoing
        $count = isset($options['count']) ? max(1, (int)$options['count']) : 3;
        $eta = isset($options['eta']) ? max(1, (int)$options['eta']) : 5;
        $interval = isset($options['interval']) ? max(1, (int)$options['interval']) : 5;
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
            // OUTGOING: Le joueur cible lance des raids vers des ennemis
            $startPlanet = $db->selectSingle(
                "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :pType;",
                [':ownerId' => $user['id'], ':pType' => $targetType]
            );

            if (empty($startPlanet)) {
                $startPlanet = $db->selectSingle(
                    "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = 1;",
                    [':ownerId' => $user['id']]
                );
            }

            if (empty($startPlanet)) {
                echo "[ERROR] Aucune planète de départ trouvée pour le joueur '{$userName}'.\n";
                return;
            }

            $enemies = $db->select("SELECT id, username FROM %%USERS%% WHERE id != :userId AND id > 0;", [
                ':userId' => $user['id']
            ]);

            if (empty($enemies)) {
                echo "[ERROR] Aucun ennemi trouvé pour lancer les raids sortants.\n";
                return;
            }

            echo "🚀 Injection de {$count} raid(s) sortant(s) depuis '{$user['username']}' [{$startPlanet['galaxy']}:{$startPlanet['system']}:{$startPlanet['planet']}] (ETA: {$eta}s)...\n";

            for ($i = 0; $i < $count; $i++) {
                $enemy = $enemies[$i % count($enemies)];
                $targetPlanet = $db->selectSingle(
                    "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = 1;",
                    [':ownerId' => $enemy['id']]
                );

                if (empty($targetPlanet)) continue;

                $now = TIMESTAMP;
                $flightEta = $eta + ($i * $interval);
                $startTime = $now + $flightEta;
                $endTime = $startTime + $flightEta;

                $ships = [
                    204 => mt_rand(200, 500), // Chasseur léger
                    206 => mt_rand(50, 100),  // Croiseur
                    207 => mt_rand(20, 50),   // Vaisseau de bataille
                    215 => mt_rand(10, 20),   // Traqueur
                ];

                ScenarioManager::ensureShips($startPlanet['id'], $ships);

                FleetFunctions::sendFleet(
                    $ships,
                    1, // Attaque
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
                    $startTime,
                    $startTime,
                    $endTime
                );

                echo "  ➜ Raid sortant #".($i+1)." vers '{$enemy['username']}' [{$targetPlanet['galaxy']}:{$targetPlanet['system']}:{$targetPlanet['planet']}] | Arrivée dans {$flightEta}s.\n";
            }

            echo "✅ Raids sortants injectés avec succès !\n";

        } else {
            // INCOMING (défaut): Des ennemis lancent des attaques vers le joueur cible
            $targetPlanet = $db->selectSingle(
                "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :pType;",
                [':ownerId' => $user['id'], ':pType' => $targetType]
            );

            if (empty($targetPlanet)) {
                echo "[ERROR] Aucune destination (Type {$targetType}) trouvée pour le joueur '{$userName}'.\n";
                return;
            }

            $enemies = $db->select("SELECT id, username FROM %%USERS%% WHERE id != :targetId AND id > 0;", [
                ':targetId' => $user['id']
            ]);

            if (empty($enemies)) {
                echo "[ERROR] Aucun joueur ennemi trouvé pour lancer les attaques.\n";
                return;
            }

            echo "⚔️ Injection de {$count} attaque(s) entrante(s) sur '{$user['username']}' [{$targetPlanet['galaxy']}:{$targetPlanet['system']}:{$targetPlanet['planet']}] (ETA: {$eta}s)...\n";

            for ($i = 0; $i < $count; $i++) {
                $enemy = $enemies[$i % count($enemies)];
                $startPlanet = $db->selectSingle(
                    "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = 1;",
                    [':ownerId' => $enemy['id']]
                );

                if (empty($startPlanet)) continue;

                $now = TIMESTAMP;
                $flightEta = $eta + ($i * $interval);
                $startTime = $now + $flightEta;
                $endTime = $startTime + $flightEta;

                $ships = [
                    204 => mt_rand(100, 300), // Chasseur léger
                    205 => mt_rand(30, 80),   // Chasseur lourd
                    206 => mt_rand(20, 50),   // Croiseur
                    207 => mt_rand(10, 30),   // Vaisseau de bataille
                ];

                ScenarioManager::ensureShips($startPlanet['id'], $ships);

                FleetFunctions::sendFleet(
                    $ships,
                    1, // Attaque
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
                    $startTime,
                    $startTime,
                    $endTime
                );

                echo "  ➜ Attaque entrante #".($i+1)." par '{$enemy['username']}' envoyée depuis {$startPlanet['galaxy']}:{$startPlanet['system']}:{$startPlanet['planet']} | Arrivée dans {$flightEta}s.\n";
            }

            echo "✅ Injection des attaques entrantes réussie !\n";
        }
    }
}
