<?php

require_once ROOT_PATH . 'includes/classes/cronjob/CronjobTask.interface.php';
require_once ROOT_PATH . 'includes/classes/TelemetryDetectors.class.php';
require_once ROOT_PATH . 'includes/classes/TelemetryReview.class.php';

class TelemetryCronjob implements CronjobTask
{
	public function run()
	{
		$health = TelemetryStore::health();
		if (($health['retry_after'] ?? 0) > time()) {
			return;
		}
		try {
			$store = new TelemetryStore(TelemetryConnection::open());
			$all = array_map(static fn($u) => TelemetrySettings::get((int)$u), Universe::availableUniverses());
			$health = $store->maintenance(
				max(array_map([TelemetrySettings::class, 'deliveryDays'], $all)),
				max(array_column($all, 'network_hours')),
				time()
			);
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
			// Player writes go on; the admin page shows the failure until a pass works again.
			TelemetryStore::health(['analysis_failed_at' => time()]);
			throw $e;
		}
	}
}
