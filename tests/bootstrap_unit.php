<?php

/**
 * PHPUnit bootstrap for unit tests (no database).
 */

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/');
}
if (!defined('MODE')) {
    define('MODE', 'TEST');
}
if (!defined('TIMESTAMP')) {
    define('TIMESTAMP', time());
}

require ROOT_PATH . 'vendor/autoload.php';
require_once __DIR__ . '/unit/UnitTestCase.php';
