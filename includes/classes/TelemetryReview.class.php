<?php

final class TelemetryReview
{
	private const TIMELINE_SAMPLE = 100;
	private const TIMELINE_KEYS = ['at', 'kind', 'actor', 'target', 'data'];

	public int $batch = TelemetrySettings::ANALYSIS_ACCOUNTS;

	public function __construct(public TelemetryStore $store)
	{
	}

	/** Checks the next batch of accounts and pairs after the cursors, and saves where it stopped. */
	public function evaluate(int $universe, array $settings, int $now, int $cursor = 0, int $pairA = 0, int $pairB = 0): array
	{
		$users = Database::get()->select(
			'SELECT id FROM %%USERS%% WHERE universe=:universe AND id>:cursor ORDER BY id LIMIT ' . ($this->batch + 1),
			[':universe' => $universe, ':cursor' => $cursor]
		);
		foreach (array_slice($users, 0, $this->batch) as $user) {
			$cursor = (int) $user['id'];
			[$findings, $partial] = $this->accountFindings($universe, $cursor, $settings, $now);
			$this->save($universe, $cursor, 0, $findings, $partial, $settings, $now);
		}
		$pairs = $this->pairsAfter($universe, $pairA, $pairB, $settings, $now);
		foreach (array_slice($pairs, 0, $this->batch) as $pair) {
			$pairA = (int) $pair['pair_a'];
			$pairB = (int) $pair['pair_b'];
			[$findings, $partial] = $this->pairFindings($universe, $pairA, $pairB, $settings, $now);
			$this->save($universe, $pairA, $pairB, $findings, $partial, $settings, $now);
		}
		$incomplete = count($users) > $this->batch || count($pairs) > $this->batch;
		$previous = TelemetryStore::health()['analysis_' . $universe] ?? [];
		$result = [
			'at' => $now,
			'completed_at' => $incomplete ? ($previous['completed_at'] ?? null) : $now,
			'cursor' => $cursor,
			'pair_a' => $pairA,
			'pair_b' => $pairB,
			'incomplete' => $incomplete,
		];
		TelemetryStore::health(['analysis_' . $universe => $result]);
		return $result;
	}

	/** Pairs with a recent delivery, plus pairs that already have a warning to re-check. */
	private function pairsAfter(int $universe, int $pairA, int $pairB, array $settings, int $now): array
	{
		return $this->store->query(
			"SELECT pair_a,pair_b FROM (
				SELECT DISTINCT pair_a,pair_b FROM %%TELEMETRY_EVENTS%%
				WHERE universe=? AND kind='delivery' AND pair_a>0 AND at>=?
				UNION
				SELECT actor,other FROM %%TELEMETRY_WARNINGS%% WHERE universe=? AND other>0
			) AS pairs
			WHERE pair_a>? OR (pair_a=? AND pair_b>?)
			ORDER BY pair_a,pair_b LIMIT " . ($this->batch + 1),
			[$universe, $now - $settings['push_days'] * 86400, $universe, $pairA, $pairA, $pairB]
		)->fetchAll(PDO::FETCH_ASSOC);
	}

	private function accountFindings(int $universe, int $actor, array $settings, int $now): array
	{
		$days = max($settings['activity_days'], $settings['automation_days']) + 1;
		$daily = $this->store->daily($universe, $actor, $now - $days * 86400);
		$findings = array_merge(
			TelemetryDetectors::availability($daily, $settings, $now),
			TelemetryDetectors::automation($daily, $settings, $now),
			TelemetryDetectors::refreshing($daily, $settings, $now)
		);
		return [$findings, false];
	}

	private function pairFindings(int $universe, int $accountA, int $accountB, array $settings, int $now): array
	{
		if ($accountA === $accountB) {
			return [[], false];
		}
		$limit = TelemetrySettings::ANALYSIS_EVENTS;
		$since = $now - TelemetrySettings::deliveryDays($settings) * 86400;
		$events = $this->store->events($universe, $accountA, $since, $limit, $accountB);
		$partial = count($events) > $limit;
		$points = Database::get()->select(
			'SELECT id_owner,total_points FROM %%STATPOINTS%%
			WHERE universe=:universe AND stat_type=1 AND id_owner IN (:a,:b)',
			[':universe' => $universe, ':a' => $accountA, ':b' => $accountB]
		);
		$points = array_column($points, 'total_points', 'id_owner');
		$findings = TelemetryDetectors::pushing(array_slice($events, 0, $limit), $accountA, $accountB, $points, $settings, $now);
		foreach ($findings as &$finding) {
			$finding['truncated'] = $partial;
			$finding['metrics']['lifetime'] = $this->store->pairTotals(
				$universe,
				$finding['metrics']['sender'],
				$finding['metrics']['recipient']
			);
		}
		return [$findings, $partial];
	}

	private function save(int $universe, int $actor, int $other, array $findings, bool $partial, array $settings, int $now): void
	{
		// With only part of the events, we cannot say that an older warning no longer matches.
		if (!$partial) {
			$this->markUnmatched($universe, $actor, $other, array_column($findings, 'kind'), $settings, $now);
		}
		foreach ($findings as $finding) {
			$this->saveFinding($universe, $actor, $other, $finding, $settings, $now);
		}
	}

	private function markUnmatched(int $universe, int $actor, int $other, array $kinds, array $settings, int $now): void
	{
		$evidence = ['matches' => false, 'evaluated_at' => $now, 'metrics' => [], 'timeline' => []];
		$sql = 'UPDATE %%TELEMETRY_WARNINGS%% SET latest_evidence=?,latest_settings=?
			WHERE universe=? AND actor=? AND other=?';
		if ($kinds) {
			$sql .= ' AND kind NOT IN (' . implode(',', array_fill(0, count($kinds), '?')) . ')';
		}
		$values = [json_encode($evidence, JSON_THROW_ON_ERROR), json_encode($settings, JSON_THROW_ON_ERROR), $universe, $actor, $other];
		$this->store->query($sql, array_merge($values, $kinds));
	}

	private function saveFinding(int $universe, int $actor, int $other, array $finding, array $settings, int $now): void
	{
		$finding['evaluated_at'] = $now;
		$finding['matches'] = true;
		$finding['truncated'] ??= false;
		$finding['timeline_count'] = count($finding['timeline']);
		$finding['timeline'] = array_map(
			static fn($event) => array_intersect_key($event, array_flip(self::TIMELINE_KEYS)),
			$finding['timeline']
		);
		if ($finding['truncated'] && $finding['strength'] === 'moderate') {
			$finding['strength'] = 'weak';
		}
		// Keep only the first and last events, so a flood of deliveries cannot make one warning grow without limit.
		if (count($finding['timeline']) > self::TIMELINE_SAMPLE) {
			$half = self::TIMELINE_SAMPLE / 2;
			$finding['timeline'] = array_merge(array_slice($finding['timeline'], 0, $half), array_slice($finding['timeline'], -$half));
			$finding['timeline_sampled'] = true;
		}
		$this->reopen($universe, $actor, $other, $finding['kind'], $now);
		$this->store->warning($universe, $actor, $other, $finding, $settings, $now);
	}

	/** A dismissed case that stopped matching and matches again is a new story: show it again. */
	private function reopen(int $universe, int $actor, int $other, string $kind, int $now): void
	{
		$case = $this->store->query(
			'SELECT id,status,latest_evidence FROM %%TELEMETRY_WARNINGS%% WHERE universe=? AND actor=? AND other=? AND kind=?',
			[$universe, $actor, $other, $kind]
		)->fetch(PDO::FETCH_ASSOC);
		if (!$case || $case['status'] !== 'dismissed' || !$case['latest_evidence']) {
			return;
		}
		if (json_decode($case['latest_evidence'], true)['matches'] ?? true) {
			return;
		}
		$this->store->query('UPDATE %%TELEMETRY_WARNINGS%% SET status=? WHERE id=?', ['open', $case['id']]);
		$this->store->query(
			'INSERT INTO %%TELEMETRY_AUDIT%% (universe,admin,at,warning_id,action,data) VALUES (?,?,?,?,?,?)',
			[$universe, 0, $now, $case['id'], 'review', json_encode(['before' => 'dismissed', 'after' => 'open', 'note' => '', 'reopened' => true])]
		);
	}

	public function decide(int $universe, int $admin, int $id, string $status, string $note, int $now): void
	{
		global $LNG;
		if (!in_array($status, ['open', 'follow_up', 'dismissed'], true) || mb_strlen($note) > 4000) {
			throw new InvalidArgumentException($LNG['telemetry_invalid_decision']);
		}
		$db = $this->store->db;
		$db->beginTransaction();
		try {
			$warning = $this->store->query(
				'SELECT status FROM %%TELEMETRY_WARNINGS%% WHERE universe=? AND id=? FOR UPDATE',
				[$universe, $id]
			)->fetch(PDO::FETCH_ASSOC);
			if (!$warning) {
				throw new InvalidArgumentException($LNG['telemetry_missing_case']);
			}
			$this->store->query('UPDATE %%TELEMETRY_WARNINGS%% SET status=? WHERE universe=? AND id=?', [$status, $universe, $id]);
			$change = ['before' => $warning['status'], 'after' => $status, 'note' => $note];
			$this->store->query(
				'INSERT INTO %%TELEMETRY_AUDIT%% (universe,admin,at,warning_id,action,data) VALUES (?,?,?,?,?,?)',
				[$universe, $admin, $now, $id, 'review', json_encode($change, JSON_THROW_ON_ERROR)]
			);
			$db->commit();
		} catch (Throwable $e) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			throw $e;
		}
	}

	public function saveSettings(int $universe, int $admin, array $input, int $now): void
	{
		$values = TelemetrySettings::validate($input);
		$previous = TelemetrySettings::get($universe);
		$changed = [];
		foreach ($values as $key => $value) {
			if ($previous[$key] != $value) {
				$changed[$key] = ['before' => $previous[$key], 'after' => $value];
			}
		}
		if (!$changed) {
			return;
		}
		// The change is only saved when its audit entry can be saved too.
		$db = $this->store->db;
		$db->beginTransaction();
		try {
			$this->store->query(
				'INSERT INTO %%TELEMETRY_AUDIT%% (universe,admin,at,action,data) VALUES (?,?,?,?,?)',
				[$universe, $admin, $now, 'settings', json_encode($changed, JSON_THROW_ON_ERROR)]
			);
			$config = Config::get($universe);
			$config->telemetry_settings = json_encode($values, JSON_THROW_ON_ERROR);
			$config->save();
			$db->commit();
		} catch (Throwable $e) {
			if ($db->inTransaction()) {
				$db->rollBack();
			}
			throw $e;
		}
	}

	public function evidence(int $universe, int $id): array
	{
		global $LNG;
		$row = $this->store->query(
			'SELECT * FROM %%TELEMETRY_WARNINGS%% WHERE universe=? AND id=?',
			[$universe, $id]
		)->fetch(PDO::FETCH_ASSOC);
		if (!$row) {
			throw new InvalidArgumentException($LNG['telemetry_missing_case']);
		}
		foreach (['settings', 'evidence', 'latest_settings', 'latest_evidence'] as $key) {
			$row[$key] = $row[$key] === null ? null : json_decode($row[$key], true);
		}
		$row['review_history'] = $this->store->query(
			'SELECT admin,at,action,data FROM %%TELEMETRY_AUDIT%%
			WHERE universe=? AND warning_id=? ORDER BY at,id LIMIT 1000',
			[$universe, $id]
		)->fetchAll(PDO::FETCH_ASSOC);
		return $row;
	}
}
