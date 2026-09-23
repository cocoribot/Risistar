<?php

/** Detection rules only: the caller gives the time, the events and the settings. */
final class TelemetryDetectors
{
	// Fast human clicking is regular too; only slower rhythms count.
	private const MIN_GAP_SECONDS = 10;
	// Idle runs made mostly of alliance page views are reported as alliance watching.
	private const ALLIANCE_SHARE = 0.8;
	// A loop that waits 20 s then 40 s has two peaks; more would also match reloads by hand.
	private const MAX_PEAKS = 2;

	private static function finding(string $kind, string $strength, array $rows, array $metrics): array
	{
		$times = array_column($rows, 'at');
		return [
			'kind' => $kind,
			'strength' => $strength,
			'from' => $times ? min($times) : 0,
			'to' => $times ? max($times) : 0,
			'metrics' => $metrics,
			'timeline' => $rows,
		];
	}

	public static function availability(array $daily, array $settings, int $now): array
	{
		$days = [];
		$timeline = [];
		$today = strtotime(gmdate('Y-m-d', $now) . ' UTC');
		$windows = TelemetryActivity::windows($daily);
		for ($dayStart = $today - $settings['activity_days'] * 86400; $dayStart < $today; $dayStart += 86400) {
			$intervals = TelemetryActivity::intervals($windows, $dayStart, $dayStart + 86400);
			$metrics = ['day' => gmdate('Y-m-d', $dayStart)] + self::dayActivity($intervals, $dayStart);
			if ($metrics['active_seconds'] >= $settings['activity_hours'] * 3600) {
				$days[] = $metrics;
			}
			if ($intervals) {
				$timeline[] = ['at' => $dayStart, 'data' => $metrics + ['windows' => $intervals]];
			}
		}
		if (!$days) {
			return [];
		}
		$shortGapDays = array_filter(
			$days,
			static fn($day) => $day['largest_gap_seconds'] <= TelemetrySettings::ACTIVITY_GAP_HOURS * 3600
		);
		$strength = count($days) >= $settings['activity_min_days'] ? 'moderate' : 'weak';
		return [self::finding('availability', $strength, $timeline, [
			'days' => $days,
			'short_gap_days' => count($shortGapDays),
			'required_days' => $settings['activity_min_days'],
		])];
	}

	private static function dayActivity(array $intervals, int $dayStart): array
	{
		$seconds = 0;
		$largestGap = 0;
		$previousEnd = $dayStart;
		foreach ($intervals as [$first, $last]) {
			$seconds += $last - $first;
			$largestGap = max($largestGap, $first - $previousEnd);
			$previousEnd = $last;
		}
		$largestGap = max($largestGap, $dayStart + 86400 - $previousEnd);
		return ['active_seconds' => $seconds, 'largest_gap_seconds' => $largestGap];
	}

	/** Days where most gaps between requests repeat the same few durations, like a script would. */
	public static function automation(array $daily, array $settings, int $now): array
	{
		$since = gmdate('Y-m-d', $now - $settings['automation_days'] * 86400);
		$days = [];
		foreach ($daily as $row) {
			if ($row['day'] < $since) {
				continue;
			}
			$rhythm = self::rhythm(json_decode($row['gaps'], true), $settings);
			if ($rhythm['observations'] >= $settings['timing_min'] && $rhythm['share'] >= $settings['timing_share']) {
				$days[] = ['at' => strtotime($row['day'] . ' UTC'), 'kind' => 'timing', 'data' => ['day' => $row['day']] + $rhythm];
			}
		}
		if (!$days) {
			return [];
		}
		return [self::finding('timing', 'moderate', $days, ['days' => array_column($days, 'data')])];
	}

	/**
	 * Hours in a row of loads that keep the planets active (*) and nothing else, on several days,
	 * even with random waits. Each run shows its loads per hour and its waits.
	 */
	public static function refreshing(array $daily, array $settings, int $now): array
	{
		$idle = [];
		foreach ($daily as $row) {
			foreach (json_decode($row['hours'], true) as $hour => [$loads, $switches, $busy, $alliance, $shortest, $longest]) {
				$at = strtotime($row['day'] . ' UTC') + $hour * 3600;
				if ($at >= $now - $settings['automation_days'] * 86400 && $loads >= $settings['refresh_per_hour'] && $busy === 0) {
					$idle[$at] = [$loads, $switches, $alliance, $shortest, $longest];
				}
			}
		}
		ksort($idle);
		$runs = [];
		foreach ($idle as $at => [$loads, $switches, $alliance, $shortest, $longest]) {
			$last = array_key_last($runs);
			// Consecutive hours join the same run, also across midnight.
			if ($last !== null && $runs[$last]['to'] === $at) {
				$run = &$runs[$last];
				$run['to'] += 3600;
				$run['loads'] += $loads;
				$run['switches'] += $switches;
				$run['alliance'] += $alliance;
				$run['per_hour'][] = $loads;
				$run['shortest_wait'] = TelemetryActivity::shortest($run['shortest_wait'], $shortest);
				$run['longest_wait'] = max($run['longest_wait'], $longest);
				unset($run);
			} else {
				$runs[] = [
					'from' => $at, 'to' => $at + 3600, 'loads' => $loads, 'switches' => $switches, 'alliance' => $alliance,
					'per_hour' => [$loads], 'shortest_wait' => $shortest, 'longest_wait' => $longest,
				];
			}
		}
		$runs = array_values(array_filter(
			$runs,
			static fn($run) => $run['to'] - $run['from'] >= $settings['refresh_hours'] * 3600
		));
		// A day counts when a run starts on it or covers refresh_hours of it, so a longer run never counts less.
		$days = [];
		foreach ($runs as $run) {
			$days[gmdate('Y-m-d', $run['from'])] = true;
			$hours = array_count_values(array_map(static fn($at) => gmdate('Y-m-d', $at), range($run['from'], $run['to'] - 3600, 3600)));
			foreach ($hours as $day => $count) {
				if ($count >= $settings['refresh_hours']) {
					$days[$day] = true;
				}
			}
		}
		if (count($days) < $settings['refresh_days']) {
			return [];
		}
		$watching = array_sum(array_column($runs, 'alliance')) >= self::ALLIANCE_SHARE * array_sum(array_column($runs, 'loads'));
		$kind = $watching ? 'alliance_watch' : 'refreshing';
		$timeline = array_map(
			static fn($run) => ['at' => $run['from'], 'kind' => $kind, 'data' => $run + [
				'idle_hours' => ($run['to'] - $run['from']) / 3600,
				'average_wait' => intdiv($run['to'] - $run['from'], $run['loads']),
			]],
			$runs
		);
		return [self::finding($kind, 'moderate', $timeline, ['days' => count($days), 'required_days' => $settings['refresh_days']])];
	}

	/** Takes the biggest group of close gaps, then the next one, until they cover the required share. */
	private static function rhythm(array $gaps, array $settings): array
	{
		$total = array_sum($gaps);
		$width = (int) round(log(1 + $settings['timing_tolerance']) / log(TelemetryActivity::GAP_STEP));
		$seconds = [];
		$covered = 0;
		while (count($seconds) < self::MAX_PEAKS && $covered < $settings['timing_share'] * $total) {
			$best = null;
			$bestCount = 0;
			foreach (array_keys($gaps) as $center) {
				if (TelemetryActivity::gapSeconds($center) < self::MIN_GAP_SECONDS) {
					continue;
				}
				$count = array_sum(array_intersect_key($gaps, array_flip(range($center - $width, $center + $width))));
				if ($count > $bestCount) {
					[$best, $bestCount] = [$center, $count];
				}
			}
			if ($best === null) {
				break;
			}
			$gaps = array_diff_key($gaps, array_flip(range($best - $width, $best + $width)));
			$seconds[] = (int) round(TelemetryActivity::gapSeconds($best));
			$covered += $bestCount;
		}
		return ['share' => $total ? $covered / $total : 0, 'seconds' => $seconds, 'observations' => $total];
	}

	public static function pushing(array $events, int $accountA, int $accountB, array $points, array $settings, int $now): array
	{
		$since = $now - TelemetrySettings::deliveryDays($settings) * 86400;
		$deliveries = array_values(array_filter(
			$events,
			static fn($event) => $event['kind'] === 'delivery' && $event['at'] >= $since
		));
		if (!$deliveries) {
			return [];
		}
		usort($deliveries, static fn($a, $b) => $a['at'] <=> $b['at']);
		$debts = self::debts($deliveries, $settings);
		$recent = $now - $settings['push_days'] * 86400;
		$due = $now - $settings['repayment_hours'] * 3600;
		$findings = [];
		foreach ([[$accountA, $accountB], [$accountB, $accountA]] as [$sender, $recipient]) {
			$overdue = array_filter(
				$debts[$sender] ?? [],
				static fn($debt) => $debt['at'] >= $recent && $debt['at'] <= $due
			);
			$unpaid = array_sum(array_column($overdue, 'unpaid'));
			$remaining = array_sum(array_map(
				static fn($debt) => max(0, $debt['unpaid'] - $settings['imbalance_allowance'] * $debt['value']),
				$overdue
			));
			$minimum = max(
				$settings['push_minimum'],
				($points[$recipient] ?? 0) * 1000 * $settings['push_points_fraction'] / $settings['rate_metal_max']
			);
			if (!$overdue || $remaining <= $minimum) {
				continue;
			}
			$combats = self::moonCombats($events, $deliveries, $settings);
			$findings[] = self::finding(
				'pushing.' . $recipient,
				$combats ? 'uncertain' : 'moderate',
				array_merge($deliveries, $combats),
				[
					'sender' => $sender,
					'recipient' => $recipient,
					'sent' => self::totals($deliveries, $sender),
					'returned' => self::totals($deliveries, $recipient),
					'balance' => ['unpaid' => $unpaid, 'remaining' => $remaining],
					'minimum' => $minimum,
					'deadline' => min(array_column($overdue, 'at')) + $settings['repayment_hours'] * 3600,
					'allowed_rates' => [
						[$settings['rate_metal_min'], $settings['rate_crystal_min'], 1],
						[$settings['rate_metal_max'], $settings['rate_crystal_max'], 1],
					],
					'allowance' => $settings['imbalance_allowance'],
					'combat_context' => $combats,
				]
			);
		}
		return $findings;
	}

	/**
	 * Goes through the deliveries in time order. Each one first pays back what the other player
	 * still owes for sends made up to push_days before, oldest first. The rest is a new debt,
	 * but only the part that is more than what was paid back at any allowed rate.
	 */
	private static function debts(array $deliveries, array $settings): array
	{
		$debts = [];
		foreach ($deliveries as $delivery) {
			$sender = (int) $delivery['actor'];
			$other = (int) $delivery['target'];
			[$low, $high] = self::values(TelemetryStore::delivered($delivery['data']), $settings);
			if ($low <= 0) {
				continue;
			}
			$payment = $high;
			$paidBack = 0.0;
			foreach ($debts[$other] ?? [] as $index => $debt) {
				if ($payment <= 0 || $debt['unpaid'] <= 0 || $debt['at'] < $delivery['at'] - $settings['push_days'] * 86400) {
					continue;
				}
				$share = min(1, $payment / $debt['unpaid']);
				$payment -= $share * $debt['unpaid'];
				$paidBack += $share * $debt['unpaid_high'];
				$debts[$other][$index]['unpaid'] *= 1 - $share;
				$debts[$other][$index]['unpaid_high'] *= 1 - $share;
			}
			$gift = $low - $paidBack;
			if ($gift > 0) {
				// The allowance is a share of the whole delivery, not only of the unpaid part.
				$debts[$sender][] = ['at' => (int) $delivery['at'], 'unpaid' => $gift, 'unpaid_high' => $gift * $high / $low, 'value' => $low];
			}
		}
		return $debts;
	}

	/** Deuterium value at the rates least and most favorable to the players. */
	private static function values(array $resources, array $settings): array
	{
		$value = static fn($metalRate, $crystalRate) => $resources['metal'] / $metalRate
			+ $resources['crystal'] / $crystalRate
			+ $resources['deuterium'];
		return [
			$value($settings['rate_metal_max'], $settings['rate_crystal_max']),
			$value($settings['rate_metal_min'], $settings['rate_crystal_min']),
		];
	}

	private static function totals(array $deliveries, int $sender): array
	{
		$totals = ['metal' => 0.0, 'crystal' => 0.0, 'deuterium' => 0.0];
		foreach ($deliveries as $delivery) {
			if ((int) $delivery['actor'] === $sender) {
				foreach (TelemetryStore::delivered($delivery['data']) as $resource => $amount) {
					$totals[$resource] += $amount;
				}
			}
		}
		return $totals;
	}

	/** A combat with a moon chance between the two players, close to a delivery, may explain it. */
	private static function moonCombats(array $events, array $deliveries, array $settings): array
	{
		$combats = [];
		foreach ($events as $event) {
			if ($event['kind'] !== 'combat' || empty($event['data']['moon_chance'])) {
				continue;
			}
			foreach ($deliveries as $delivery) {
				if (abs($delivery['at'] - $event['at']) <= $settings['moon_context_hours'] * 3600) {
					$combats[] = $event;
					break;
				}
			}
		}
		return $combats;
	}
}
