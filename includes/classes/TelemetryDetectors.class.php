<?php

/** Deterministic rules; callers supply a clock, bounded rows and settings. */
final class TelemetryDetectors
{
    private static function finding(string $kind, string $strength, string $text, array $rows, array $metrics): array
    {
        $times = array_column($rows, 'at');
        return [
            'kind' => $kind,
            'strength' => $strength,
            'explanation' => $text,
            'from' => $times ? min($times) : 0,
            'to' => $times ? max($times) : 0,
            'metrics' => $metrics,
            'timeline' => $rows,
        ];
    }

    public static function availability(array $daily, array $s, int $now): array
    {
        $days = [];
        $timeline = [];
        $end = strtotime(gmdate('Y-m-d', $now) . ' UTC');
        $windows = TelemetryActivity::windows($daily);
        for ($at = $end - $s['activity_days'] * 86400; $at < $end; $at += 86400) {
            $intervals = TelemetryActivity::intervals($windows, $at, $at + 86400);
            $seconds = 0;
            $previous = $at;
            $gap = 0;
            foreach ($intervals as [$first, $last]) {
                $seconds += $last - $first;
                $gap = max($gap, $first - $previous);
                $previous = $last;
            }
            $gap = max($gap, $at + 86400 - $previous);
            $metrics = ['day' => gmdate('Y-m-d', $at), 'active_seconds' => $seconds, 'largest_gap_seconds' => $gap];
            if ($seconds >= $s['activity_hours'] * 3600) {
                $days[] = $metrics;
            }
            if ($intervals) {
                $timeline[] = ['at' => $at, 'data' => $metrics + ['windows' => $intervals]];
            }
        }
        if (!$days) {
            return [];
        }
        $short = count(array_filter($days, static fn($d) => $d['largest_gap_seconds'] <= $s['activity_gap_hours'] * 3600));
        $text = count($days) . ' jour(s) à longue durée active estimée ; ' . $short . ' avec de courtes pauses. ' . 'Signalement de disponibilité.';
        return [self::finding(
            'availability',
            count($days) >= $s['activity_min_days'] ? 'moderate' : 'weak',
            $text,
            $timeline,
            ['days' => $days, 'short_gap_days' => $short, 'required_days' => $s['activity_min_days']]
        )];
    }

    public static function readerActions(array $events): array
    {
        $unique = [];
        foreach ($events as $e) {
            if (empty($e['interactive']) || $e['kind'] === 'interaction' || ($e['result'] ?? 'success') !== 'success') {
                continue;
            }
            // Count each action kind once per request.
            $key = $e['request_id'] . ':' . $e['kind'];
            $unique[$key] ??= $e;
        }
        $rows = array_values($unique);
        usort($rows, static fn($a, $b) => $a['at'] <=> $b['at']);
        return $rows;
    }

    public static function automation(array $events, array $s, int $now): array
    {
        $events = array_values(array_filter($events, static fn($e) => $e['at'] >= $now - $s['automation_days'] * 86400));
        $actions = self::readerActions($events);
        $findings = [];
        $n = count($actions);
        if ($n >= $s['timing_min']) {
            $deltas = [];
            for ($i = 1; $i < $n; ++$i) {
                $delta = $actions[$i]['at'] - $actions[$i - 1]['at'];
                if ($delta > 0 && $delta < 3600) {
                    $deltas[] = $delta;
                }
            }
            $best = ['share' => 0];
            for ($period = 1; $period <= 4; ++$period) {
                $matches = 0;
                $medians = [];
                for ($phase = 0; $phase < $period; ++$phase) {
                    $values = [];
                    for ($i = $phase; $i < count($deltas); $i += $period) {
                        $values[] = $deltas[$i];
                    }
                    sort($values);
                    if (!$values) {
                        continue;
                    }
                    $median = $values[intdiv(count($values), 2)];
                    $medians[] = $median;
                    $matches += count(array_filter($values, static fn($v) => abs($v - $median) <= max(1, $median * $s['timing_tolerance'])));
                }
                $share = $matches / max(1, count($deltas));
                if ($share > $best['share']) {
                    $best = ['share' => $share, 'period' => $period, 'seconds' => $medians, 'observations' => count($deltas)];
                }
            }
            if (($best['observations'] ?? 0) >= $s['timing_min'] - 1 && $best['share'] >= $s['timing_share']) {
                $findings[] = self::finding(
                    'timing',
                    'moderate',
                    'Intervalles régulièrement répétés.',
                    $actions,
                    $best
                );
            }
        }
        $symbols = array_map(static fn($e) => $e['kind'] . ':' . ($e['data']['command'] ?? $e['data']['mission'] ?? ''), $actions);
        $best = ['repeats' => 0, 'share' => 0];
        for ($length = 2; $length <= 5 && $length <= $n; ++$length) {
            $candidates = [];
            for ($i = 0; $i < min($n - $length + 1, 1000); ++$i) {
                $pattern = array_slice($symbols, $i, $length);
                if (count(array_unique($pattern)) < 2) {
                    continue;
                }
                $key = implode('|', $pattern);
                $candidates[$key] = $pattern;
                if (count($candidates) >= 100) {
                    break;
                }
            }
            foreach ($candidates as $pattern) {
                $repeats = 0;
                $starts = [];
                for ($i = 0; $i < $n;) {
                    $j = $i;
                    $matched = 0;
                    $extra = 0;
                    while ($j < $n && $matched < $length && $extra <= 1) {
                        if ($symbols[$j] === $pattern[$matched]) {
                            ++$matched;
                        } else {
                            ++$extra;
                        }
                        ++$j;
                    }
                    if ($matched === $length && $extra <= 1) {
                        ++$repeats;
                        $starts[] = $actions[$i]['at'];
                        $i = $j;
                    } else {
                        ++$i;
                    }
                }
                $share = $repeats * $length / max(1, $n);
                if ($repeats >= $s['workflow_min'] && $share > $best['share']) {
                    $best = ['repeats' => $repeats, 'share' => $share, 'pattern' => $pattern, 'starts' => $starts];
                }
            }
        }
        if ($best['repeats'] >= $s['workflow_min'] && $best['share'] >= $s['workflow_share']) {
            $findings[] = self::finding('workflow', 'moderate', 'Séquence répétée d’actions.', $actions, $best);
        }
        $reads = array_values(array_filter($actions, static fn($e) => $e['kind'] === 'galaxy.view'));
        if (count($reads) >= $s['poll_min'] && end($reads)['at'] - $reads[0]['at'] >= $s['poll_span_hours'] * 3600) {
            $findings[] = self::finding(
                'polling',
                'weak',
                'Consultations répétées de la galaxie, y compris les contenus inchangés.',
                $reads,
                ['checks' => count($reads), 'span_seconds' => end($reads)['at'] - $reads[0]['at']]
            );
        }
        $steps = 0;
        $maxSteps = 0;
        $previous = null;
        foreach ($reads as $read) {
            if ($read['kind'] !== 'galaxy.view') {
                continue;
            }
            $coord = $read['data'];
            $steps = $previous && ($coord['galaxy'] ?? null) === ($previous['galaxy'] ?? null) && abs(($coord['system'] ?? -100) - ($previous['system'] ?? 100)) === 1 ? $steps + 1 : 1;
            $maxSteps = max($maxSteps, $steps);
            $previous = $coord;
        }
        if ($maxSteps >= $s['traversal_min']) {
            $findings[] = self::finding(
                'traversal',
                'weak',
                'Parcours répété de systèmes voisins ; exploration systématique observée.',
                $reads,
                ['consecutive_systems' => $maxSteps]
            );
        }
        $support = array_column($findings, 'kind');
        foreach ($findings as &$finding) {
            $finding['supporting_checks'] = $support;
        }
        return $findings;
    }

    private static function value(array $resources, float $metal, float $crystal): float
    {
        return ($resources['metal'] ?? 0) / $metal + ($resources['crystal'] ?? 0) / $crystal + ($resources['deuterium'] ?? 0);
    }
    /** Minimize over the inclusive rate corridor, including interior stationary points. */
    public static function favorable(array $sent, array $returned, array $s): array
    {
        $m = $s['rate_metal_min'];
        $c = $s['rate_crystal_min'];
        $dm = $s['rate_metal_max'] - $m;
        $dc = $s['rate_crystal_max'] - $c;
        $net = [];
        foreach (['metal', 'crystal', 'deuterium'] as $r) {
            $net[$r] = (1 - $s['imbalance_allowance']) * ($sent[$r] ?? 0) - ($returned[$r] ?? 0);
        }
        $candidates = [0, 1];
        // f'(t)= -A*dm/m(t)^2 - B*dc/c(t)^2.
        if ($dm > 0 && $dc > 0 && $net['metal'] * $net['crystal'] < 0) {
            $ratio = sqrt(-$net['crystal'] * $dc / ($net['metal'] * $dm));
            $denominator = $dc - $ratio * $dm;
            if (abs($denominator) > 1.0E-12) {
                $t = ($ratio * $m - $c) / $denominator;
                if ($t > 0 && $t < 1) {
                    $candidates[] = $t;
                }
            }
        }
        $best = null;
        foreach ($candidates as $t) {
            $metal = $m + $dm * $t;
            $crystal = $c + $dc * $t;
            $remaining = self::value($net, $metal, $crystal);
            if ($best === null || $remaining < $best['remaining']) {
                $best = [
                    'remaining' => $remaining,
                    'sent_value' => self::value($sent, $metal, $crystal),
                    'returned_value' => self::value($returned, $metal, $crystal),
                    'rate' => [$metal, $crystal, 1],
                ];
            }
        }
        return $best;
    }

    public static function pushing(array $events, int $a, int $b, array $points, array $s, int $now): array
    {
        $deliveries = array_values(array_filter(
            $events,
            static fn($e) => $e['kind'] === 'delivery' && $e['at'] >= $now - $s['push_days'] * 86400
        ));
        usort($deliveries, static fn($x, $y) => $x['at'] <=> $y['at']);
        if (!$deliveries) {
            return [];
        }
        $findings = [];
        foreach ([[$a, $b], [$b, $a]] as [$sender, $recipient]) {
            $sent = ['metal' => 0, 'crystal' => 0, 'deuterium' => 0];
            $overdue = $sent;
            $returned = $sent;
            $oldest = null;
            $first = null;
            foreach ($deliveries as $delivery) {
                $isSent = (int) $delivery['actor'] === $sender;
                if ($isSent) {
                    $first ??= $delivery['at'];
                }
                foreach (array_keys($sent) as $r) {
                    $amount = max(0, (float) ($delivery['data'][$r] ?? 0));
                    if ($isSent) {
                        $sent[$r] += $amount;
                        if ($delivery['at'] + $s['repayment_hours'] * 3600 <= $now) {
                            $overdue[$r] += $amount;
                            if ($amount > 0) {
                                $oldest ??= $delivery['at'];
                            }
                        }
                    } else {
                        $returned[$r] += $amount;
                    }
                }
            }
            $minimum = max($s['push_minimum'], ($points[$recipient] ?? 0) * 1000 * $s['push_points_fraction'] / $s['rate_metal_max']);
            $balance = self::favorable($overdue, $returned, $s);
            $total = self::favorable($sent, $returned, $s);
            if ($balance['remaining'] <= $minimum && $total['remaining'] <= $minimum) {
                continue;
            }
            $pending = $balance['remaining'] <= $minimum;
            $context = [];
            foreach ($events as $e) {
                if ($e['kind'] !== 'combat' || empty($e['data']['moon_chance'])) {
                    continue;
                }
                foreach ($deliveries as $d) {
                    if (abs($d['at'] - $e['at']) <= $s['moon_context_hours'] * 3600 && ($d['data']['planet'] ?? 0) === ($e['data']['planet'] ?? -1)) {
                        $context[] = $e;
                        break;
                    }
                }
            }
            $text = $pending ? 'Échange en attente de remboursement.' : 'Livraisons nettement unilatérales après le délai de remboursement.';
            if ($context) {
                $text .= ' Combat avec chance de lune à proximité.';
            }
            $finding = self::finding(
                'pushing.' . $recipient,
                $pending ? 'pending' : ($context ? 'uncertain' : 'moderate'),
                $text,
                array_merge($deliveries, $context),
                [
                    'sender' => $sender,
                    'recipient' => $recipient,
                    'sent' => $sent,
                    'overdue_sent' => $overdue,
                    'returned' => $returned,
                    'balance' => $balance,
                    'total_balance' => $total,
                    'minimum' => $minimum,
                    'awaiting_repayment' => $pending,
                    'deadline' => ($oldest ?? $first) + $s['repayment_hours'] * 3600,
                    'allowed_rates' => [[$s['rate_metal_min'], $s['rate_crystal_min'], 1], [$s['rate_metal_max'], $s['rate_crystal_max'], 1]],
                    'allowance' => $s['imbalance_allowance'],
                    'combat_context' => $context,
                ]
            );
            $findings[] = $finding;
        }
        return $findings;
    }
}
