<?php

require_once ROOT_PATH . 'includes/classes/cronjob/CronjobTask.interface.php';

class TelemetryCronjob implements CronjobTask
{
    public function run()
    {
        if (!TelemetryConnection::masterEnabled()) {
            return;
        }
        $store = new TelemetryStore(TelemetryConnection::open());
        // Retention uses the longest configured history; the quota is deployment-wide.
        $settings = TelemetrySettings::defaults();
        $all = array_map(static fn($u) => TelemetrySettings::get((int)$u), Universe::availableUniverses());
        foreach (['event_days','daily_days','closed_days'] as $key) {
            $settings[$key] = max(array_column($all, $key));
        }
        $settings['budget_mb'] = min(array_column($all, 'budget_mb'));
        $settings['reserve_mb'] = max(array_column($all, 'reserve_mb'));
        $store->maintenance($settings, time());
    }
}
