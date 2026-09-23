<?php

namespace Risistar\Tests\Unit;

use TelemetrySettings;

class TelemetrySettingsTest extends UnitTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::bootConstants();
        require_once self::rootPath() . 'includes/classes/TelemetrySettings.class.php';
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['LNG']);
    }

    public function testEverySettingHasAFrenchAndEnglishLabel(): void
    {
        foreach (['fr', 'en'] as $language) {
            $this->loadLanguage($language);

            foreach (TelemetrySettings::definitions() as $name => $definition) {
                $this->assertNotSame($name, $definition['label'], "Missing {$language} label for {$name}");
            }
        }
    }

    public function testDefaultsAreValid(): void
    {
        $this->loadLanguage('en');

        $this->assertSame(TelemetrySettings::defaults(), TelemetrySettings::validate(TelemetrySettings::defaults()));
    }

    public function testMinimumRateAboveMaximumRateIsRejected(): void
    {
        $this->loadLanguage('en');
        $settings = array_replace(TelemetrySettings::defaults(), ['rate_metal_min' => 5]);

        $this->expectExceptionMessage($GLOBALS['LNG']['telemetry_invalid_rates']);

        TelemetrySettings::validate($settings);
    }

    public function testWholeNumberSettingRejectsDecimals(): void
    {
        $this->loadLanguage('en');
        $settings = array_replace(TelemetrySettings::defaults(), ['activity_days' => 7.5]);

        $this->expectException(\InvalidArgumentException::class);

        TelemetrySettings::validate($settings);
    }

    public function testDeliveriesAreKeptLongEnoughToSeeTheRepayment(): void
    {
        $defaults = TelemetrySettings::defaults();

        $this->assertSame(21, TelemetrySettings::deliveryDays($defaults));
        $this->assertSame(60, TelemetrySettings::deliveryDays(array_replace($defaults, ['push_days' => 20])));
    }

    public function testTelemetryTablesAreTheSameInEverySchemaFile(): void
    {
        $tables = [];
        foreach (['install/install.sql', 'install/migrations/migration_8.sql', 'install/telemetry.sql'] as $file) {
            preg_match_all('/CREATE TABLE IF NOT EXISTS `?(?:%PREFIX%)?telemetry_.*?;/s', file_get_contents(self::rootPath() . $file), $matches);
            $tables[$file] = array_map(
                static fn($sql) => preg_replace('/\s+/', ' ', str_replace(['`', '%PREFIX%'], '', $sql)),
                $matches[0]
            );
        }

        $this->assertCount(6, $tables['install/telemetry.sql']);
        $this->assertSame($tables['install/telemetry.sql'], $tables['install/install.sql']);
        $this->assertSame($tables['install/telemetry.sql'], $tables['install/migrations/migration_8.sql']);
    }

    private function loadLanguage(string $language): void
    {
        $LNG = [];
        require self::rootPath() . 'language/' . $language . '/ADMIN.php';
        $GLOBALS['LNG'] = $LNG;
    }
}
