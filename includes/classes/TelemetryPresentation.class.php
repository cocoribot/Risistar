<?php

final class TelemetryPresentation
{
	public function __construct(private DateTimeZone $timezone)
	{
	}

	public function date(int $at): string
	{
		return (new DateTimeImmutable('@' . $at))->setTimezone($this->timezone)->format('d/m/Y H:i:s T');
	}

	public static function duration(int $seconds): string
	{
		global $LNG;
		$parts = [];
		foreach ([86400 => ($LNG['telemetry_duration_day'] ?? 'd'), 3600 => 'h', 60 => 'min', 1 => 's'] as $unit => $label) {
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
		$unit = TelemetrySettings::definitions()[$key][4] ?? '';
		if ($unit === '0/1' || $key === 'enabled') {
			return $value ? $LNG['telemetry_on'] : $LNG['telemetry_off'];
		}
		return $unit === 'fraction' ? round($value * 100, 3) . ' %' : trim($value . ' ' . ($LNG['telemetry_unit_' . $unit] ?? $unit));
	}

	public function activity(array $daily, int $from, int $to): array
	{
		$start = (new DateTimeImmutable('@' . $from))->setTimezone($this->timezone)->setTime(0, 0);
		$end = (new DateTimeImmutable('@' . $to))->setTimezone($this->timezone);
		$windows = TelemetryActivity::windows($daily);
		$days = [];
		$total = 0;
		$activeDays = 0;
		for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
			$intervals = TelemetryActivity::intervals($windows, max($from, $date->getTimestamp()), min($to + 60, $date->modify('+1 day')->getTimestamp()));
			$day = ['day' => $date->format('d/m/Y'), 'bars' => [], 'windows' => [], 'seconds' => 0];
			foreach ($intervals as [$first, $last]) {
				$a = (new DateTimeImmutable('@' . $first))->setTimezone($this->timezone);
				$b = (new DateTimeImmutable('@' . $last))->setTimezone($this->timezone);
				$day['seconds'] += $last - $first;
				$label = $this->date($first) . ' – ' . $this->date($last);
				$day['windows'][] = ['range' => $a->format('H:i:s') . ' – ' . $b->format('H:i:s'), 'duration' => self::duration($last - $first)];
				$boundaries = array_column($this->timezone->getTransitions($first, $last) ?: [], 'ts');
				$boundaries[] = $last;
				$at = $first;
				foreach ($boundaries as $stop) {
					if ($stop <= $at) {
						continue;
					}
					$local = (new DateTimeImmutable('@' . $at))->setTimezone($this->timezone);
					$second = (int)$local->format('G') * 3600 + (int)$local->format('i') * 60 + (int)$local->format('s');
					$day['bars'][] = ['start' => $second / 864, 'width' => ($stop - $at) / 864, 'date' => $label];
					$at = $stop;
				}
			}
			$total += $day['seconds'];
			$activeDays += (int) ($day['seconds'] > 0);
			$day['duration'] = self::duration($day['seconds']);
			$days[$date->format('Y-m-d')] = $day;
		}
		$last = null;
		foreach ($windows as [$first, $at]) {
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

	public static function label(string $key): string
	{
		global $LNG;
		if (str_starts_with($key, 'pushing.')) {
			$key = 'pushing';
		}
		return $LNG['telemetry_label_' . str_replace('.', '_', $key)] ?? TelemetrySettings::definitions()[$key][5] ?? $key;
	}
	public static function explanation(string $kind): string
	{
		global $LNG;
		return $LNG['telemetry_explain_' . explode('.', $kind)[0]];
	}

	public function exchange(array $metrics): ?array
	{
		global $LNG;
		if (!isset($metrics['sent'], $metrics['balance'], $metrics['total_balance'])) {
			return null;
		}
		$resources = [];
		foreach (['sent' => $LNG['telemetry_sent'], 'returned' => $LNG['telemetry_returned'], 'overdue_sent' => $LNG['telemetry_overdue']] as $key => $label) {
			if ($key === 'overdue_sent' && $metrics[$key] == $metrics['sent']) {
				continue;
			}
			$row = ['label' => $label];
			foreach (['metal', 'crystal', 'deuterium'] as $resource) {
				$row[$resource] = pretty_number($metrics[$key][$resource] ?? 0);
			}
			$resources[] = $row;
		}
		$pending = !empty($metrics['awaiting_repayment']);
		$balance = $metrics[$pending ? 'total_balance' : 'balance'];
		return [
			'sender' => (int) $metrics['sender'],
			'recipient' => (int) $metrics['recipient'],
			'resources' => $resources,
			'summary' => [
				['label' => $pending ? $LNG['telemetry_pending_benefit'] : $LNG['telemetry_unpaid_benefit'], 'value' => pretty_number($balance['remaining']) . $LNG['telemetry_equivalent']],
				['label' => $LNG['telemetry_threshold'], 'value' => pretty_number($metrics['minimum']) . $LNG['telemetry_equivalent']],
				['label' => $LNG['telemetry_allowance'], 'value' => round($metrics['allowance'] * 100, 3) . ' %'],
				['label' => $LNG['telemetry_rate'], 'value' => implode(':', $balance['rate'])],
				['label' => $LNG['telemetry_deadline'], 'value' => $this->date((int) $metrics['deadline'])],
			],
		];
	}

	public function rows(array $data, string $prefix = '', bool $dates = false): array
	{
		global $LNG;
		$rows = [];
		foreach ($data as $key => $value) {
			// Combat details are already in the timeline.
			if ($key === 'combat_context' || $key === 'slots') {
				continue;
			}
			$label = $prefix . ($prefix ? ' / ' : '') . (is_int($key) ? (string) ($key + 1) : self::label($key));
			if ($key === 'windows') {
				foreach ($value as [$first, $last]) {
					$rows[] = ['label' => $label, 'value' => $this->date($first) . ' – ' . $this->date($last)];
				}
				continue;
			}
			if (is_array($value)) {
				if ($key === 'seconds') {
					$value = implode(' → ', array_map([self::class, 'duration'], $value));
				} elseif ($key === 'rate') {
					$value = implode(':', $value);
				} elseif ($key === 'pattern') {
					$value = implode(' → ', array_map(static fn($v) => is_string($v) ? self::label(rtrim($v, ':')) : $v, $value));
				} else {
					$rows = array_merge($rows, $this->rows($value, $label, $key === 'starts'));
					continue;
				}
			}
			if (in_array($key, ['active_seconds', 'largest_gap_seconds', 'span_seconds'], true) && is_numeric($value)) {
				$value = self::duration((int) $value);
			} elseif (($dates || in_array($key, ['deadline'], true)) && $value) {
				$value = $this->date((int) $value);
			} elseif (is_bool($value)) {
				$value = $value ? $LNG['telemetry_yes'] : $LNG['telemetry_no'];
			} elseif ($value === null) {
				$value = '—';
			} elseif (in_array($key, ['share', 'allowance'], true) && is_numeric($value)) {
				$value = round($value * 100, 3) . ' %';
			} elseif (in_array($key, ['metal', 'crystal', 'deuterium', 'remaining', 'minimum', 'sent_value', 'returned_value', 'observations', 'repeats', 'checks'], true) && is_numeric($value)) {
				$value = pretty_number($value);
			} elseif (is_float($value)) {
				$value = round($value, 3);
			}
			$rows[] = ['label' => $label, 'value' => $value];
		}
		return $rows;
	}

	public function timeline(array $events): array
	{
		usort($events, static fn($a, $b) => $a['at'] <=> $b['at']);
		foreach ($events as &$event) {
			$event['date'] = $this->date((int) $event['at']);
			$event['label'] = self::label($event['kind'] ?? 'availability');
			$data = $event['data'] ?? array_intersect_key($event, array_flip(['active_seconds', 'largest_gap_seconds']));
			if (!empty($event['ip'])) {
				$data['ip'] = $event['ip'];
			}
			$event['details'] = $this->rows($data);
		}
		return $events;
	}

	public function gaps(array $gaps): array
	{
		foreach ($gaps as &$gap) {
			$gap['start'] = $this->date((int) $gap['from']);
			$gap['end'] = $this->date((int) $gap['to']);
			$gap['label'] = self::label($gap['reason']);
		}
		return $gaps;
	}
}
