<?php

final class TelemetryPresentation
{
	private const DURATION_KEYS = ['active_seconds', 'largest_gap_seconds', 'shortest_wait', 'average_wait', 'longest_wait'];
	private const PERCENT_KEYS = ['share', 'allowance'];
	private const NUMBER_KEYS = ['metal', 'crystal', 'deuterium', 'remaining', 'unpaid', 'minimum', 'observations'];
	// JSON columns sort keys by length, so these are put back in reading order.
	private const ORDER = ['from', 'to', 'idle_hours', 'loads', 'per_hour', 'shortest_wait', 'average_wait', 'longest_wait'];

	public function __construct(private DateTimeZone $timezone)
	{
	}

	public function date(int $at): string
	{
		return $this->local($at)->format('d/m/Y H:i:s T');
	}

	private function local(int $at): DateTimeImmutable
	{
		return (new DateTimeImmutable('@' . $at))->setTimezone($this->timezone);
	}

	public static function duration(int $seconds): string
	{
		global $LNG;
		$units = [86400 => $LNG['telemetry_duration_day'] ?? 'd', 3600 => 'h', 60 => 'min', 1 => 's'];
		$parts = [];
		foreach ($units as $unit => $label) {
			$value = intdiv($seconds, $unit);
			if ($value > 0) {
				$parts[] = $value . ' ' . $label;
			}
			$seconds %= $unit;
		}
		return $parts ? implode(' ', $parts) : '0 s';
	}

	public static function setting(string $key, $value): string
	{
		global $LNG;
		$definition = TelemetrySettings::definitions()[$key] ?? ['unit' => ''];
		if ($definition['unit'] === '0/1') {
			return $value ? $LNG['telemetry_on'] : $LNG['telemetry_off'];
		}
		if ($definition['unit'] === 'fraction') {
			return round($value * 100, 3) . ' %';
		}
		return trim($value . ' ' . TelemetrySettings::unitLabel($definition));
	}

	public function activity(array $daily, int $from, int $to): array
	{
		$windows = TelemetryActivity::windows($daily);
		$days = [];
		$total = 0;
		$activeDays = 0;
		$end = $this->local($to);
		for ($date = $this->local($from)->setTime(0, 0); $date <= $end; $date = $date->modify('+1 day')) {
			$dayStart = max($from, $date->getTimestamp());
			$dayEnd = min($to + 60, $date->modify('+1 day')->getTimestamp());
			$day = ['day' => $date->format('d/m/Y'), 'bars' => [], 'windows' => [], 'seconds' => 0];
			foreach (TelemetryActivity::intervals($windows, $dayStart, $dayEnd) as [$first, $last]) {
				$day['seconds'] += $last - $first;
				$day['windows'][] = [
					'range' => $this->local($first)->format('H:i:s') . ' – ' . $this->local($last)->format('H:i:s'),
					'duration' => self::duration($last - $first),
				];
				array_push($day['bars'], ...$this->bars($first, $last));
			}
			$total += $day['seconds'];
			$activeDays += (int) ($day['seconds'] > 0);
			$day['duration'] = self::duration($day['seconds']);
			$days[$date->format('Y-m-d')] = $day;
		}
		$last = null;
		foreach ($windows as [, $at]) {
			if ($at >= $from && $at <= $to) {
				$last = max($last ?? 0, $at);
			}
		}
		$average = (int) round($total / max(1, count($days)));
		return [
			'days' => array_reverse($days, true),
			'active_days' => $activeDays,
			'total_seconds' => $total,
			'total' => self::duration($total),
			'average_seconds' => $average,
			'average' => self::duration($average),
			'last_activity' => $last === null ? '—' : $this->date($last),
		];
	}

	/** Heatmap bars as percentages of the local day, split at clock changes. */
	private function bars(int $first, int $last): array
	{
		$title = $this->date($first) . ' – ' . $this->date($last);
		$stops = array_column($this->timezone->getTransitions($first, $last) ?: [], 'ts');
		$stops[] = $last;
		$bars = [];
		$at = $first;
		foreach ($stops as $stop) {
			if ($stop <= $at) {
				continue;
			}
			$local = $this->local($at);
			$secondOfDay = (int) $local->format('G') * 3600 + (int) $local->format('i') * 60 + (int) $local->format('s');
			$bars[] = ['start' => $secondOfDay / 86400 * 100, 'width' => ($stop - $at) / 86400 * 100, 'date' => $title];
			$at = $stop;
		}
		return $bars;
	}

	public static function label(string $key): string
	{
		global $LNG;
		if (str_starts_with($key, 'pushing.')) {
			$key = 'pushing';
		}
		return $LNG['telemetry_label_' . str_replace('.', '_', $key)]
			?? TelemetrySettings::definitions()[$key]['label']
			?? $key;
	}

	public static function explanation(string $kind): string
	{
		global $LNG;
		return $LNG['telemetry_explain_' . explode('.', $kind)[0]];
	}

	public function exchange(array $metrics): ?array
	{
		global $LNG;
		if (!isset($metrics['sent'], $metrics['balance'])) {
			return null;
		}
		$totals = [[$LNG['telemetry_sent'], $metrics['sent']], [$LNG['telemetry_returned'], $metrics['returned'] ?? []]];
		$lifetime = $metrics['lifetime'] ?? null;
		if ($lifetime) {
			$totals[] = [self::label('lifetime') . ' / ' . self::label('sent'), $lifetime['sent']];
			$totals[] = [self::label('lifetime') . ' / ' . self::label('returned'), $lifetime['returned']];
		}
		$resources = [];
		foreach ($totals as [$label, $values]) {
			$row = ['label' => $label];
			foreach (['metal', 'crystal', 'deuterium'] as $resource) {
				$row[$resource] = pretty_number($values[$resource] ?? 0);
			}
			$resources[] = $row;
		}
		$equivalent = $LNG['telemetry_equivalent'];
		$exchange = [
			'sender' => (int) $metrics['sender'],
			'recipient' => (int) $metrics['recipient'],
			'resources' => $resources,
			'summary' => [
				['label' => $LNG['telemetry_unpaid_benefit'], 'value' => pretty_number($metrics['balance']['remaining']) . $equivalent],
				['label' => $LNG['telemetry_threshold'], 'value' => pretty_number($metrics['minimum']) . $equivalent],
				['label' => $LNG['telemetry_allowance'], 'value' => round($metrics['allowance'] * 100, 3) . ' %'],
				['label' => $LNG['telemetry_rate'], 'value' => self::rates($metrics['allowed_rates'])],
				['label' => $LNG['telemetry_deadline'], 'value' => $this->date((int) $metrics['deadline'])],
			],
		];
		if ($lifetime) {
			foreach (['deliveries', 'returned_deliveries', 'since'] as $key) {
				$exchange['summary'][] = ['label' => self::label('lifetime') . ' / ' . self::label($key), 'value' => $this->value($key, $lifetime[$key])];
			}
		}
		return $exchange;
	}

	private static function rates(array $rates): string
	{
		return implode(' – ', array_map(static fn($rate) => implode(':', $rate), $rates));
	}

	/** Turns nested metrics into label/value rows, e.g. "Sent / Metal". */
	public function rows(array $data, string $prefix = ''): array
	{
		global $LNG;
		$order = array_flip(self::ORDER);
		uksort($data, static fn($a, $b) => ($order[$a] ?? count($order)) <=> ($order[$b] ?? count($order)));
		$rows = [];
		foreach ($data as $key => $value) {
			// Combat details are already in the timeline.
			if ($key === 'combat_context') {
				continue;
			}
			$label = is_int($key) ? (string) ($key + 1) : self::label($key);
			if ($prefix !== '') {
				$label = $prefix . ' / ' . $label;
			}
			if ($key === 'windows') {
				foreach ($value as [$first, $last]) {
					$rows[] = ['label' => $label, 'value' => $this->date($first) . ' – ' . $this->date($last)];
				}
			} elseif ($key === 'seconds' && is_array($value)) {
				$rows[] = ['label' => $label, 'value' => implode(' → ', array_map([self::class, 'duration'], $value))];
			} elseif ($key === 'per_hour') {
				$rows[] = ['label' => $label, 'value' => implode(' · ', $value)];
			} elseif ($key === 'allowed_rates') {
				$rows[] = ['label' => $label, 'value' => self::rates($value)];
			} elseif ($key === 'ships') {
				foreach ($value as $ship => $count) {
					$rows[] = ['label' => $label . ' / ' . ($LNG['tech'][$ship] ?? $ship), 'value' => pretty_number($count)];
				}
			} elseif (is_array($value)) {
				$rows = array_merge($rows, $this->rows($value, $label));
			} else {
				$rows[] = ['label' => $label, 'value' => $this->value($key, $value)];
			}
		}
		return $rows;
	}

	private function value($key, $value)
	{
		global $LNG;
		if ($value === null) {
			return '—';
		}
		if (in_array($key, ['deadline', 'since', 'from', 'to'], true)) {
			return $value ? $this->date((int) $value) : '—';
		}
		if (!is_numeric($value)) {
			return $value;
		}
		if (in_array($key, self::DURATION_KEYS, true)) {
			return self::duration((int) $value);
		}
		if (in_array($key, self::PERCENT_KEYS, true)) {
			return round($value * 100, 3) . ' %';
		}
		if (in_array($key, self::NUMBER_KEYS, true)) {
			return pretty_number($value);
		}
		return is_float($value) ? round($value, 3) : $value;
	}

	public function timeline(array $events): array
	{
		usort($events, static fn($a, $b) => $a['at'] <=> $b['at']);
		foreach ($events as &$event) {
			$event['date'] = $this->date((int) $event['at']);
			$event['label'] = self::label($event['kind'] ?? 'availability');
			$data = $event['data'] ?? array_intersect_key($event, array_flip(self::DURATION_KEYS));
			$event['details'] = $this->rows($data);
		}
		return $events;
	}

	/** Client profiles are stored as codes, e.g. "Firefox · other · desktop". */
	public static function client(string $client): string
	{
		global $LNG;
		$parts = explode(' · ', $client);
		foreach ($parts as &$part) {
			$part = $LNG['telemetry_client_' . $part] ?? $part;
		}
		return implode(' · ', $parts);
	}
}
