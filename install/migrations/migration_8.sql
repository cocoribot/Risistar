ALTER TABLE `%PREFIX%config` ADD telemetry_settings VARCHAR(4096) NOT NULL DEFAULT '{}';

CREATE TABLE IF NOT EXISTS `%PREFIX%telemetry_daily` (
    universe INT UNSIGNED NOT NULL,
    actor INT UNSIGNED NOT NULL,
    day DATE NOT NULL,
    windows JSON NOT NULL,
    actions JSON NOT NULL,
    gaps JSON NOT NULL,
    hours JSON NOT NULL,
    last_at INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (universe,actor,day),
    KEY cleanup (day)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `%PREFIX%telemetry_events` (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    universe INT UNSIGNED NOT NULL,
    actor INT UNSIGNED NOT NULL,
    target INT UNSIGNED NOT NULL DEFAULT 0,
    pair_a INT UNSIGNED NOT NULL,
    pair_b INT UNSIGNED NOT NULL,
    at INT UNSIGNED NOT NULL,
    kind VARCHAR(40) CHARACTER SET ascii NOT NULL,
    data JSON NOT NULL,
    PRIMARY KEY (id),
    KEY actor_time (universe,actor,at,id),
    KEY delivery_pairs (universe,kind,pair_a,pair_b,at),
    KEY cleanup (at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `%PREFIX%telemetry_network` (
    universe INT UNSIGNED NOT NULL,
    actor INT UNSIGNED NOT NULL,
    day DATE NOT NULL,
    ip VARCHAR(45) CHARACTER SET ascii NOT NULL,
    client VARCHAR(64) NOT NULL,
    requests INT UNSIGNED NOT NULL,
    first_at INT UNSIGNED NOT NULL,
    last_at INT UNSIGNED NOT NULL,
    PRIMARY KEY (universe,actor,day,ip,client),
    KEY cleanup (last_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `%PREFIX%telemetry_pairs` (
    universe INT UNSIGNED NOT NULL,
    pair_a INT UNSIGNED NOT NULL,
    pair_b INT UNSIGNED NOT NULL,
    a_metal DOUBLE NOT NULL DEFAULT 0,
    a_crystal DOUBLE NOT NULL DEFAULT 0,
    a_deuterium DOUBLE NOT NULL DEFAULT 0,
    a_deliveries INT UNSIGNED NOT NULL DEFAULT 0,
    b_metal DOUBLE NOT NULL DEFAULT 0,
    b_crystal DOUBLE NOT NULL DEFAULT 0,
    b_deuterium DOUBLE NOT NULL DEFAULT 0,
    b_deliveries INT UNSIGNED NOT NULL DEFAULT 0,
    first_at INT UNSIGNED NOT NULL,
    last_at INT UNSIGNED NOT NULL,
    PRIMARY KEY (universe,pair_a,pair_b)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `%PREFIX%telemetry_warnings` (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    universe INT UNSIGNED NOT NULL,
    actor INT UNSIGNED NOT NULL,
    other INT UNSIGNED NOT NULL DEFAULT 0,
    kind VARCHAR(40) CHARACTER SET ascii NOT NULL,
    strength VARCHAR(20) NOT NULL,
    first_seen INT UNSIGNED NOT NULL,
    last_seen INT UNSIGNED NOT NULL,
    observation_start INT UNSIGNED NOT NULL,
    observation_end INT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'open',
    settings JSON NOT NULL,
    evidence JSON NOT NULL,
    latest_settings JSON NULL,
    latest_evidence JSON NULL,
    PRIMARY KEY (id),
    UNIQUE KEY one_case (universe,actor,other,kind),
    KEY queue (universe,status,id),
    KEY cleanup (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `%PREFIX%telemetry_audit` (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    universe INT UNSIGNED NOT NULL,
    admin INT UNSIGNED NOT NULL,
    at INT UNSIGNED NOT NULL,
    warning_id BIGINT UNSIGNED NULL,
    action VARCHAR(32) NOT NULL,
    data JSON NOT NULL,
    PRIMARY KEY (id),
    KEY warning_history (universe,warning_id,at),
    KEY settings_history (universe,action,at),
    KEY cleanup (at),
    FOREIGN KEY (warning_id) REFERENCES `%PREFIX%telemetry_warnings`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `%PREFIX%cronjobs` (name,isActive,min,hours,dom,month,dow,class,nextTime)
SELECT 'Player activity',1,'*/5','*','*','*','*','TelemetryCronjob',0 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `%PREFIX%cronjobs` WHERE class='TelemetryCronjob');
