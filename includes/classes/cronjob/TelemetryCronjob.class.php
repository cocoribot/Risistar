<?php

require_once ROOT_PATH . 'includes/classes/cronjob/CronjobTask.interface.php';
require_once ROOT_PATH . 'includes/classes/TelemetryDetectors.class.php';
require_once ROOT_PATH . 'includes/classes/TelemetryReview.class.php';

class TelemetryCronjob implements CronjobTask
{
	public function run()
	{
		if (!TelemetryConnection::masterEnabled()) {
			return;
		}
		$health = TelemetryStore::health();
		if (($health['retry_after'] ?? 0) > time()) {
			return;
		}
		try {
			$store = new TelemetryStore(TelemetryConnection::open());
			// Retention uses the longest configured history; the quota is deployment-wide.
			$all = array_map(static fn($u) => TelemetrySettings::get((int)$u), Universe::availableUniverses());
			$settings = TelemetrySettings::storage($all);
			$health = $store->maintenance($settings, time());
			if (TelemetryConnection::shared() && !empty($health['suspended'])) {
				return;
			}
			$review = new TelemetryReview($store);
			foreach ($all as $index => $s) {
				if (!$s['enabled']) {
					continue;
				}
				$universe = (int)Universe::availableUniverses()[$index];
				$previous = $health['analysis_' . $universe] ?? [];
				$continue = !empty($previous['incomplete']);
				$review->evaluate($universe, $s, time(),
					$continue ? (int)$previous['cursor'] : 0,
					$continue ? (int)$previous['pair_a'] : 0,
					$continue ? (int)$previous['pair_b'] : 0);
			}
		} catch (Throwable $e) {
			TelemetryStore::health(['failure' => 'analysis_failed', 'retry_after' => time() + 60]);
			throw $e;
		}
	}
}
