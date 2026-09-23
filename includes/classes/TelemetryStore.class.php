<?php

class TelemetryStore
{
	public const PAGE_SIZE = 50;

	private const EVENT_COLUMNS = ['universe', 'actor', 'target', 'pair_a', 'pair_b', 'at', 'kind', 'data'];
	// Only exchanges between players are kept one by one; everything else is counted per day.
	private const EVIDENCE_KINDS = ['delivery', 'combat'];
	// Loads that keep the planet activity (*) alive or watch fleets, without playing.
	private const KEEP_ALIVE_KINDS = ['reload', 'planet.switch', 'alliance.view', 'passive'];
	private const UNAVAILABLE = ['unavailable' => true, 'suspended' => true, 'failure' => 'health_unavailable'];

	public function __construct(public PDO $db)
	{
	}

	public function query(string $sql, array $values = []): PDOStatement
	{
		$stmt = $this->db->prepare(strtr($sql, TelemetryConnection::tables()));
		$stmt->execute($values);
		return $stmt;
	}

	/** Small shared state: when to retry, the measured space, and where the analysis stopped. */
	public static function health(array $change = []): array
	{
		$directory = (defined('CACHE_PATH') ? CACHE_PATH : ROOT_PATH . 'cache/') . 'telemetry/';
		if (!is_dir($directory)) {
			@mkdir($directory, 0770, true);
		}
		$lock = @fopen($directory . 'health.lock', 'c');
		if (!$lock || !flock($lock, $change ? LOCK_EX : LOCK_SH)) {
			// Without this file the used space is unknown, so collection stays paused.
			return self::UNAVAILABLE;
		}
		try {
			$file = $directory . 'health.json';
			$saved = is_file($file) ? file_get_contents($file) : '';
			$state = $saved === '' ? [] : json_decode((string) $saved, true);
			if (!is_array($state)) {
				if (!$change) {
					return self::UNAVAILABLE;
				}
				$state = ['suspended' => true];
			}
			if ($change) {
				$state = array_filter(array_replace($state, $change), static fn($value) => $value !== null);
				// A new file replaces the old one, so a crash never leaves half a file.
				$json = json_encode($state, JSON_UNESCAPED_UNICODE);
				if (file_put_contents($file . '.tmp', $json) !== strlen($json) || !rename($file . '.tmp', $file)) {
					return self::UNAVAILABLE;
				}
			}
			return $state;
		} finally {
			flock($lock, LOCK_UN);
			fclose($lock);
		}
	}

	public function write(array $events): bool
	{
		// The cron measures the used space. When the shared database is full, nothing is written;
		// a separate database still keeps activity time.
		$health = self::health();
		if (TelemetryConnection::shared() && !empty($health['suspended'])) {
			return false;
		}
		$days = [];
		$network = [];
		$rows = [];
		foreach ($events as $event) {
			if (in_array($event['kind'], self::EVIDENCE_KINDS, true)) {
				$rows[] = $event;
				continue;
			}
			$day = gmdate('Y-m-d', $event['at']);
			$key = $event['universe'] . ':' . $event['actor'] . ':' . $day;
			$days[$key] ??= ['universe' => $event['universe'], 'actor' => $event['actor'], 'day' => $day, 'windows' => [], 'actions' => [], 'requests' => [], 'hours' => []];
			if ($event['interactive']) {
				$days[$key]['windows'][] = [$event['at'], $event['at']];
			}
			// Per hour: keep-alive loads, planet switches and alliance views among them, and everything else.
			// The shortest and longest wait between requests are added when the day is saved.
			$hour = (int) gmdate('G', $event['at']);
			$counts = $days[$key]['hours'][$hour] ?? [0, 0, 0, 0];
			$counts[in_array($event['kind'], self::KEEP_ALIVE_KINDS, true) ? 0 : 2]++;
			$counts[1] += (int) ($event['kind'] === 'planet.switch');
			$counts[3] += (int) ($event['kind'] === 'alliance.view');
			$days[$key]['hours'][$hour] = $counts;
			$days[$key]['actions'][$event['kind']] = ($days[$key]['actions'][$event['kind']] ?? 0) + 1;
			$days[$key]['requests'][$event['request_id']] = min($days[$key]['requests'][$event['request_id']] ?? $event['at'], $event['at']);
			if (isset($event['ip']) || isset($event['client'])) {
				$address = $key . ':' . ($event['ip'] ?? '') . ':' . ($event['client'] ?? '');
				$network[$address] ??= [
					'values' => [$event['universe'], $event['actor'], $day, $event['ip'] ?? '', $event['client'] ?? ''],
					'requests' => [],
					'first' => $event['at'],
					'last' => $event['at'],
				];
				$network[$address]['requests'][$event['request_id']] = true;
				$network[$address]['first'] = min($network[$address]['first'], $event['at']);
				$network[$address]['last'] = max($network[$address]['last'], $event['at']);
			}
		}
		// Lock rows always in the same order when one request touches several accounts.
		ksort($days);
		ksort($network);
		$this->db->beginTransaction();
		try {
			foreach ($days as $day) {
				$this->addDay($day);
			}
			foreach ($rows as $row) {
				if ($row['kind'] === 'delivery' && $row['target'] > 0) {
					$this->addPair($row);
				}
			}
			if (empty($health['suspended'])) {
				$this->insertEvents($rows);
				foreach ($network as $address) {
					$this->addNetwork($address);
				}
			}
			$this->db->commit();
			return true;
		} catch (Throwable $e) {
			if ($this->db->inTransaction()) {
				$this->db->rollBack();
			}
			throw $e;
		}
	}

	/** Resources of one delivery, with the build cost of the ships left there. */
	public static function delivered(array $data): array
	{
		$resources = [];
		foreach (['metal', 'crystal', 'deuterium'] as $resource) {
			$resources[$resource] = max(0, (float) ($data[$resource] ?? 0)) + (float) ($data['ship_value'][$resource] ?? 0);
		}
		return $resources;
	}

	/** Activity time, action counts, hourly counts and waits, and gaps between requests of one account and day. */
	private function addDay(array $day): void
	{
		$key = [$day['universe'], $day['actor'], $day['day']];
		$this->query(
			"INSERT INTO %%TELEMETRY_DAILY%% (universe,actor,day,windows,actions,gaps,hours) VALUES (?,?,?,'[]','{}','{}','{}')
			ON DUPLICATE KEY UPDATE actor=VALUES(actor)",
			$key
		);
		$saved = $this->query(
			'SELECT windows,actions,gaps,hours,last_at FROM %%TELEMETRY_DAILY%% WHERE universe=? AND actor=? AND day=? FOR UPDATE',
			$key
		)->fetch(PDO::FETCH_ASSOC);
		$windows = TelemetryActivity::merge(array_merge(json_decode($saved['windows'], true, 512, JSON_THROW_ON_ERROR), $day['windows']));
		$actions = json_decode($saved['actions'], true, 512, JSON_THROW_ON_ERROR);
		foreach ($day['actions'] as $kind => $count) {
			$actions[$kind] = ($actions[$kind] ?? 0) + $count;
		}
		[$gaps, $last, $waits] = TelemetryActivity::addGaps(
			json_decode($saved['gaps'], true, 512, JSON_THROW_ON_ERROR),
			array_values($day['requests']),
			(int) $saved['last_at']
		);
		$hours = json_decode($saved['hours'], true, 512, JSON_THROW_ON_ERROR);
		foreach ($day['hours'] as $hour => $counts) {
			[$loads, $switches, $busy, $alliance, $shortest, $longest] = $hours[$hour] ?? [0, 0, 0, 0, 0, 0];
			[$newShortest, $newLongest] = $waits[$hour] ?? [0, 0];
			$hours[$hour] = [
				$loads + $counts[0],
				$switches + $counts[1],
				$busy + $counts[2],
				$alliance + $counts[3],
				TelemetryActivity::shortest($shortest, $newShortest),
				max($longest, $newLongest),
			];
		}
		$this->query(
			'UPDATE %%TELEMETRY_DAILY%% SET windows=?,actions=?,gaps=?,hours=?,last_at=? WHERE universe=? AND actor=? AND day=?',
			array_merge([
				json_encode($windows, JSON_THROW_ON_ERROR),
				json_encode($actions, JSON_THROW_ON_ERROR),
				json_encode($gaps, JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR),
				json_encode($hours, JSON_THROW_ON_ERROR),
				$last,
			], $key)
		);
	}

	private function insertEvents(array $events): void
	{
		if (!$events) {
			return;
		}
		$values = [];
		foreach ($events as $event) {
			array_push(
				$values,
				$event['universe'],
				$event['actor'],
				$event['target'],
				min($event['actor'], $event['target']),
				max($event['actor'], $event['target']),
				$event['at'],
				$event['kind'],
				json_encode($event['data'], JSON_THROW_ON_ERROR)
			);
		}
		$placeholders = '(' . implode(',', array_fill(0, count(self::EVENT_COLUMNS), '?')) . ')';
		$sql = 'INSERT INTO %%TELEMETRY_EVENTS%% (' . implode(',', self::EVENT_COLUMNS) . ')
			VALUES ' . implode(',', array_fill(0, count($events), $placeholders));
		$this->query($sql, $values)->closeCursor();
	}

	private function addNetwork(array $address): void
	{
		$this->query(
			'INSERT INTO %%TELEMETRY_NETWORK%% (universe,actor,day,ip,client,requests,first_at,last_at)
			VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE requests=requests+VALUES(requests),
			first_at=LEAST(first_at,VALUES(first_at)),last_at=GREATEST(last_at,VALUES(last_at))',
			array_merge($address['values'], [count($address['requests']), $address['first'], $address['last']])
		);
	}

	/** All-time totals per pair of accounts, kept after the deliveries themselves are removed. */
	private function addPair(array $delivery): void
	{
		$side = $delivery['actor'] < $delivery['target'] ? 'a' : 'b';
		$resources = self::delivered($delivery['data']);
		$this->query(
			"INSERT INTO %%TELEMETRY_PAIRS%%
			(universe,pair_a,pair_b,{$side}_metal,{$side}_crystal,{$side}_deuterium,{$side}_deliveries,first_at,last_at)
			VALUES (?,?,?,?,?,?,1,?,?) ON DUPLICATE KEY UPDATE
			{$side}_metal={$side}_metal+VALUES({$side}_metal),{$side}_crystal={$side}_crystal+VALUES({$side}_crystal),
			{$side}_deuterium={$side}_deuterium+VALUES({$side}_deuterium),{$side}_deliveries={$side}_deliveries+1,
			first_at=LEAST(first_at,VALUES(first_at)),last_at=GREATEST(last_at,VALUES(last_at))",
			[
				$delivery['universe'],
				min($delivery['actor'], $delivery['target']),
				max($delivery['actor'], $delivery['target']),
				$resources['metal'],
				$resources['crystal'],
				$resources['deuterium'],
				$delivery['at'],
				$delivery['at'],
			]
		);
	}

	/** All-time exchange seen from the sender: what they sent and what came back. */
	public function pairTotals(int $universe, int $sender, int $recipient): ?array
	{
		$row = $this->query(
			'SELECT * FROM %%TELEMETRY_PAIRS%% WHERE universe=? AND pair_a=? AND pair_b=?',
			[$universe, min($sender, $recipient), max($sender, $recipient)]
		)->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			return null;
		}
		[$out, $in] = $sender < $recipient ? ['a', 'b'] : ['b', 'a'];
		$totals = [];
		foreach (['sent' => $out, 'returned' => $in] as $name => $side) {
			foreach (['metal', 'crystal', 'deuterium'] as $resource) {
				$totals[$name][$resource] = (float) $row[$side . '_' . $resource];
			}
		}
		$totals['deliveries'] = (int) $row[$out . '_deliveries'];
		$totals['returned_deliveries'] = (int) $row[$in . '_deliveries'];
		$totals['since'] = (int) $row['first_at'];
		return $totals;
	}

	public function events(int $universe, int $actor, int $since, int $limit, ?int $other = null, bool $recent = false): array
	{
		if ($other === null) {
			$where = 'actor=?';
			$values = [$universe, $actor, $since];
		} else {
			$where = "pair_a=? AND pair_b=? AND kind IN ('delivery','combat')";
			$values = [$universe, min($actor, $other), max($actor, $other), $since];
		}
		$order = $recent ? 'at DESC,id DESC' : 'at,id';
		$rows = $this->query(
			"SELECT * FROM %%TELEMETRY_EVENTS%% WHERE universe=? AND {$where} AND at>=?
			ORDER BY {$order} LIMIT " . ($limit + 1),
			$values
		)->fetchAll(PDO::FETCH_ASSOC);
		foreach ($rows as &$row) {
			$row['data'] = json_decode($row['data'], true);
		}
		return $rows;
	}

	public function daily(int $universe, int $actor, int $since): array
	{
		return $this->query(
			'SELECT * FROM %%TELEMETRY_DAILY%% WHERE universe=? AND actor=? AND day>=? ORDER BY day',
			[$universe, $actor, gmdate('Y-m-d', $since - 300)]
		)->fetchAll(PDO::FETCH_ASSOC);
	}

	public function actionCounts(int $universe, int $actor, int $from, int $to): array
	{
		$counts = [];
		$rows = $this->query(
			'SELECT actions FROM %%TELEMETRY_DAILY%% WHERE universe=? AND actor=? AND day>=? AND day<=?',
			[$universe, $actor, gmdate('Y-m-d', $from), gmdate('Y-m-d', $to)]
		)->fetchAll(PDO::FETCH_COLUMN);
		foreach ($rows as $actions) {
			foreach (json_decode($actions, true, 512, JSON_THROW_ON_ERROR) as $kind => $count) {
				$counts[$kind] = ($counts[$kind] ?? 0) + $count;
			}
		}
		return $counts;
	}

	public function network(int $universe, int $actor, int $from, int $to): array
	{
		$where = 'universe=? AND actor=? AND day>=? AND day<=?';
		$values = [$universe, $actor, gmdate('Y-m-d', $from), gmdate('Y-m-d', $to)];
		$network = $this->query(
			"SELECT COUNT(DISTINCT NULLIF(ip,'')) AS ips,COUNT(DISTINCT NULLIF(client,'')) AS clients
			FROM %%TELEMETRY_NETWORK%% WHERE {$where}",
			$values
		)->fetch(PDO::FETCH_ASSOC);
		$rows = $this->query(
			"SELECT NULLIF(ip,'') AS ip,NULLIF(client,'') AS client,SUM(requests) AS requests,
			MIN(first_at) AS first_seen,MAX(last_at) AS last_seen
			FROM %%TELEMETRY_NETWORK%% WHERE {$where}
			GROUP BY ip,client ORDER BY last_seen DESC LIMIT " . (self::PAGE_SIZE + 1),
			$values
		)->fetchAll(PDO::FETCH_ASSOC);
		$network['more'] = count($rows) > self::PAGE_SIZE;
		$network['rows'] = array_slice($rows, 0, self::PAGE_SIZE);
		return $network;
	}

	/** One page of warnings involving an account, newest first; one extra row tells if more exist. */
	public function accountWarnings(int $universe, int $actor, int $beforeId): array
	{
		return $this->query(
			'SELECT id,actor,other,kind,strength,observation_start,observation_end,status
			FROM %%TELEMETRY_WARNINGS%%
			WHERE universe=? AND (actor=? OR other=?) AND id<?
			ORDER BY id DESC LIMIT ' . (self::PAGE_SIZE + 1),
			[$universe, $actor, $actor, $beforeId]
		)->fetchAll(PDO::FETCH_ASSOC);
	}

	/** Accounts with warnings in this status, as sender or as receiver. */
	public function warnedAccounts(int $universe, string $status, int $beforeAccount): array
	{
		return $this->query(
			'SELECT account,COUNT(*) AS total,GROUP_CONCAT(DISTINCT kind ORDER BY kind) AS kinds,MAX(observation_end) AS last_seen
			FROM (
				SELECT actor AS account,kind,observation_end FROM %%TELEMETRY_WARNINGS%%
				WHERE universe=? AND status=?
				UNION ALL
				SELECT other AS account,kind,observation_end FROM %%TELEMETRY_WARNINGS%%
				WHERE universe=? AND status=? AND other>0 AND other<>actor
			) AS warnings
			WHERE account<? GROUP BY account ORDER BY account DESC LIMIT ' . (self::PAGE_SIZE + 1),
			[$universe, $status, $universe, $status, $beforeAccount]
		)->fetchAll(PDO::FETCH_ASSOC);
	}

	public function settingsHistory(int $universe): array
	{
		return $this->query(
			"SELECT admin,at,data FROM %%TELEMETRY_AUDIT%%
			WHERE universe=? AND action='settings' ORDER BY at DESC LIMIT 20",
			[$universe]
		)->fetchAll(PDO::FETCH_ASSOC);
	}

	public function warning(int $universe, int $actor, int $other, array $finding, array $settings, int $now): void
	{
		$this->query(
			'INSERT INTO %%TELEMETRY_WARNINGS%%
			(universe,actor,other,kind,strength,first_seen,last_seen,observation_start,observation_end,settings,evidence)
			VALUES (?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
			last_seen=VALUES(last_seen),strength=VALUES(strength),
			latest_evidence=VALUES(evidence),latest_settings=VALUES(settings),observation_end=VALUES(observation_end)',
			[
				$universe,
				$actor,
				$other,
				$finding['kind'],
				$finding['strength'],
				$now,
				$now,
				$finding['from'],
				$finding['to'],
				json_encode($settings, JSON_THROW_ON_ERROR),
				json_encode($finding, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
			]
		);
	}

	public function maintenance(int $deliveryDays, int $networkHours, int $now): array
	{
		$megabytes = $this->allocatedMegabytes();
		$closed = $now - TelemetrySettings::CLOSED_DAYS * 86400;
		$cleanups = [
			['%%TELEMETRY_EVENTS%%', 'at', $now - $deliveryDays * 86400, '1', []],
			['%%TELEMETRY_NETWORK%%', 'last_at', $now - $networkHours * 3600, '1', []],
			['%%TELEMETRY_DAILY%%', 'day', gmdate('Y-m-d', $now - TelemetrySettings::DAILY_DAYS * 86400), '1', []],
			// A recent moderator decision keeps its case, even when the evidence is older.
			['%%TELEMETRY_WARNINGS%%', 'last_seen', $closed, "status='dismissed' AND id NOT IN
				(SELECT warning_id FROM %%TELEMETRY_AUDIT%% WHERE at>=? AND warning_id IS NOT NULL)", [$closed]],
			['%%TELEMETRY_AUDIT%%', 'at', $now - 365 * 86400, 'warning_id IS NULL', []],
		];
		foreach ($cleanups as [$table, $column, $boundary, $condition, $values]) {
			$this->query(
				"DELETE FROM {$table} WHERE {$column}<? AND {$condition} ORDER BY {$column} LIMIT " . TelemetrySettings::CLEANUP_ROWS,
				array_merge([$boundary], $values)
			);
		}
		return self::health([
			'maintenance_at' => $now,
			'allocated_mb' => $megabytes,
			'suspended' => $megabytes >= TelemetrySettings::BUDGET_MB - TelemetrySettings::RESERVE_MB,
		]);
	}

	/**
	 * Whole database size, including free pages: DELETE alone does not give disk space back.
	 * When all tables share one file, each of them reports its free space, so it is added once.
	 * InnoDB updates these numbers by itself; ANALYZE TABLE would make player requests wait.
	 */
	private function allocatedMegabytes(): float
	{
		// MySQL 8 caches table sizes for a day by default.
		$version = $this->db->getAttribute(PDO::ATTR_SERVER_VERSION);
		if (!str_contains($version, 'MariaDB') && version_compare($version, '8.0', '>=')) {
			$this->db->exec('SET SESSION information_schema_stats_expiry=0');
		}
		$bytes = $this->query(
			'SELECT COALESCE(SUM(DATA_LENGTH+INDEX_LENGTH)+IF(@@innodb_file_per_table,SUM(DATA_FREE),MAX(DATA_FREE)),0)
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA=DATABASE()'
		)->fetchColumn();
		return (float) $bytes / 1048576;
	}
}
