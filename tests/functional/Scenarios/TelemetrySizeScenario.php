<?php

/**
 * Risistar - Functional Scenario: Télémétrie, taille des tables pour X joueurs actifs
 */

require_once __DIR__ . '/../ScenarioInterface.php';

class TelemetrySizeScenario implements ScenarioInterface
{
    // Comptes fictifs : les tables de télémétrie ne sont pas liées aux joueurs.
    private const FIRST_ACTOR = 800000;
    private const TABLES = ['daily', 'events', 'network', 'pairs'];

    public function getKey(): string
    {
        return 'telemetry_size';
    }

    public function getName(): string
    {
        return 'Télémétrie : taille des tables pour X joueurs actifs';
    }

    public function getDescription(): string
    {
        return 'Remplit la télémétrie avec des joueurs fictifs (3 sessions par jour, des livraisons, des combats, un joueur sur 10 très actif avec un script la nuit), mesure chaque table puis estime la taille pour 100 à 5000 joueurs. Options: --players=200, --days=7, --universe=1, --keep=1 pour garder les données.';
    }

    public function execute(array $options, bool $isCli): void
    {
        $players = isset($options['players']) ? max(10, (int)$options['players']) : 200;
        $days = isset($options['days']) ? max(1, (int)$options['days']) : 7;
        $universe = isset($options['universe']) ? (int)$options['universe'] : ROOT_UNI;
        $settings = TelemetrySettings::get($universe);
        $store = new TelemetryStore(TelemetryConnection::open());
        mt_srand(42);

        $this->clear($store, $universe);
        $before = $this->measure($store);

        $today = strtotime(gmdate('Y-m-d', TIMESTAMP) . ' UTC');
        $requests = 0;
        for ($player = 0; $player < $players; $player++) {
            $actor = self::FIRST_ACTOR + $player;
            $heavy = $player % 10 === 0;
            for ($day = $days; $day >= 1; $day--) {
                $events = $this->day($universe, $actor, $players, $today - $day * 86400, $heavy);
                $requests += count($events);
                if (!$store->write($events)) {
                    echo "[ERROR] Écriture refusée : la base de télémétrie est pleine (voir l'état dans l'admin).\n";
                    return;
                }
            }
        }
        echo "📡 {$players} joueurs fictifs sur {$days} jours : " . number_format($requests, 0, ',', ' ') . " requêtes.\n";

        $after = $this->measure($store);
        $playerDays = $players * $days;
        // Nombre de jours gardés par chaque table avec les réglages de l'univers.
        // Les paires gardent un total à vie : elles grandissent avec le nombre de partenaires.
        $kept = [
            'daily' => TelemetrySettings::DAILY_DAYS,
            'events' => TelemetrySettings::deliveryDays($settings),
            'network' => intdiv($settings['network_hours'] + 23, 24) + 1,
            'pairs' => $days,
        ];

        echo "\n📦 Mesure par table :\n";
        $perPlayer = [];
        foreach (self::TABLES as $table) {
            $rows = $this->rows($store, $universe, $table);
            $bytes = max(0, $after[$table] - $before[$table]);
            $perPlayer[$table] = $bytes / $playerDays * $kept[$table];
            printf(
                "  ➜ %-8s %8d lignes, %7.1f Ko, %5d octets par ligne, gardée %s\n",
                $table, $rows, $bytes / 1024, $rows ? $bytes / $rows : 0, $table === 'pairs' ? 'à vie' : $kept[$table] . ' jours'
            );
        }

        $limit = TelemetrySettings::BUDGET_MB - TelemetrySettings::RESERVE_MB;
        echo "\n📈 Estimation (Mo) avec ce mélange de joueurs :\n";
        printf("  %8s %9s %9s %9s %9s %9s\n", 'joueurs', 'daily', 'events', 'network', 'pairs', 'total');
        foreach ([100, 500, 1000, 2000, 5000] as $count) {
            $sizes = array_map(static fn($bytes) => $bytes * $count / 1048576, $perPlayer);
            printf(
                "  %8d %9.1f %9.1f %9.1f %9.1f %9.1f\n",
                $count, $sizes['daily'], $sizes['events'], $sizes['network'], $sizes['pairs'], array_sum($sizes)
            );
        }
        echo "  Limite : {$limit} Mo pour toute la base si elle est partagée avec le jeu, sinon pour la télémétrie seule.\n";

        if (empty($options['keep'])) {
            $this->clear($store, $universe);
            echo "🧹 Données fictives supprimées (--keep=1 pour les garder).\n";
        }
    }

    /** Une journée : 3 sessions, un joueur sur 10 joue 16 h et recharge la page 4 h la nuit. */
    private function day(int $universe, int $actor, int $players, int $midnight, bool $heavy): array
    {
        $events = [];
        $ip = '192.0.2.' . ($actor % 200 + mt_rand(0, 1));
        $client = mt_rand(0, 4) ? 'Firefox · Windows · desktop' : 'Chrome · Android · mobile';
        $sessions = $heavy ? [[7, 16]] : [[mt_rand(7, 10), 0.5], [mt_rand(12, 14), 0.5], [mt_rand(19, 22), 1]];
        foreach ($sessions as [$hour, $length]) {
            $start = $midnight + $hour * 3600 + mt_rand(0, 1800);
            for ($at = $start; $at < $start + $length * 3600; $at += mt_rand(5, 60)) {
                $events[] = $this->event($universe, $actor, $at, $this->kind(), $ip, $client);
            }
        }
        if ($heavy) {
            for ($at = $midnight + 3600; $at < $midnight + 5 * 3600; $at += mt_rand(120, 300)) {
                $events[] = $this->event($universe, $actor, $at, 'reload', $ip, $client);
            }
        }
        for ($i = mt_rand(0, 2); $i > 0; $i--) {
            $target = self::FIRST_ACTOR + mt_rand(0, $players - 1);
            if ($target !== $actor) {
                $events[] = $this->event($universe, $actor, $midnight + mt_rand(0, 86399), 'delivery', null, null, $target, [
                    'metal' => mt_rand(0, 500000), 'crystal' => mt_rand(0, 250000), 'deuterium' => mt_rand(0, 100000),
                    'planet' => mt_rand(1, 99999), 'mission' => 3,
                ]);
            }
        }
        if (mt_rand(0, 1)) {
            $events[] = $this->event($universe, $actor, $midnight + mt_rand(0, 86399), 'combat', null, null, self::FIRST_ACTOR + mt_rand(0, $players - 1), [
                'planet' => mt_rand(1, 99999), 'moon_chance' => mt_rand(0, 20),
            ]);
        }
        return $events;
    }

    /** Mélange des pages et actions d'un joueur normal. */
    private function kind(): string
    {
        $roll = mt_rand(1, 100);
        return match (true) {
            $roll <= 50 => 'interaction',
            $roll <= 60 => 'reload',
            $roll <= 75 => 'planet.switch',
            $roll <= 85 => 'galaxy.view',
            $roll <= 90 => 'fleet.send',
            $roll <= 95 => 'alliance.view',
            default => 'passive',
        };
    }

    private function event(int $universe, int $actor, int $at, string $kind, ?string $ip, ?string $client, int $target = 0, array $data = []): array
    {
        return [
            'request_id' => bin2hex(random_bytes(16)),
            'universe' => $universe,
            'actor' => $actor,
            'target' => $target,
            'at' => $at,
            'kind' => $kind,
            'data' => $data,
            'interactive' => !in_array($kind, ['delivery', 'combat', 'passive'], true),
            'ip' => $ip,
            'client' => $client,
        ];
    }

    /** Taille allouée de chaque table, en octets. */
    private function measure(TelemetryStore $store): array
    {
        $names = TelemetryConnection::tables();
        $version = $store->query('SELECT VERSION()')->fetchColumn();
        // MySQL 8 garde la taille des tables en cache pendant un jour.
        if (!str_contains($version, 'MariaDB') && version_compare($version, '8.0', '>=')) {
            $store->query('SET SESSION information_schema_stats_expiry=0');
        }
        $sizes = [];
        foreach (self::TABLES as $table) {
            $name = $names['%%TELEMETRY_' . strtoupper($table) . '%%'];
            $store->query('ANALYZE TABLE ' . $name)->fetchAll();
            $sizes[$table] = (int)$store->query(
                'SELECT DATA_LENGTH+INDEX_LENGTH FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',
                [trim($name, '`')]
            )->fetchColumn();
        }
        return $sizes;
    }

    private function rows(TelemetryStore $store, int $universe, string $table): int
    {
        $actor = $table === 'pairs' ? 'pair_a' : 'actor';
        return (int)$store->query(
            'SELECT COUNT(*) FROM %%TELEMETRY_' . strtoupper($table) . "%% WHERE universe=? AND {$actor}>=?",
            [$universe, self::FIRST_ACTOR]
        )->fetchColumn();
    }

    private function clear(TelemetryStore $store, int $universe): void
    {
        foreach (self::TABLES as $table) {
            $actor = $table === 'pairs' ? 'pair_a' : 'actor';
            $store->query('DELETE FROM %%TELEMETRY_' . strtoupper($table) . "%% WHERE universe=? AND {$actor}>=?", [$universe, self::FIRST_ACTOR]);
        }
    }
}
