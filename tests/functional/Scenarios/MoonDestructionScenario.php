<?php

/**
 * Risistar - Functional Scenario: Moon Destruction (Mission 9)
 */

require_once __DIR__ . '/../ScenarioInterface.php';

class MoonDestructionScenario implements ScenarioInterface
{
    public function getKey(): string
    {
        return 'moon_destruction';
    }

    public function getName(): string
    {
        return 'Attaque de Destruction de Lune (RIP / Mission 9)';
    }

    public function getDescription(): string
    {
        return 'Envoie une flotte d\'Étoiles de la Mort (RIP / ID 214) avec mission 9 pour détruire la lune du joueur cible.';
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

        // Trouver une lune de la cible
        $targetMoon = $db->selectSingle("SELECT id, id_owner, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :pType;", [
            ':ownerId' => $targetUser['id'],
            ':pType' => 3
        ]);

        if (empty($targetMoon)) {
            echo "[WARN] La cible '{$targetName}' n'a pas encore de lune. Recherche d'une autre lune...\n";
            $targetMoon = $db->selectSingle("SELECT id, id_owner, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE planet_type = :pType;", [
                ':pType' => 3
            ]);
        }

        if (empty($targetMoon)) {
            echo "[ERROR] Aucune lune trouvée dans l'univers.\n";
            return;
        }

        $attacker = $db->selectSingle("SELECT id, username FROM %%USERS%% WHERE id != :targetId;", [
            ':targetId' => $targetMoon['id_owner']
        ]);

        $attackerPlanet = $db->selectSingle("SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = :pType;", [
            ':ownerId' => $attacker['id'],
            ':pType' => 1
        ]);

        echo "🌕💥 Envoi d'une mission de destruction de lune (Mission 9) par '{$attacker['username']}' vers la lune de '{$targetName}' [{$targetMoon['galaxy']}:{$targetMoon['system']}:{$targetMoon['planet']}] (ETA: {$eta}s)...\n";

        $now = TIMESTAMP;
        $arrivalTime = $now + $eta;
        $endTime = $arrivalTime + $eta;

        $ships = [
            214 => mt_rand(5, 20), // Étoiles de la Mort (RIP)
            206 => 50,             // Croiseurs
        ];

        ScenarioManager::ensureShips($attackerPlanet['id'], $ships);

        FleetFunctions::sendFleet(
            $ships,
            9, // Destruction de lune
            $attacker['id'],
            $attackerPlanet['id'],
            $attackerPlanet['galaxy'],
            $attackerPlanet['system'],
            $attackerPlanet['planet'],
            $attackerPlanet['planet_type'],
            $targetMoon['id_owner'],
            $targetMoon['id'],
            $targetMoon['galaxy'],
            $targetMoon['system'],
            $targetMoon['planet'],
            $targetMoon['planet_type'],
            [901 => 0, 902 => 0, 903 => 0],
            $arrivalTime,
            $arrivalTime,
            $endTime
        );

        echo "✅ Mission de destruction de lune (Mission 9) injectée avec succès ! Arrivée estimée dans {$eta}s.\n";
    }
}
