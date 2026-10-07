<?php

namespace Risistar\Tests\Integration;

use ShowSettingsPage;

/**
 * Integration tests for the planet sort options on the settings page.
 */
class SettingsPlanetSortTest extends GamePageTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowSettingsPage.class.php';
    }

    public function testInvalidSortOptionsAreNotSaved(): void
    {
        foreach ([-1, 3, 999] as $value) {
            $this->sendSettings($value, $value);

            $saved = $this->savedUser();
            $this->assertSame(0, (int) $saved['planet_sort']);
            $this->assertSame(0, (int) $saved['planet_sort_order']);
            $this->assertNotEmpty(getPlanets($saved));
        }
    }

    public function testSupportedSortOptionsAreSaved(): void
    {
        foreach ([0, 1, 2] as $sort) {
            foreach ([0, 1] as $order) {
                $this->sendSettings($sort, $order);

                $saved = $this->savedUser();
                $this->assertSame($sort, (int) $saved['planet_sort']);
                $this->assertSame($order, (int) $saved['planet_sort_order']);
            }
        }
    }

    public function testInvalidSavedSortStillLoadsPlanets(): void
    {
        $user = $this->savedUser();
        $user['planet_sort'] = 0;
        $user['planet_sort_order'] = 0;
        $expected = getPlanets($user);

        $user['planet_sort'] = 3;
        $user['planet_sort_order'] = 3;

        $this->assertSame($expected, getPlanets($user));
    }

    private function sendSettings(int $sort, int $order): void
    {
        $GLOBALS['_REQUEST'] = [
            'planetSort' => $sort,
            'planetOrder' => $order,
            'timezone' => $GLOBALS['USER']['timezone'],
        ];
        $this->callPage($this->page(ShowSettingsPage::class), 'send');
    }

    private function savedUser(): array
    {
        return self::$db->selectSingle('SELECT * FROM %%USERS%% WHERE id = :id;', [':id' => $GLOBALS['USER']['id']]);
    }
}
