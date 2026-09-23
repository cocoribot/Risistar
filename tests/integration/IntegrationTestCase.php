<?php

namespace Risistar\Tests\Integration;

use Database;
use PHPUnit\Framework\TestCase;

/**
 * Base class for PHPUnit tests that use the isolated database (2moons_test).
 */
abstract class IntegrationTestCase extends TestCase
{
    protected static ?Database $db = null;
    protected static ?string $bootstrapError = null;

    protected static function rootPath(): string
    {
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', dirname(__DIR__, 2) . '/');
        }

        return ROOT_PATH;
    }

    public static function setUpBeforeClass(): void
    {
        if (!defined('MODE')) {
            define('MODE', 'TEST');
        }
        if (!defined('DATABASE_VERSION')) {
            define('DATABASE_VERSION', 'OLD');
        }

        try {
            require_once self::rootPath() . 'includes/common.php';
            require_once self::rootPath() . 'includes/vars.php';
            self::ensureGameGlobals();

            self::$db = Database::get();
        } catch (\Throwable $e) {
            self::$bootstrapError = $e->getMessage();
            self::$db = null;
        }
    }

    protected static function ensureGameGlobals(): void
    {
        global $resource;

        if (!isset($resource[204])) {
            $vars = \Cache::get()->getData('vars');
            foreach ($vars as $key => $value) {
                $GLOBALS[$key] = $value;
            }
        }
    }

    protected function requireDatabase(): void
    {
        if (self::$db === null) {
            $this->markTestSkipped('Database not available: ' . (self::$bootstrapError ?? 'unknown'));
        }
    }
}
