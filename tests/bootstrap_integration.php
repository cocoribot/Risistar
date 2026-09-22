<?php

/**
 * PHPUnit bootstrap for integration tests (isolated DB: 2moons_test).
 * Does not modify includes/config.php — the browser keeps using 2moons.
 */

define('DATABASE_CONFIG_FILE', dirname(__DIR__) . '/includes/config.test.php');

require dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/integration/IntegrationTestCase.php';
