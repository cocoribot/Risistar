<?php

final class TelemetryConnection
{
	private static ?array $configuration = null;
	private static string $prefix = '';
	private static bool $shared = true;

	public static function configure(array $game, array $telemetry): void
	{
		self::$shared = empty($telemetry['databasename']);
		self::$configuration = self::$shared ? $game : $telemetry;
		self::$prefix = self::$shared ? $game['tableprefix'] : ($telemetry['tableprefix'] ?? '');
	}

	public static function configuration(): array
	{
		if (self::$configuration === null) {
			Database::get();
		}
		return self::$configuration;
	}

	public static function shared(): bool
	{
		self::configuration();
		return self::$shared;
	}

	public static function tables(): array
	{
		self::configuration();
		$tables = [];
		foreach (['daily', 'events', 'network', 'pairs', 'warnings', 'audit'] as $name) {
			$tables['%%TELEMETRY_' . strtoupper($name) . '%%'] = '`' . str_replace('`', '``', self::$prefix) . 'telemetry_' . $name . '`';
		}
		return $tables;
	}

	/** Each query may take at most $readTimeout seconds, so a stuck database cannot hold a request. */
	public static function open(int $readTimeout = 30): PDO
	{
		$c = self::configuration();
		foreach (['host', 'port', 'databasename', 'user', 'userpw'] as $key) {
			if (!isset($c[$key])) {
				throw new RuntimeException('Telemetry connection is incomplete.');
			}
		}
		// A separate connection keeps telemetry out of game transactions, even in the same database.
		$previous = ini_set('mysqlnd.net_read_timeout', (string) $readTimeout);
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
			ini_set('mysqlnd.net_read_timeout', $previous);
		}
	}
}
