CREATE TABLE IF NOT EXISTS telemetry_daily (
    universe INT UNSIGNED NOT NULL,
    actor INT UNSIGNED NOT NULL,
    day DATE NOT NULL,
    windows JSON NOT NULL,
    PRIMARY KEY (universe,actor,day),
    KEY cleanup (day)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS telemetry_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_key VARCHAR(48) CHARACTER SET ascii NOT NULL,
    request_id CHAR(32) CHARACTER SET ascii NOT NULL,
    universe INT UNSIGNED NOT NULL,
    actor INT UNSIGNED NOT NULL,
    target INT UNSIGNED NOT NULL DEFAULT 0,
    pair_a INT UNSIGNED NOT NULL,
    pair_b INT UNSIGNED NOT NULL,
    at BIGINT UNSIGNED NOT NULL,
    kind VARCHAR(40) CHARACTER SET ascii NOT NULL,
    result VARCHAR(12) CHARACTER SET ascii NOT NULL,
    fleet_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
    interactive TINYINT NOT NULL DEFAULT 0,
    ip VARCHAR(45) CHARACTER SET ascii NULL,
    data JSON NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY event_key (event_key),
    KEY actor_time (universe,actor,at,id),
    KEY pair_time (universe,pair_a,pair_b,at,id),
    KEY kind_time (universe,kind,at,id),
    KEY delivery_pairs (universe,kind,pair_a,pair_b,at),
    KEY cleanup (at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS telemetry_warnings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    universe INT UNSIGNED NOT NULL,
    actor INT UNSIGNED NOT NULL,
    other INT UNSIGNED NOT NULL DEFAULT 0,
    kind VARCHAR(40) CHARACTER SET ascii NOT NULL,
    strength VARCHAR(20) NOT NULL,
    explanation TEXT NOT NULL,
    first_seen BIGINT UNSIGNED NOT NULL,
    last_seen BIGINT UNSIGNED NOT NULL,
    observation_start BIGINT UNSIGNED NOT NULL,
    observation_end BIGINT UNSIGNED NOT NULL,
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

CREATE TABLE IF NOT EXISTS telemetry_audit (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    universe INT UNSIGNED NOT NULL,
    admin INT UNSIGNED NOT NULL,
    at BIGINT UNSIGNED NOT NULL,
    warning_id BIGINT UNSIGNED NULL,
    action VARCHAR(32) NOT NULL,
    data JSON NOT NULL,
    PRIMARY KEY (id),
    KEY warning_history (universe,warning_id,at),
    KEY settings_history (universe,action,at),
    KEY cleanup (at),
    FOREIGN KEY (warning_id) REFERENCES telemetry_warnings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
