<?php

return static function(Database $db): void {
    require_once ROOT_PATH . 'includes/classes/TelemetryConnection.class.php';
    require_once ROOT_PATH . 'includes/classes/TelemetrySchema.class.php';
    // DDL may auto-commit. Each step is individually retryable.
    $column = $db->nativeQuery("SHOW COLUMNS FROM %%CONFIG%% LIKE 'telemetry_settings'");
    if (!$column) {
        $db->nativeQuery("ALTER TABLE %%CONFIG%% ADD telemetry_settings TEXT NULL");
    }
    $db->nativeQuery("UPDATE %%CONFIG%% SET telemetry_settings='{}' WHERE telemetry_settings IS NULL");
    TelemetrySchema::verify();
    $db->nativeQuery("INSERT INTO %%CRONJOBS%% (name,isActive,min,hours,dom,month,dow,class,nextTime)
        SELECT 'Activité joueurs',1,'*/5','*','*','*','*','TelemetryCronjob',0
        WHERE NOT EXISTS (SELECT 1 FROM %%CRONJOBS%% WHERE class='TelemetryCronjob')");
};
