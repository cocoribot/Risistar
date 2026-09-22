<?php

final class TelemetryStore
{
    public int $queries = 0;
    public function __construct(public PDO $db)
    {
    }

    public function query(string $sql, array $values = []): PDOStatement
    {
        ++$this->queries;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);
        return $stmt;
    }

    public static function health(array|callable $change = []): array
    {
        $directory = (defined('CACHE_PATH') ? CACHE_PATH : ROOT_PATH . 'cache/') . 'telemetry/';
        if (!is_dir($directory)) {
            @mkdir($directory, 0770, true);
        }
        $collecting = is_array($change) && (isset($change['reserve_bytes']) || isset($change['gap_start']));
        $path = $directory . 'health.json';
        $handle = @fopen($path, 'c+');
        $deadline = microtime(true) + 0.1;
        while ($handle && !flock($handle, LOCK_EX | LOCK_NB)) {
            if (microtime(true) >= $deadline) {
                fclose($handle);
                $handle = false;
                break;
            }
            usleep(2000);
        }
        if (!$handle) {
            return self::healthUnavailable($directory, $collecting);
        }
        try {
            $json = stream_get_contents($handle);
            $state = $json === '' ? ['gaps' => []] : json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $recovered = @rename($directory . 'write-failed', $directory . 'write-failed-recovered');
            if ($recovered) {
                $state['gap_start'] = min($state['gap_start'] ?? PHP_INT_MAX, filemtime($directory . 'write-failed-recovered'));
                $state['failure'] = 'health_unavailable';
            }
            if (is_callable($change)) {
                $change = $change($state);
            }
            if ($recovered && !$change) {
                $change = ['gap_start' => $state['gap_start']];
            }
            if ($change) {
                if (isset($change['reserve_bytes'])) {
                    $state['estimated_new_bytes'] = ($state['estimated_new_bytes'] ?? 0) + $change['reserve_bytes'];
                    if (($state['allocated_mb'] ?? 0) * 1048576 + $state['estimated_new_bytes'] >= $change['ceiling_bytes']) {
                        $state['suspended'] = true;
                        $state['event_gap_start'] ??= time();
                    }
                    unset($change['reserve_bytes'], $change['ceiling_bytes']);
                }
                if (isset($change['gap_start'])) {
                    $change['gap_start'] = min($state['gap_start'] ?? PHP_INT_MAX, $change['gap_start']);
                }
                if (isset($change['success']) && isset($state['gap_start'])) {
                    $state['gaps'][] = [
                        'from' => $state['gap_start'],
                        'to' => $change['success'],
                        'reason' => $state['failure'] ?? 'unknown',
                    ];
                    $state['gaps'] = array_slice($state['gaps'], -200);
                    unset($state['gap_start'], $state['failure']);
                }
                $state = array_replace($state, $change);
                rewind($handle);
                ftruncate($handle, 0);
                $json = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                if (fwrite($handle, $json) !== strlen($json) || !fflush($handle)) {
                    throw new RuntimeException('Telemetry health write failed.');
                }
            }
            if ($recovered) {
                unlink($directory . 'write-failed-recovered');
            }
            return $state;
        } catch (Throwable $e) {
            if (!empty($recovered)) {
                @rename($directory . 'write-failed-recovered', $directory . 'write-failed');
            }
            return self::healthUnavailable($directory, $collecting);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private static function healthUnavailable(string $directory, bool $collecting): array
    {
        // This marker survives contention on the health file and keeps the first failure time.
        if ($collecting) {
            $marker = @fopen($directory . 'write-failed', 'x');
            if ($marker) {
                fclose($marker);
            }
            error_log('Telemetry health unavailable; collection interrupted.');
        }
        return ['unavailable' => true, 'failure' => 'health_unavailable', 'gaps' => []];
    }

    public function write(array $events, array $settings): void
    {
        $budget = min(array_column($settings, 'budget_mb'));
        $reserve = max(array_column($settings, 'reserve_mb'));
        $health = self::health([
            'reserve_bytes' => strlen(json_encode($events, JSON_THROW_ON_ERROR)) * 3 + count($events) * 512,
            'ceiling_bytes' => ($budget - $reserve) * 1048576,
        ]);
        if (!empty($health['unavailable'])) {
            throw new RuntimeException('Telemetry storage reservation unavailable.');
        }
        $daily = [];
        $rows = [];
        $detailedRequests = [];
        foreach ($events as $event) {
            if ($event['interactive'] && $event['kind'] !== 'interaction') {
                $detailedRequests[$event['request_id']] = true;
            }
        }
        foreach ($events as $e) {
            $s = $settings[$e['universe']];
            if ($e['interactive']) {
                $day = gmdate('Y-m-d', $e['at']);
                $key = $e['universe'] . ':' . $e['actor'] . ':' . $day;
                $daily[$key] ??= ['universe' => $e['universe'], 'actor' => $e['actor'], 'day' => $day, 'windows' => []];
                $daily[$key]['windows'][] = [$e['at'], $e['at']];
            }
            // Keep one navigation row when it is the only source of client information in this request.
            $keep = $e['kind'] !== 'interaction' || $s['network_enabled'] && !isset($detailedRequests[$e['request_id']]);
            if ($keep && $s['events_enabled'] && empty($health['suspended'])) {
                $rows[] = $e;
            }
        }
        $this->db->beginTransaction();
        try {
            // Take daily row locks in a stable order when a request spans several accounts.
            ksort($daily);
            foreach ($daily as $d) {
                $key = [$d['universe'], $d['actor'], $d['day']];
                $this->query(
                    "INSERT INTO telemetry_daily (universe,actor,day,windows) VALUES (?,?,?,'[]') ON DUPLICATE KEY UPDATE actor=VALUES(actor)",
                    $key
                );
                $previous = json_decode($this->query(
                    'SELECT windows FROM telemetry_daily WHERE universe=? AND actor=? AND day=? FOR UPDATE',
                    $key
                )->fetchColumn(), true, 512, JSON_THROW_ON_ERROR);
                $windows = TelemetryActivity::merge(array_merge($previous, $d['windows']));
                $this->query(
                    'UPDATE telemetry_daily SET windows=? WHERE universe=? AND actor=? AND day=?',
                    array_merge([json_encode($windows, JSON_THROW_ON_ERROR)], $key)
                );
            }
            if ($rows) {
                $values = [];
                foreach ($rows as $e) {
                    array_push(
                        $values,
                        $e['event_key'],
                        $e['request_id'],
                        $e['universe'],
                        $e['actor'],
                        $e['target'],
                        min($e['actor'], $e['target']),
                        max($e['actor'], $e['target']),
                        $e['at'],
                        $e['kind'],
                        $e['result'],
                        $e['fleet_id'],
                        (int) $e['interactive'],
                        $e['ip'],
                        json_encode($e['data'], JSON_THROW_ON_ERROR)
                    );
                }
                $this->query(
                    'INSERT INTO telemetry_events (event_key,request_id,universe,actor,target,pair_a,pair_b,at,kind,result,fleet_id,interactive,ip,data) VALUES ' . implode(',', array_fill(0, count($rows), '(' . implode(',', array_fill(0, 14, '?')) . ')')),
                    $values
                )->closeCursor();
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function events(int $universe, int $actor, int $since, int $limit, ?int $other = null, bool $recent = false): array
    {
        $where = $other === null ? 'actor=?' : 'pair_a=? AND pair_b=?';
        $args = $other === null ? [$universe, $actor, $since] : [$universe, min($actor, $other), max($actor, $other), $since];
        $order = $recent ? 'at DESC,id DESC' : 'at,id';
        $rows = $this->query(
            "SELECT * FROM telemetry_events WHERE universe=? AND {$where} AND at>=? ORDER BY {$order} LIMIT " . ($limit + 1),
            $args
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['data'] = json_decode($row['data'], true);
        }
        return $rows;
    }

    public function daily(int $universe, int $actor, int $since): array
    {
        return $this->query(
            'SELECT * FROM telemetry_daily WHERE universe=? AND actor=? AND day>=? ORDER BY day',
            [$universe, $actor, gmdate('Y-m-d', $since - 300)]
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function actionCounts(int $universe, int $actor, int $from, int $to): array
    {
        $rows = $this->query(
            'SELECT kind,COUNT(DISTINCT request_id) AS count FROM telemetry_events
            WHERE universe=? AND actor=? AND at>=? AND at<=? AND interactive=1 GROUP BY kind',
            [$universe, $actor, $from, $to]
        )->fetchAll(PDO::FETCH_ASSOC);
        return array_column($rows, 'count', 'kind');
    }

    public function network(int $universe, int $actor, int $from, int $to): array
    {
        $where = 'universe=? AND actor=? AND at>=? AND at<=? AND interactive=1';
        $values = [$universe, $actor, $from, $to];
        $client = "JSON_UNQUOTE(JSON_EXTRACT(data, '$.client'))";
        $totals = $this->query(
            "SELECT COUNT(DISTINCT ip) AS ips,COUNT(DISTINCT {$client}) AS clients\n            FROM telemetry_events WHERE {$where}",
            $values
        )->fetch(PDO::FETCH_ASSOC);
        $totals['rows'] = $this->query(
            "SELECT ip,{$client} AS client,COUNT(DISTINCT request_id) AS requests,\n            MIN(at) AS first_seen,MAX(at) AS last_seen FROM telemetry_events\n            WHERE {$where} AND (ip IS NOT NULL OR {$client} IS NOT NULL)\n            GROUP BY ip,client ORDER BY last_seen DESC LIMIT 51",
            $values
        )->fetchAll(PDO::FETCH_ASSOC);
        $totals['more'] = count($totals['rows']) > 50;
        $totals['rows'] = array_slice($totals['rows'], 0, 50);
        return $totals;
    }

    public function warning(int $universe, int $actor, int $other, array $finding, array $settings, int $now): void
    {
        $evidence = json_encode($finding, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $this->query(
            'INSERT INTO telemetry_warnings
            (universe,actor,other,kind,strength,explanation,first_seen,last_seen,observation_start,observation_end,settings,evidence)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE
            last_seen=VALUES(last_seen),strength=VALUES(strength),explanation=VALUES(explanation),
            latest_evidence=VALUES(evidence),latest_settings=VALUES(settings),observation_end=VALUES(observation_end)',
            [
                $universe,
                $actor,
                $other,
                $finding['kind'],
                $finding['strength'],
                $finding['explanation'],
                $now,
                $now,
                $finding['from'],
                $finding['to'],
                json_encode($settings, JSON_THROW_ON_ERROR),
                $evidence,
            ]
        );
    }

    public function maintenance(array $settings, int $now): array
    {
        $reservation = self::health()['estimated_new_bytes'] ?? 0;
        // Allocation includes free pages: DELETE alone does not prove disk was released.
        $analysis = $this->query('ANALYZE TABLE telemetry_daily,telemetry_events,telemetry_warnings,telemetry_audit')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($analysis as $row) {
            if ($row['Msg_type'] === 'error') {
                throw new RuntimeException('Telemetry allocation statistics unavailable.');
            }
        }
        $this->db->exec('SET SESSION information_schema_stats_expiry=0');
        $size = $this->query('SELECT COALESCE(SUM(DATA_LENGTH+INDEX_LENGTH+DATA_FREE),0) AS allocated,
            COALESCE(SUM(TABLE_ROWS),0) AS estimated_rows FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetch(PDO::FETCH_ASSOC);
        $mb = (float) $size['allocated'] / 1048576;
        $suspended = $mb >= $settings['budget_mb'] - $settings['reserve_mb'];
        $pressured = $mb >= ($settings['budget_mb'] - $settings['reserve_mb']) * 0.8;
        $days = $pressured ? 1 : $settings['event_days'];
        $limit = (int) $settings['cleanup_rows'];
        $deleted = 0;
        $removed = [];
        foreach ([
            ['telemetry_events', 'at', $now - $days * 86400, ''],
            ['telemetry_daily', 'day', gmdate('Y-m-d', $now - $settings['daily_days'] * 86400), ''],
            ['telemetry_warnings', 'last_seen', $now - $settings['closed_days'] * 86400, " AND status='dismissed'"],
            ['telemetry_audit', 'at', $now - 365 * 86400, ' AND warning_id IS NULL'],
        ] as [$table, $column, $boundary, $extra]) {
            $count = $this->query(
                "DELETE FROM {$table} WHERE {$column}<? {$extra} ORDER BY {$column} LIMIT {$limit}",
                [$boundary]
            )->rowCount();
            $deleted += $count;
            if ($count && in_array($table, ['telemetry_events', 'telemetry_daily'], true)) {
                $removed[$table === 'telemetry_events' ? 'events_removed_before' : 'daily_removed_before'] = is_int($boundary) ? $boundary : strtotime($boundary . ' UTC');
            }
        }
        $change = [
            'maintenance_at' => $now,
            'allocated_mb' => $mb,
            'estimated_rows' => (int) $size['estimated_rows'],
            'pressured' => $pressured,
            'effective_event_days' => $days,
            'cleanup_deleted' => $deleted,
        ] + $removed;
        return self::health(static function (array $old) use ($change, $reservation, $settings, $mb, $now): array {
            // Writes made during measurement still need their reservation.
            $change['estimated_new_bytes'] = max(0, ($old['estimated_new_bytes'] ?? 0) - $reservation);
            $change['suspended'] = $mb + $change['estimated_new_bytes'] / 1048576 >= $settings['budget_mb'] - $settings['reserve_mb'];
            if ($change['suspended']) {
                $change['event_gap_start'] = $old['event_gap_start'] ?? $now;
            } elseif (!empty($old['event_gap_start'])) {
                $change['gaps'] = array_slice(
                    array_merge(
                        $old['gaps'],
                        [['from' => $old['event_gap_start'], 'to' => $now, 'reason' => 'storage_events']]
                    ),
                    -200
                );
                $change['event_gap_start'] = null;
            }
            return $change;
        });
    }

    public static function switchChanged(int $universe, string $reason, bool $enabled, int $now): void
    {
        self::health(static function (array $health) use ($universe, $reason, $enabled, $now): array {
            $key = $reason . '_since_' . $universe;
            if (!$enabled) {
                return [$key => $health[$key] ?? $now];
            }
            if (!isset($health[$key])) {
                return [];
            }
            return [
                $key => null,
                'gaps' => array_slice(array_merge($health['gaps'], [
                    ['from' => $health[$key], 'to' => $now, 'reason' => $reason, 'universe' => $universe],
                ]), -200),
            ];
        });
    }

    public static function interruptions(int $universe, int $from, int $to, bool $events = true): array
    {
        $health = self::health();
        $gaps = $health['gaps'] ?? [];
        foreach (['gap_start' => 'write_failed', 'disabled_since_' . $universe => 'disabled'] as $key => $reason) {
            if (!empty($health[$key])) {
                $gaps[] = ['from' => $health[$key], 'to' => $to, 'reason' => $reason];
            }
        }
        if ($events && !empty($health['event_gap_start'])) {
            $gaps[] = ['from' => $health['event_gap_start'], 'to' => $to, 'reason' => 'storage_events'];
        }
        if ($events && !empty($health['events_disabled_since_' . $universe])) {
            $gaps[] = ['from' => $health['events_disabled_since_' . $universe], 'to' => $to, 'reason' => 'events_disabled'];
        }
        $boundary = $health[$events ? 'events_removed_before' : 'daily_removed_before'] ?? 0;
        if ($from < $boundary) {
            $gaps[] = ['from' => $from, 'to' => $boundary, 'reason' => 'retention'];
        }
        return array_values(array_filter(
            $gaps,
            static fn($gap) => ($gap['universe'] ?? $universe) === $universe && $gap['from'] <= $to && $gap['to'] >= $from && ($events || !in_array($gap['reason'], ['storage_events', 'events_disabled'], true))
        ));
    }
}
