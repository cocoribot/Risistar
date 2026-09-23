<?php

/**
 * Risistar - Functional Scenario: Télémétrie (activité, rythme régulier, push)
 */

require_once __DIR__ . '/../ScenarioInterface.php';
require_once ROOT_PATH . 'includes/classes/TelemetryDetectors.class.php';
require_once ROOT_PATH . 'includes/classes/TelemetryReview.class.php';

class TelemetryScenario implements ScenarioInterface
{
    public function getKey(): string
    {
        return 'telemetry';
    }

    public function getName(): string
    {
        return 'Télémétrie : historique suspect et avertissements admin';
    }

    public function getDescription(): string
    {
        return 'Active le module, crée 4 journées de 21 h, un rythme de 30 s, des rechargements sans action et une livraison non remboursée, puis lance l\'analyse. Options: --user=Admin, --partner=PlayerOne, --eta=30.';
    }

    public function execute(array $options, bool $isCli): void
    {
        $db = Database::get();
        $userName = $options['user'] ?? 'Admin';
        $partnerName = $options['partner'] ?? 'PlayerOne';
        $eta = isset($options['eta']) ? max(1, (int)$options['eta']) : 30;

        $user = $db->selectSingle("SELECT id, username, universe FROM %%USERS%% WHERE username = :name;", [':name' => $userName]);
        $partner = $db->selectSingle("SELECT id, username, universe FROM %%USERS%% WHERE username = :name;", [':name' => $partnerName]);

        if (empty($user) || empty($partner) || $user['id'] == $partner['id']) {
            echo "[ERROR] Il faut deux joueurs différents ('{$userName}' et '{$partnerName}').\n";
            return;
        }

        $universe = (int)$user['universe'];
        $config = Config::get($universe);
        $modules = array_pad(explode(';', $config->moduls), MODULE_AMOUNT, 1);
        $modules[MODULE_TELEMETRY] = 1;
        $config->moduls = implode(';', $modules);
        $config->save();
        echo "📡 Module télémétrie activé sur l'univers {$universe}.\n";

        $store = new TelemetryStore(TelemetryConnection::open());
        $settings = TelemetrySettings::get($universe);

        // Relancer le scénario repart d'un historique propre pour ces deux joueurs.
        foreach (['EVENTS', 'DAILY', 'NETWORK', 'WARNINGS'] as $table) {
            $store->query("DELETE FROM %%TELEMETRY_{$table}%% WHERE universe = ? AND actor IN (?, ?)", [$universe, $user['id'], $partner['id']]);
        }
        $store->query("DELETE FROM %%TELEMETRY_PAIRS%% WHERE universe = ? AND pair_a = ? AND pair_b = ?", [$universe, min($user['id'], $partner['id']), max($user['id'], $partner['id'])]);

        $today = strtotime(gmdate('Y-m-d', TIMESTAMP) . ' UTC');
        $events = [];

        // 4 journées de 21 h de jeu : une page toutes les 1 à 4 minutes, parfois la galaxie.
        for ($day = 4; $day >= 1; $day--) {
            $start = $today - $day * 86400 + 3600;
            for ($at = $start; $at < $start + 21 * 3600 - 300; $at += mt_rand(60, 240)) {
                $events[] = $this->event($universe, (int)$user['id'], $at, mt_rand(0, 4) ? 'interaction' : 'galaxy.view');
            }
        }
        echo "  ➜ Activité : 4 journées de 21 h pour '{$user['username']}'.\n";

        // Aujourd'hui, 40 actions espacées d'exactement 30 s.
        $start = TIMESTAMP - 7200;
        for ($i = 0; $i < 40; $i++) {
            $events[] = $this->event($universe, (int)$user['id'], $start + $i * 30, $i % 2 ? 'fleet.send' : 'galaxy.view');
        }
        echo "  ➜ Rythme : 40 actions toutes les 30 s pour '{$user['username']}'.\n";

        // 3 nuits de rechargements toutes les 2 à 5 minutes sans action, et du jeu normal le soir.
        for ($day = 3; $day >= 1; $day--) {
            $midnight = $today - $day * 86400;
            for ($at = $midnight + 3600; $at < $midnight + 5 * 3600; $at += mt_rand(120, 300)) {
                $events[] = $this->event($universe, (int)$partner['id'], $at, 'passive');
            }
            for ($at = $midnight + 19 * 3600; $at < $midnight + 21 * 3600; $at += mt_rand(5, 90)) {
                $events[] = $this->event($universe, (int)$partner['id'], $at, mt_rand(0, 3) ? 'interaction' : 'galaxy.view');
            }
        }
        echo "  ➜ Rafraîchissement : 3 nuits de rechargements aléatoires sans action pour '{$partner['username']}', jeu normal le soir.\n";

        // Une livraison de 4M de métal il y a 3 jours, jamais remboursée.
        $events[] = $this->event($universe, (int)$user['id'], TIMESTAMP - 3 * 86400, 'delivery', (int)$partner['id'], [
            'metal' => 4000000, 'crystal' => 0, 'deuterium' => 0, 'planet' => 0,
        ]);
        echo "  ➜ Push : 4 000 000 de métal livrés à '{$partner['username']}' il y a 3 jours, sans retour.\n";

        foreach (array_chunk($events, 256) as $batch) {
            if (!$store->write($batch)) {
                echo "[ERROR] Écriture refusée : la base de télémétrie est pleine (voir l'état dans l'admin).\n";
                return;
            }
        }

        $this->sendTransport($user, $partner, $eta);

        $review = new TelemetryReview($store);
        $result = ['cursor' => 0, 'pair_a' => 0, 'pair_b' => 0, 'incomplete' => true];
        while ($result['incomplete']) {
            $result = $review->evaluate($universe, $settings, TIMESTAMP, $result['cursor'], $result['pair_a'], $result['pair_b']);
        }

        echo "🔎 Analyse terminée. Avertissements :\n";
        foreach ([$user, $partner] as $player) {
            foreach ($store->accountWarnings($universe, (int)$player['id'], PHP_INT_MAX) as $warning) {
                if ((int)$warning['actor'] === (int)$player['id']) {
                    echo "  ➜ #{$warning['id']} {$player['username']} : {$warning['kind']} ({$warning['strength']})\n";
                }
            }
        }

        echo "✅ À vérifier dans l'admin : admin.php?page=telemetry&account={$user['id']}&other={$partner['id']}\n";
    }

    /** Un vrai transport : la livraison est enregistrée par le jeu à l'arrivée de la flotte. */
    private function sendTransport(array $user, array $partner, int $eta): void
    {
        $db = Database::get();
        $from = $db->selectSingle(
            "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = 1 ORDER BY id ASC;",
            [':ownerId' => $user['id']]
        );
        $to = $db->selectSingle(
            "SELECT id, galaxy, `system`, planet, planet_type FROM %%PLANETS%% WHERE id_owner = :ownerId AND planet_type = 1 ORDER BY id ASC;",
            [':ownerId' => $partner['id']]
        );

        if (empty($from) || empty($to)) {
            echo "[ERROR] Planète introuvable pour le transport.\n";
            return;
        }

        $ships = [202 => 10]; // Petits transporteurs
        ScenarioManager::ensureShips($from['id'], $ships);

        $arrival = TIMESTAMP + $eta;
        FleetFunctions::sendFleet(
            $ships,
            3, // Mission 3 = Transport
            $user['id'],
            $from['id'],
            $from['galaxy'],
            $from['system'],
            $from['planet'],
            $from['planet_type'],
            $partner['id'],
            $to['id'],
            $to['galaxy'],
            $to['system'],
            $to['planet'],
            $to['planet_type'],
            [901 => 50000, 902 => 0, 903 => 0],
            $arrival,
            $arrival,
            $arrival + $eta
        );

        echo "  ➜ Transport réel de 50 000 métal vers '{$partner['username']}' : arrivée dans {$eta}s, puis visible dans la chronologie.\n";
    }

    private function event(int $universe, int $actor, int $at, string $kind, int $target = 0, array $data = []): array
    {
        return [
            'request_id' => bin2hex(random_bytes(16)),
            'universe' => $universe,
            'actor' => $actor,
            'target' => $target,
            'at' => $at,
            'kind' => $kind,
            'data' => $data,
            'interactive' => !in_array($kind, ['delivery', 'passive'], true),
            'ip' => null,
            'client' => null,
        ];
    }
}
