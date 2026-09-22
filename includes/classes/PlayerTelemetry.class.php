<?php

final class PlayerTelemetry
{
    private static array $settings = [];
    private static array $ready = [];
    private static ?string $request = null;
    private static int $queued = 0;

    public static function enabled(int $universe): bool
    {
        try {
            if (!array_key_exists($universe, self::$settings)) {
                self::$settings[$universe] = TelemetryConnection::masterEnabled()
                    ? TelemetrySettings::get($universe) : ['enabled' => 0];
            }
            return !empty(self::$settings[$universe]['enabled']);
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function passive(array $query): bool
    {
        $page = strtolower((string)($query['page'] ?? 'overview'));
        return (in_array($page, ['buildings', 'research', 'overview', 'shipyard'], true)
                && ($query['passive_reload'] ?? '') === 'queue');
    }

    public static function interaction(int $actor, int $universe): void
    {
        if (!self::passive($_GET + $_POST)) {
            self::record($actor, $universe, 'interaction', 0, 0, [], true);
        }
    }

    public static function action(string $kind, int $target = 0, int $fleet = 0, array $data = []): void
    {
        global $USER;
        if (empty($USER['id']) || empty($USER['universe'])) {
            return;
        }
        self::record((int)$USER['id'], (int)$USER['universe'], $kind, $target,
            $fleet, $data, true);
    }

    public static function client(string $agent): string
    {
        $browser = match (true) {
            preg_match('~Edg(?:e|A|iOS)?/~', $agent) === 1 => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Firefox/') || str_contains($agent, 'FxiOS/') => 'Firefox',
            str_contains($agent, 'Chrome/') || str_contains($agent, 'CriOS/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => 'Autre navigateur',
        };
        $platform = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Autre système',
        };
        $device = match (true) {
            str_contains($agent, 'iPad') || ($platform === 'Android' && !str_contains($agent, 'Mobile')) => 'tablette',
            str_contains($agent, 'Mobile') || str_contains($agent, 'iPhone') => 'mobile',
            in_array($platform, ['Windows', 'macOS', 'Linux'], true) => 'ordinateur',
            default => 'type inconnu',
        };
        return $browser.' · '.$platform.' · '.$device;
    }

    public static function record(int $actor, int $universe, string $kind, int $target = 0,
        int $fleetId = 0, array $data = [], bool $interactive = false, ?int $at = null): void
    {
        if ($actor <= 0 || !self::enabled($universe)) {
            return;
        }
        try {
            $at ??= time();
            if (++self::$queued > 256) {
                TelemetryStore::health(['failure' => 'request_limit', 'gap_start' => $at]);
                return;
            }
            self::$request ??= bin2hex(random_bytes(16));
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $network = $interactive && self::$settings[$universe]['network_enabled'];
            if ($network && !empty($_SERVER['HTTP_USER_AGENT'])) {
                $data['client'] = self::client(substr($_SERVER['HTTP_USER_AGENT'], 0, 1024));
            }
            $event = [
                'event_key' => self::$request . '-' . self::$queued,
                'request_id' => self::$request, 'universe' => $universe, 'actor' => $actor,
                'target' => $target, 'at' => $at, 'kind' => $kind, 'result' => 'success',
                'fleet_id' => $fleetId, 'interactive' => $interactive,
                'ip' => $network && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null,
                'data' => $data,
            ];
            // The callback only moves memory. SQL is deferred until all game locks are gone.
            Database::get()->afterCommit(static function() use ($event) {
                self::$ready[] = $event;
            });
        } catch (Throwable $e) {
            TelemetryStore::health(['failure' => 'buffer_failed', 'gap_start' => time()]);
        }
    }

    public static function flush(): void
    {
        if (!self::$ready || Database::get()->inTransaction()) {
            return;
        }
        $events = self::$ready;
        self::$ready = [];
        try {
            $store = new TelemetryStore(TelemetryConnection::open());
            $store->write($events, self::$settings);
            TelemetryStore::health(['success' => time()]);
        } catch (Throwable $e) {
            // Keep connection details and SQL out of the admin health record.
            TelemetryStore::health(['failure' => 'write_failed', 'gap_start' => min(array_column($events, 'at'))]);
            error_log('Telemetry collection failed (' . get_class($e) . '). Gameplay already persisted.');
        }
    }
}
