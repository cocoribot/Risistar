<?php

final class TelemetryReview
{
	public function __construct(public TelemetryStore $store)
	{
	}

	public function evaluate(int $universe, array $s, int $now, int $cursor = 0, int $pairA = 0, int $pairB = 0): array
	{
		$started = microtime(true);
		$queries = $this->store->queries;
		$batch = (int) $s['analysis_accounts'];
		$users = Database::get()->select(
			'SELECT id FROM %%USERS%% WHERE universe=:universe AND id>:cursor ORDER BY id LIMIT ' . ($batch + 1),
			[':universe' => $universe, ':cursor' => $cursor]
		);
		$incomplete = count($users) > $batch;
		$truncated = [];
		$findings = 0;
		foreach (array_slice($users, 0, $batch) as $user) {
			$cursor = (int) $user['id'];
			$results = $this->accountFindings($universe, $cursor, $s, $now, $truncated);
			if (!in_array('account:' . $cursor, $truncated, true)) {
				$this->updateMatches($universe, $cursor, 0, $results, $s, $now);
			}
			foreach ($results as $finding) {
				$this->saveFinding($universe, $cursor, $finding['other'], $finding, $s, $now, $finding['truncated']);
				++$findings;
			}
		}
		// Only completed deliveries identify pairs for exchange analysis.
		$pairs = $this->store->query(
			"SELECT pair_a,pair_b FROM (
				SELECT DISTINCT pair_a,pair_b FROM %%TELEMETRY_EVENTS%% WHERE universe=? AND kind='delivery' AND pair_a>0 AND at>=?
				UNION SELECT actor,other FROM %%TELEMETRY_WARNINGS%% WHERE universe=? AND other>0
			) AS pairs WHERE pair_a>? OR (pair_a=? AND pair_b>?) ORDER BY pair_a,pair_b LIMIT " . ($batch + 1),
			[$universe, $now - $s['push_days'] * 86400, $universe, $pairA, $pairA, $pairB]
		)->fetchAll(PDO::FETCH_ASSOC);
		$incomplete = $incomplete || count($pairs) > $batch;
		foreach (array_slice($pairs, 0, $batch) as $pair) {
			$pairA = (int) $pair['pair_a'];
			$pairB = (int) $pair['pair_b'];
			$results = $this->pairFindings($universe, $pairA, $pairB, $s, $now, $truncated);
			if (!in_array('pair:' . $pairA . ':' . $pairB, $truncated, true)) {
				$this->updateMatches($universe, $pairA, $pairB, $results, $s, $now);
			}
			foreach ($results as $finding) {
				$this->saveFinding($universe, $pairA, $pairB, $finding, $s, $now, $finding['truncated']);
				++$findings;
			}
		}
		$previous = TelemetryStore::health()['analysis_' . $universe] ?? [];
		$result = [
			'at' => $now,
			'completed_at' => $incomplete ? ($previous['completed_at'] ?? null) : $now,
			'cursor' => $cursor,
			'pair_a' => $pairA,
			'pair_b' => $pairB,
			'incomplete' => $incomplete,
			'truncated' => $truncated,
			'findings' => $findings,
			'queries' => $this->store->queries - $queries,
			'seconds' => microtime(true) - $started,
		];
		TelemetryStore::health(['analysis_' . $universe => $result]);
		return $result;
	}

	private function updateMatches(int $universe, int $actor, int $other, array $findings, array $settings, int $now): void
	{
		$kinds = array_column($findings, 'kind');
		$where = $kinds ? ' AND kind NOT IN (' . implode(',', array_fill(0, count($kinds), '?')) . ')' : '';
		$since = $now - ($other ? TelemetrySettings::deliveryDays($settings) : $settings['automation_days']) * 86400;
		$gaps = TelemetryStore::interruptions($universe, $since, $now, true, $other > 0);
		$evidence = ['matches' => false, 'evaluated_at' => $now, 'metrics' => [], 'timeline' => [], 'coverage' => ['gaps' => $gaps], 'supporting_checks' => []];
		$this->store->query(
			'UPDATE %%TELEMETRY_WARNINGS%% SET latest_evidence=?,latest_settings=? WHERE universe=? AND actor=? AND other=?' . $where,
			array_merge([json_encode($evidence, JSON_THROW_ON_ERROR), json_encode($settings, JSON_THROW_ON_ERROR), $universe, $actor, $other], $kinds)
		);
	}

	private function accountFindings(int $universe, int $actor, array $s, int $now, array &$truncated): array
	{
		$limit = (int) $s['analysis_events'];
		$since = $now - $s['automation_days'] * 86400;
		$events = $this->store->events($universe, $actor, $since, $limit);
		$actorTruncated = count($events) > $limit ? ['account:' . $actor] : [];
		$truncated = array_merge($truncated, $actorTruncated);
		$events = array_slice($events, 0, $limit);
		$daily = $this->store->daily($universe, $actor, $now - ($s['activity_days'] + 1) * 86400);
		$results = array_merge(TelemetryDetectors::availability($daily, $s, $now), TelemetryDetectors::automation($events, $s, $now));
		foreach ($results as &$finding) {
			$finding['other'] = 0;
			$finding['truncated'] = $finding['kind'] === 'availability' ? [] : $actorTruncated;
		}
		unset($finding);
		return $results;
	}

	private function pairFindings(int $universe, int $a, int $b, array $s, int $now, array &$truncated): array
	{
		if ($a === $b) {
			return [];
		}
		$limit = (int) $s['analysis_events'];
		$events = $this->store->events($universe, $a, $now - TelemetrySettings::deliveryDays($s) * 86400, $limit, $b);
		$pairTruncated = count($events) > $limit ? ['pair:' . $a . ':' . $b] : [];
		$truncated = array_merge($truncated, $pairTruncated);
		$points = Database::get()->select(
			'SELECT id_owner,total_points FROM %%STATPOINTS%%
			WHERE universe=:universe AND stat_type=1 AND id_owner IN (:a,:b)',
			[':universe' => $universe, ':a' => $a, ':b' => $b]
		);
		$findings = TelemetryDetectors::pushing(array_slice($events, 0, $limit), $a, $b, array_column($points, 'total_points', 'id_owner'), $s, $now);
		foreach ($findings as &$finding) {
			$finding['truncated'] = $pairTruncated;
		}
		return $findings;
	}

	private function saveFinding(int $universe, int $actor, int $other, array $finding, array $s, int $now, array $truncated): void
	{
		$finding['evaluated_at'] = $now;
		$finding['matches'] = true;
		$lookback = match (true) {
			$finding['kind'] === 'availability' => ($s['activity_days'] + 1) * 86400,
			str_starts_with($finding['kind'], 'pushing.') => TelemetrySettings::deliveryDays($s) * 86400,
			default => $s['automation_days'] * 86400,
		};
		$from = $finding['kind'] === 'availability'
			? strtotime(gmdate('Y-m-d', $now) . ' UTC') - $s['activity_days'] * 86400
			: $now - $lookback;
		$gaps = TelemetryStore::interruptions($universe, $from, $now, $finding['kind'] !== 'availability', str_starts_with($finding['kind'], 'pushing.'));
		$finding['coverage'] = ['gaps' => $gaps, 'truncated' => $truncated];
		$finding['timeline_count'] = count($finding['timeline']);
		$hasGap = (bool) $gaps || (bool) $truncated;
		if ($hasGap && $finding['strength'] === 'moderate' && $finding['kind'] !== 'availability') {
			$finding['strength'] = 'weak';
		}
		// Preserve bounded samples and complete aggregate metrics, even under hostile polling.
		if (count($finding['timeline']) > 500) {
			$finding['timeline'] = array_merge(array_slice($finding['timeline'], 0, 250), array_slice($finding['timeline'], -250));
			$finding['timeline_sampled'] = true;
		}
		$this->store->warning($universe, $actor, $other, $finding, $s, $now);
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
			$this->store->query(
				'INSERT INTO %%TELEMETRY_AUDIT%% (universe,admin,at,warning_id,action,data) VALUES (?,?,?,?,?,?)',
				[$universe, $admin, $now, $id, 'review', json_encode(['before' => $warning['status'], 'after' => $status, 'note' => $note], JSON_THROW_ON_ERROR)]
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
		$config = Config::get($universe);
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
		$config->telemetry_settings = json_encode($values, JSON_THROW_ON_ERROR);
		$config->save();
		$this->store->query(
			'INSERT INTO %%TELEMETRY_AUDIT%% (universe,admin,at,action,data) VALUES (?,?,?,?,?)',
			[$universe, $admin, $now, 'settings', json_encode($changed, JSON_THROW_ON_ERROR)]
		);
		if (isset($changed['events_enabled'])) {
			TelemetryStore::switchChanged($universe, 'events_disabled', (bool)$values['events_enabled'], $now);
		}
	}

	public function evidence(int $universe, int $id): array
	{
		global $LNG;
		$row = $this->store->query('SELECT * FROM %%TELEMETRY_WARNINGS%% WHERE universe=? AND id=?', [$universe, $id])->fetch(PDO::FETCH_ASSOC);
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
