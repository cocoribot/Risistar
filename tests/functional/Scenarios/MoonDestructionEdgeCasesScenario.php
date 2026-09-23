<?php

/**
 * Risistar - Functional Scenario: Moon Destruction Edge Cases
 */

require_once __DIR__ . '/../ScenarioInterface.php';

class MoonDestructionEdgeCasesScenario implements ScenarioInterface
{
    public function getKey(): string
    {
        return 'moon_destruction_edge_cases';
    }

    public function getName(): string
    {
        return 'Cas Limites de Destruction de Lune (Flottes en vol)';
    }

    public function getDescription(): string
    {
        return 'Génère des scénarios critiques : flottes retournant sur une lune détruite en cours de vol, et flottes en vol vers une lune détruite avant l\'arrivée.';
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

        $pMoon = $db->selectSingle("SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :pType;", [
            ':ownerId' => $player['id'],
            ':pType' => 3
        ]);

        if (empty($pMoon)) {
            echo "[WARN] La cible n'a pas de lune. Création d'une lune temporaire pour le scénario...\n";
            $pPlanet = $db->selectSingle("SELECT galaxy, `system`, planet FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :pType;", [
                ':ownerId' => $player['id'],
                ':pType' => 1
            ]);
            if ($pPlanet) {
                $moonId = PlayerUtil::createMoon(1, $pPlanet['galaxy'], $pPlanet['system'], $pPlanet['planet'], $player['id'], 20, 8944, 'Lune Test EdgeCase');
                $pMoon = $db->selectSingle("SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id = :id;", [':id' => $moonId]);
            }
        }

        if (empty($pMoon)) {
            echo "[ERROR] Impossible de créer ou trouver une lune pour ce scénario.\n";
            return;
        }

        $otherPlanet = $db->selectSingle("SELECT id, id_owner, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner != :ownerId AND planet_type = :pType;", [
            ':ownerId' => $player['id'],
            ':pType' => 1
        ]);

        echo "🌕⚠️ Injection des cas limites de destruction de lune pour '{$player['username']}' sur la lune [{$pMoon['galaxy']}:{$pMoon['system']}:{$pMoon['planet']}]...\n";

        $now = TIMESTAMP;

        // 1. Scénario : Flotte en vol de RETOUR vers la Lune qui est sur le point d'être détruite
        $startTime = $now - 60; // Partie il y a 60s
        $stayTime = $now - 10;  // Arrivée sur cible dépassée
        $endTime = $now + $eta; // Arrivée du retour dans $eta secondes

        $s1 = [204 => 300, 206 => 50];
        ScenarioManager::ensureShips($pMoon['id'], $s1);

        FleetFunctions::sendFleet(
            $s1,
            1, // Attaque
            $player['id'],
            $pMoon['id'],
            $pMoon['galaxy'],
            $pMoon['system'],
            $pMoon['planet'],
            3, // Départ Lune
            $otherPlanet['id_owner'],
            $otherPlanet['id'],
            $otherPlanet['galaxy'],
            $otherPlanet['system'],
            $otherPlanet['planet'],
            1,
            [901 => 0, 902 => 0, 903 => 0],
            $startTime,
            $stayTime,
            $endTime
        );

        $lastFleetId = $db->selectSingle("SELECT fleet_id FROM %%FLEETS%% ORDER BY fleet_id DESC;", [], 'fleet_id');
        // Mettre la flotte en état de retour (fleet_mess = 1) et l'événement sur endTime
        $db->update("UPDATE %%FLEETS%% SET fleet_mess = 1 WHERE fleet_id = :fid;", [':fid' => $lastFleetId]);
        $db->update("UPDATE %%FLEETS_EVENT%% SET `time` = :endTime WHERE fleetID = :fid;", [':endTime' => $endTime, ':fid' => $lastFleetId]);

        echo "  ➜ [Cas 1] Flotte en vol de RETOUR vers la Lune (Fleet ID: {$lastFleetId}) | Arrivée retour dans {$eta}s.\n";

        // 2. Scénario : Flotte ennemie d'ATTAQUE vers la Lune
        $t2 = $now + $eta + 5;
        $s2 = [204 => 200, 207 => 20];
        ScenarioManager::ensureShips($otherPlanet['id'], $s2);

        FleetFunctions::sendFleet(
            $s2,
            1,
            $otherPlanet['id_owner'],
            $otherPlanet['id'],
            $otherPlanet['galaxy'],
            $otherPlanet['system'],
            $otherPlanet['planet'],
            1,
            $player['id'],
            $pMoon['id'],
            $pMoon['galaxy'],
            $pMoon['system'],
            $pMoon['planet'],
            3, // Arrivée Lune
            [901 => 0, 902 => 0, 903 => 0],
            $t2,
            $t2,
            $t2 + $eta
        );
        $lastFleetId2 = $db->selectSingle("SELECT fleet_id FROM %%FLEETS%% ORDER BY fleet_id DESC;", [], 'fleet_id');

        echo "  ➜ [Cas 2] Flotte d'attaque ennemie vers la Lune (Fleet ID: {$lastFleetId2}) | Arrivée dans ".($eta+5)."s.\n";
        echo "💡 Astuce : Vous pouvez maintenant exécuter '--scenario=moon_destruction --eta=3' pour détruire la lune AVANT l'arrivée de ces flottes et tester le re-routage automatique !\n";
        echo "✅ Injection des cas limites de lune réussie !\n";
    }
}
