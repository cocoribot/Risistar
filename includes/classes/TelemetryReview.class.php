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
            foreach ($results as $finding) {
                $this->saveFinding($universe, $cursor, $finding['other'], $finding, $s, $now, $finding['truncated']);
                ++$findings;
            }
        }
        // Only completed deliveries identify pairs for exchange analysis.
        $pairs = $this->store->query(
            "SELECT DISTINCT pair_a,pair_b FROM telemetry_events
            WHERE universe=? AND kind='delivery' AND pair_a>0 AND (pair_a>? OR (pair_a=? AND pair_b>?))
            AND at>=? ORDER BY pair_a,pair_b LIMIT " . ($batch + 1),
            [$universe, $pairA, $pairA, $pairB, $now - $s['push_days'] * 86400]
        )->fetchAll(PDO::FETCH_ASSOC);
        $incomplete = $incomplete || count($pairs) > $batch;
        foreach (array_slice($pairs, 0, $batch) as $pair) {
            $pairA = (int) $pair['pair_a'];
            $pairB = (int) $pair['pair_b'];
            foreach ($this->pairFindings($universe, $pairA, $pairB, $s, $now, $truncated) as $finding) {
                $this->saveFinding($universe, $pairA, $pairB, $finding, $s, $now, $finding['truncated']);
                ++$findings;
            }
        }
        $result = [
            'at' => $now,
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
        $events = $this->store->events($universe, $a, $now - $s['push_days'] * 86400, $limit, $b);
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

    public function refresh(int $universe, int $id, array $s, int $now): array
    {
        $case = $this->evidence($universe, $id);
        $truncated = [];
        $findings = str_starts_with($case['kind'], 'pushing.') ? $this->pairFindings($universe, (int) $case['actor'], (int) $case['other'], $s, $now, $truncated) : $this->accountFindings($universe, (int) $case['actor'], $s, $now, $truncated);
        foreach ($findings as $finding) {
            if ($finding['kind'] === $case['kind'] && ($finding['other'] ?? (int) $case['other']) === (int) $case['other']) {
                $this->saveFinding($universe, (int) $case['actor'], (int) $case['other'], $finding, $s, $now, $finding['truncated']);
                return $this->evidence($universe, $id);
            }
        }
        // Keep the opening evidence and the moderator's decision when a check no longer matches.
        $latest = [
            'matches' => false,
            'evaluated_at' => $now,
            'metrics' => [],
            'timeline' => [],
            'coverage' => ['truncated' => $truncated],
            'supporting_checks' => [],
        ];
        $this->store->query(
            'UPDATE telemetry_warnings SET latest_evidence=?,latest_settings=? WHERE universe=? AND id=?',
            [json_encode($latest, JSON_THROW_ON_ERROR), json_encode($s, JSON_THROW_ON_ERROR), $universe, $id]
        );
        return $this->evidence($universe, $id);
    }

    private function saveFinding(int $universe, int $actor, int $other, array $finding, array $s, int $now, array $truncated): void
    {
        $finding['evaluated_at'] = $now;
        $finding['matches'] = true;
        $lookback = match (true) {
            $finding['kind'] === 'availability' => ($s['activity_days'] + 1) * 86400,
            str_starts_with($finding['kind'], 'pushing.') => $s['push_days'] * 86400,
            default => $s['automation_days'] * 86400,
        };
        $gaps = TelemetryStore::interruptions($universe, $now - $lookback, $now, $finding['kind'] !== 'availability');
        $finding['coverage'] = ['gaps' => $gaps, 'truncated' => $truncated];
        $finding['timeline_count'] = count($finding['timeline']);
        $hasGap = (bool) $gaps || (bool) $truncated;
        if ($hasGap && $finding['strength'] === 'moderate' && $finding['kind'] !== 'availability') {
            $finding['strength'] = 'weak';
            $finding['explanation'] .= ' La collecte comporte des lacunes : preuve partielle.';
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
        if (!in_array($status, ['open', 'follow_up', 'dismissed'], true) || mb_strlen($note) > 4000) {
            throw new InvalidArgumentException('Décision ou note invalide (4000 caractères maximum).');
        }
        $db = $this->store->db;
        $db->beginTransaction();
        try {
            $warning = $this->store->query(
                'SELECT status FROM telemetry_warnings WHERE universe=? AND id=? FOR UPDATE',
                [$universe, $id]
            )->fetch(PDO::FETCH_ASSOC);
            if (!$warning) {
                throw new InvalidArgumentException('Dossier introuvable.');
            }
            $this->store->query('UPDATE telemetry_warnings SET status=? WHERE universe=? AND id=?', [$status, $universe, $id]);
            $this->store->query(
                'INSERT INTO telemetry_audit (universe,admin,at,warning_id,action,data) VALUES (?,?,?,?,?,?)',
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
        // Prepare an audit before changing game Config. The completion marker distinguishes failed saves.
        $this->store->query(
            'INSERT INTO telemetry_audit (universe,admin,at,action,data) VALUES (?,?,?,?,?)',
            [$universe, $admin, $now, 'settings_pending', json_encode($changed, JSON_THROW_ON_ERROR)]
        );
        $id = $this->store->db->lastInsertId();
        try {
            $config->telemetry_settings = json_encode($values, JSON_THROW_ON_ERROR);
            $config->save();
        } catch (Throwable $e) {
            Config::reload();
            throw $e;
        }
        $this->store->query("UPDATE telemetry_audit SET action='settings' WHERE universe=? AND id=?", [$universe, $id]);
        if (isset($changed['events_enabled'])) {
            TelemetryStore::switchChanged($universe, 'events_disabled', (bool)$values['events_enabled'], $now);
        }
    }

    public function evidence(int $universe, int $id): array
    {
        $row = $this->store->query('SELECT * FROM telemetry_warnings WHERE universe=? AND id=?', [$universe, $id])->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new InvalidArgumentException('Dossier introuvable.');
        }
        foreach (['settings', 'evidence', 'latest_settings', 'latest_evidence'] as $key) {
            $row[$key] = $row[$key] === null ? null : json_decode($row[$key], true);
        }
        $row['review_history'] = $this->store->query(
            'SELECT admin,at,action,data FROM telemetry_audit
            WHERE universe=? AND warning_id=? ORDER BY at,id LIMIT 1000',
            [$universe, $id]
        )->fetchAll(PDO::FETCH_ASSOC);
        return $row;
    }
}
