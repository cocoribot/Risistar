<?php

namespace Risistar\Tests\Integration;

use PlayerUtil;
use ShowSettingsPage;

/**
 * Integration tests for the settings page.
 */
class SettingsPageTest extends GamePageTestCase
{
    private const PASSWORD = 'mot-de-passe-é';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowSettingsPage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['USER']['password'] = PlayerUtil::cryptPassword(self::PASSWORD);
    }

    public function testInvalidSortOptionsAreSavedAsZero(): void
    {
        foreach ([-1, 3, 999] as $value) {
            $this->sendSettings(['planetSort' => $value, 'planetOrder' => $value]);

            $saved = $this->savedUser();
            $this->assertSame(0, (int) $saved['planet_sort'], "planetSort $value");
            $this->assertSame(0, (int) $saved['planet_sort_order'], "planetOrder $value");
        }
    }

    public function testSupportedSortOptionsAreSaved(): void
    {
        foreach ([0, 1, 2] as $sort) {
            foreach ([0, 1] as $order) {
                $this->sendSettings(['planetSort' => $sort, 'planetOrder' => $order]);

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

    public function testDeletionNeedsTheCurrentPassword(): void
    {
        foreach ([0, 1] as $vacation) {
            foreach (['', 'wrong-password'] as $password) {
                $this->setDeletion($vacation, 0);

                $this->sendSettings(['delete' => 1, 'password' => $password]);

                $this->assertSame(0, $this->savedDeletion(), "vacation $vacation, password '$password'");
            }
        }
    }

    public function testCorrectPasswordSchedulesTheDeletion(): void
    {
        foreach ([0, 1] as $vacation) {
            $this->setDeletion($vacation, 0);

            $this->sendSettings(['delete' => 1, 'password' => self::PASSWORD]);

            $this->assertSame(TIMESTAMP, $this->savedDeletion(), "vacation $vacation");
        }
    }

    public function testCancellingTheDeletionDoesNotNeedThePassword(): void
    {
        foreach ([0, 1] as $vacation) {
            $this->setDeletion($vacation, TIMESTAMP);

            $this->sendSettings(['delete' => 0]);

            $this->assertSame(0, $this->savedDeletion(), "vacation $vacation");
        }
    }

    private function setDeletion(int $vacation, int $scheduled): void
    {
        $GLOBALS['USER']['urlaubs_modus'] = $vacation;
        $GLOBALS['USER']['db_deaktjava'] = $scheduled;
        self::$db->update('UPDATE %%USERS%% SET db_deaktjava = :scheduled WHERE id = :id;',
            [':scheduled' => $scheduled, ':id' => $GLOBALS['USER']['id']]);
    }

    private function sendSettings(array $request): void
    {
        $GLOBALS['_REQUEST'] = $request + ['timezone' => $GLOBALS['USER']['timezone']];
        $this->callPage($this->page(ShowSettingsPage::class), 'send');
    }

    private function savedDeletion(): int
    {
        return (int) $this->savedUser()['db_deaktjava'];
    }

    private function savedUser(): array
    {
        return self::$db->selectSingle('SELECT * FROM %%USERS%% WHERE id = :id;', [':id' => $GLOBALS['USER']['id']]);
    }
}
