<?php

final class TelemetrySettings
{
	/** group, default, minimum, maximum, unit, label, explanation */
	public static function definitions(): array
	{
		$definitions = [
			'events_enabled' => ['collection', 1, 0, 1, '0/1'],
			'network_enabled' => ['collection', 1, 0, 1, '0/1'],
			'activity_days' => ['activity', 7, 2, 90, 'days'],
			'activity_hours' => ['activity', 20, 1, 24, 'hours_day'],
			'activity_min_days' => ['activity', 4, 1, 90, 'days'],
			'activity_gap_hours' => ['activity', 3.0, 0.25, 24, 'hours'],
			'automation_days' => ['automation', 7, 1, 30, 'days'],
			'timing_min' => ['automation', 30, 8, 5000, 'actions'],
			'timing_tolerance' => ['automation', 0.1, 0.01, 0.5, 'fraction'],
			'timing_share' => ['automation', 0.8, 0.5, 1, 'fraction'],
			'workflow_min' => ['automation', 8, 3, 1000, 'repetitions'],
			'workflow_share' => ['automation', 0.6, 0.2, 1, 'fraction'],
			'poll_min' => ['automation', 40, 5, 10000, 'views'],
			'poll_span_hours' => ['automation', 2.0, 0.1, 168, 'hours'],
			'traversal_min' => ['automation', 12, 4, 1000, 'systems'],
			'push_days' => ['pushing', 7, 3, 30, 'days'],
			'repayment_hours' => ['pushing', 48, 1, 168, 'hours'],
			'rate_metal_min' => ['pushing', 2, 1, 10, 'metal_deuterium'],
			'rate_metal_max' => ['pushing', 4, 1, 10, 'metal_deuterium'],
			'rate_crystal_min' => ['pushing', 1, 1, 10, 'crystal_deuterium'],
			'rate_crystal_max' => ['pushing', 2, 1, 10, 'crystal_deuterium'],
			'imbalance_allowance' => ['pushing', 0.25, 0, 0.75, 'fraction'],
			'push_minimum' => ['pushing', 100000, 1, 1000000000000000.0, 'deuterium_equivalent'],
			'push_points_fraction' => ['pushing', 0.01, 0, 1, 'fraction'],
			'moon_context_hours' => ['pushing', 6.0, 0.5, 48, 'hours'],
			'event_days' => ['storage', 7, 1, 30, 'days'],
			'daily_days' => ['storage', 90, 7, 180, 'days'],
			'closed_days' => ['storage', 90, 7, 365, 'days'],
			'budget_mb' => ['advanced', 1800, 100, 1900, 'mb'],
			'reserve_mb' => ['advanced', 400, 20, 500, 'mb'],
			'cleanup_rows' => ['advanced', 1000, 100, 10000, 'rows'],
			'analysis_accounts' => ['advanced', 10, 1, 50, 'accounts'],
			'analysis_events' => ['advanced', 10000, 100, 50000, 'events'],
		];
		global $LNG;
		foreach ($definitions as $name => &$definition) {
			$definition[] = $LNG['telemetry_setting_' . $name] ?? $name;
			$definition[] = $LNG['telemetry_setting_' . $name . '_help'] ?? '';
		}
		return $definitions;
	}

	public static function storage(array $settings): array
	{
		$combined = self::defaults();
		foreach (['event_days', 'daily_days', 'closed_days', 'push_days', 'repayment_hours'] as $key) {
			$combined[$key] = max(array_column($settings, $key));
		}
		$combined['cleanup_rows'] = min(array_column($settings, 'cleanup_rows'));
		$combined['budget_mb'] = min(array_column($settings, 'budget_mb'));
		$combined['reserve_mb'] = max(array_column($settings, 'reserve_mb'));
		return $combined;
	}

	public static function deliveryDays(array $settings): int
	{
		return max(2 * (int)$settings['push_days'], (int)$settings['push_days'] + (int)ceil($settings['repayment_hours'] / 24));
	}

	public static function defaults(): array
	{
		return array_map(static fn($d) => $d[1], self::definitions());
	}

	public static function get(int $universe): array
	{
		$config = Config::get($universe);
		$saved = isset($config->telemetry_settings) ? json_decode($config->telemetry_settings, true) : [];
		$modules = explode(';', $config->moduls);
		return ['enabled' => (int) ($modules[MODULE_TELEMETRY] ?? 0)]
			+ array_replace(self::defaults(), array_intersect_key(is_array($saved) ? $saved : [], self::defaults()));
	}

	public static function validate(array $input): array
	{
		global $LNG;
		$out = [];
		foreach (self::definitions() as $name => $d) {
			$value = $input[$name] ?? null;
			if (!is_scalar($value) || !is_numeric($value) || !is_finite((float) $value) || $value < $d[2] || $value > $d[3] || is_int($d[1]) && (float) $value != (int) $value) {
				$scale = $d[4] === 'fraction' ? 100 : 1;
				$unit = $scale === 100 ? '%' : ($LNG['telemetry_unit_' . $d[4]] ?? $d[4]);
				throw new InvalidArgumentException(sprintf($LNG['telemetry_invalid_range'], $d[5], $d[2] * $scale, $d[3] * $scale, $unit));
			}
			$out[$name] = is_int($d[1]) ? (int) $value : (float) $value;
		}
		if ($out['activity_min_days'] > $out['activity_days']) {
			throw new InvalidArgumentException($LNG['telemetry_invalid_days']);
		}
		if ($out['reserve_mb'] >= $out['budget_mb']) {
			throw new InvalidArgumentException($LNG['telemetry_invalid_reserve']);
		}
		if ($out['rate_metal_min'] > $out['rate_metal_max'] || $out['rate_crystal_min'] > $out['rate_crystal_max']) {
			throw new InvalidArgumentException($LNG['telemetry_invalid_rates']);
		}
		if ($out['repayment_hours'] >= $out['push_days'] * 24) {
			throw new InvalidArgumentException($LNG['telemetry_invalid_repayment']);
		}
		if ($out['daily_days'] < $out['activity_days']) {
			throw new InvalidArgumentException($LNG['telemetry_invalid_retention']);
		}
		if ($out['event_days'] < $out['automation_days']) {
			throw new InvalidArgumentException(sprintf($LNG['telemetry_invalid_event_retention'], $out['automation_days']));
		}
		return $out;
	}
}
