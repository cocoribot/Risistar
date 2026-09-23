<?php

/**
 * Risistar - Functional Scenario: Expedition (Mission 15)
 */

require_once __DIR__ . '/../ScenarioInterface.php';

class ExpeditionScenario implements ScenarioInterface
{
    public function getKey(): string
    {
        return 'expedition';
    }

    public function getName(): string
    {
        return 'Missions d\'Expédition Spatiale (Position 16 / Mission 15)';
    }

    public function getDescription(): string
    {
        return 'Envoie des flottes en expédition vers la position 16. Options: --fleet_size=small|medium|large|massive (défaut: medium), --count=10, --interval=3, --eta=5, --stay=60.';
    }

    public function execute(array $options, bool $isCli): void
    {
        $db = Database::get();

        $count = isset($options['count']) ? max(1, (int)$options['count']) : 10;
        $eta = isset($options['eta']) ? max(1, (int)$options['eta']) : 5;
        $interval = isset($options['interval']) ? max(1, (int)$options['interval']) : 3;
        $stay = isset($options['stay']) ? max(10, (int)$options['stay']) : 60; // Durée sur place en secondes (défaut: 60s)
        $userName = $options['user'] ?? 'Admin';
        $fleetSize = strtolower((string)($options['fleet_size'] ?? $options['size'] ?? 'medium'));

        $user = $db->selectSingle("SELECT id, username FROM %%USERS%% WHERE username = :name;", [
            ':name' => $userName
        ]);

        if (empty($user)) {
            echo "[ERROR] Joueur '{$userName}' non trouvé.\n";
            return;
        }

        $startPlanet = $db->selectSingle(
            "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = 1 ORDER BY id ASC;",
            [':ownerId' => $user['id']]
        );

        if (empty($startPlanet)) {
            echo "[ERROR] Aucune planète trouvée pour le joueur '{$userName}'.\n";
            return;
        }

        // Configuration du modèle de flotte selon la taille demandée
        switch ($fleetSize) {
            case 'small':
            case 'petite':
                $shipsTemplate = [202 => 10, 203 => 5, 204 => 20, 210 => 2];
                $sizeName = "Petite (small)";
                break;
            case 'large':
            case 'grande':
                $shipsTemplate = [202 => 200, 203 => 100, 204 => 500, 206 => 100, 207 => 50, 210 => 10, 215 => 30, 213 => 5];
                $sizeName = "Grande (large)";
                break;
            case 'massive':
            case 'titanesque':
                $shipsTemplate = [202 => 1000, 203 => 500, 204 => 3000, 206 => 500, 207 => 200, 210 => 20, 215 => 100, 213 => 50, 214 => 1];
                $sizeName = "Titanesque (massive)";
                break;
            case 'medium':
            case 'moyenne':
            default:
                $shipsTemplate = [202 => 50, 203 => 20, 204 => 100, 206 => 25, 210 => 5, 215 => 10];
                $sizeName = "Moyenne (medium)";
                break;
        }

        echo "🚀 🌌 Injection de {$count} expédition(s) spatiale(s) [Taille: {$sizeName}] depuis '{$user['username']}' [{$startPlanet['galaxy']}:{$startPlanet['system']}:{$startPlanet['planet']}] vers [{$startPlanet['galaxy']}:{$startPlanet['system']}:16] (Intervalle: {$interval}s, Séjour: {$stay}s)...\n";

        for ($i = 0; $i < $count; $i++) {
            $now = TIMESTAMP;
            $flightEta = $eta + ($i * $interval);
            $arrivalTime = $now + $flightEta;
            $endStayTime = $arrivalTime + $stay;
            $returnTime = $endStayTime + $flightEta;

            $ships = $shipsTemplate;

            ScenarioManager::ensureShips($startPlanet['id'], $ships);

            FleetFunctions::sendFleet(
                $ships,
                15, // Mission 15 = Expédition
                $user['id'],
                $startPlanet['id'],
                $startPlanet['galaxy'],
                $startPlanet['system'],
                $startPlanet['planet'],
                $startPlanet['planet_type'],
                $user['id'],
                0, // Target Planet ID = 0 pour position 16
                $startPlanet['galaxy'],
                $startPlanet['system'],
                16, // Position 16 (Expédition)
                1,
                [901 => 0, 902 => 0, 903 => 0],
                $arrivalTime,
                $endStayTime,
                $returnTime
            );

            echo "  ➜ Expédition #".($i+1)." [{$sizeName}] envoyée vers [{$startPlanet['galaxy']}:{$startPlanet['system']}:16] | Arrivée dans {$flightEta}s | Fin de séjour dans ".($flightEta + $stay)."s.\n";
        }

        echo "✅ Toutes les missions d'expédition [{$sizeName}] ont été injectées avec succès !\n";
    }
}
