<?php

final class TelemetrySettings
{
	// Limits for the whole server. Detection thresholds stay editable in the admin settings.
	public const DAILY_DAYS = 90;
	public const CLOSED_DAYS = 90;
	public const BUDGET_MB = 1800;
	public const RESERVE_MB = 400;
	public const CLEANUP_ROWS = 5000;
	public const ANALYSIS_ACCOUNTS = 10;
	public const ANALYSIS_EVENTS = 10000;
	public const ACTIVITY_GAP_HOURS = 3;

	/** [group, default, minimum, maximum, unit] */
	private const THRESHOLDS = [
		'network_enabled' => ['collection', 1, 0, 1, '0/1'],
		'network_hours' => ['collection', 72, 1, 2160, 'hours'],
		'activity_days' => ['activity', 7, 2, 90, 'days'],
		'activity_hours' => ['activity', 20, 1, 24, 'hours_day'],
		'activity_min_days' => ['activity', 4, 1, 90, 'days'],
		'automation_days' => ['automation', 7, 1, 30, 'days'],
		'timing_min' => ['automation', 30, 8, 5000, 'actions'],
		'refresh_hours' => ['automation', 3, 1, 24, 'hours'],
		'refresh_per_hour' => ['automation', 6, 1, 3600, 'loads_hour'],
		'refresh_days' => ['automation', 3, 1, 30, 'days'],
		'timing_tolerance' => ['automation', 0.1, 0.01, 0.5, 'fraction'],
		'timing_share' => ['automation', 0.8, 0.5, 1, 'fraction'],
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
	];

	public static function definitions(): array
	{
		global $LNG;
		$definitions = [];
		foreach (self::THRESHOLDS as $name => [$group, $default, $min, $max, $unit]) {
			$definitions[$name] = [
				'group' => $group,
				'default' => $default,
				'min' => $min,
				'max' => $max,
				'unit' => $unit,
				'label' => $LNG['telemetry_setting_' . $name] ?? $name,
				'help' => $LNG['telemetry_setting_' . $name . '_help'] ?? '',
			];
		}
		return $definitions;
	}

	public static function scale(array $definition): int
	{
		return $definition['unit'] === 'fraction' ? 100 : 1;
	}

	public static function unitLabel(array $definition): string
	{
		global $LNG;
		if ($definition['unit'] === 'fraction') {
			return '%';
		}
		return $LNG['telemetry_unit_' . $definition['unit']] ?? $definition['unit'];
	}

	/**
	 * A delivery can pay back what was sent up to push_days before it. Keeping three periods
	 * lets the analysis see every payment that counts for the deliveries it checks.
	 */
	public static function deliveryDays(array $settings): int
	{
		return 3 * (int) $settings['push_days'];
	}

	public static function defaults(): array
	{
		return array_map(static fn($definition) => $definition['default'], self::definitions());
	}

	public static function get(int $universe): array
	{
		$config = Config::get($universe);
		$saved = isset($config->telemetry_settings) ? json_decode($config->telemetry_settings, true) : [];
		if (!is_array($saved)) {
			$saved = [];
		}
		$modules = explode(';', $config->moduls);
		$settings = array_replace(self::defaults(), array_intersect_key($saved, self::defaults()));
		return ['enabled' => (int) ($modules[MODULE_TELEMETRY] ?? 0)] + $settings;
	}

	public static function validate(array $input): array
	{
		global $LNG;
		$settings = [];
		foreach (self::definitions() as $name => $definition) {
			$value = $input[$name] ?? null;
			if (!self::inRange($value, $definition)) {
				$scale = self::scale($definition);
				throw new InvalidArgumentException(sprintf(
					$LNG['telemetry_invalid_range'],
					$definition['label'],
					$definition['min'] * $scale,
					$definition['max'] * $scale,
					self::unitLabel($definition)
				));
			}
			$settings[$name] = is_int($definition['default']) ? (int) $value : (float) $value;
		}
		if ($settings['activity_min_days'] > $settings['activity_days'] || $settings['refresh_days'] > $settings['automation_days']) {
			throw new InvalidArgumentException($LNG['telemetry_invalid_days']);
		}
		if ($settings['rate_metal_min'] > $settings['rate_metal_max'] || $settings['rate_crystal_min'] > $settings['rate_crystal_max']) {
			throw new InvalidArgumentException($LNG['telemetry_invalid_rates']);
		}
		if ($settings['repayment_hours'] >= $settings['push_days'] * 24) {
			throw new InvalidArgumentException($LNG['telemetry_invalid_repayment']);
		}
		return $settings;
	}

	private static function inRange($value, array $definition): bool
	{
		if (!is_scalar($value) || !is_numeric($value) || !is_finite((float) $value)) {
			return false;
		}
		if ($value < $definition['min'] || $value > $definition['max']) {
			return false;
		}
		return !is_int($definition['default']) || (float) $value == (int) $value;
	}
}
