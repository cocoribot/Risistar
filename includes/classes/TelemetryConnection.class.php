<?php

final class TelemetryConnection
{
    public static function configuration(): array
    {
        $telemetry = [];
        require defined('DATABASE_CONFIG_FILE') ? DATABASE_CONFIG_FILE : ROOT_PATH . 'includes/config.php';
        return $telemetry;
    }

    public static function masterEnabled(): bool
    {
        return !empty(self::configuration()['enabled']);
    }

    public static function open(): PDO
    {
        $c = self::configuration();
        foreach (['host', 'port', 'databasename', 'user', 'userpw'] as $key) {
            if (!isset($c[$key])) {
                throw new RuntimeException('Connexion télémétrie non configurée.');
            }
        }
        // Let the one-second server lock timeout arrive before the socket read deadline.
        $previous = ini_set('mysqlnd.net_read_timeout', '2');
        try {
            $db = new PDO('mysql:host=' . $c['host'] . ';port=' . (int)$c['port']
                . ';dbname=' . $c['databasename'] . ';charset=utf8mb4', $c['user'], $c['userpw'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 1,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false,
                ]);
            $db->exec("SET SESSION innodb_lock_wait_timeout=1, lock_wait_timeout=1, time_zone='+00:00'");
            return $db;
        } finally {
            if ($previous !== false) {
                ini_set('mysqlnd.net_read_timeout', $previous);
            }
        }
    }
}
