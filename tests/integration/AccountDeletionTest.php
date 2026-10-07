<?php

namespace Risistar\Tests\Integration;

use PlayerUtil;
use ShowSettingsPage;

/**
 * Integration tests for scheduling the account deletion on the settings page.
 */
class AccountDeletionTest extends GamePageTestCase
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

    public function testDeletionNeedsTheCurrentPassword(): void
    {
        foreach ([0, 1] as $vacation) {
            foreach (['', 'wrong-password'] as $password) {
                $this->setDeletion($vacation, 0);

                $this->sendSettings(1, $password);

                $this->assertSame(0, $this->savedDeletion());
            }
        }
    }

    public function testCorrectPasswordSchedulesTheDeletion(): void
    {
        foreach ([0, 1] as $vacation) {
            $this->setDeletion($vacation, 0);

            $this->sendSettings(1, self::PASSWORD);

            $this->assertSame(TIMESTAMP, $this->savedDeletion());
        }
    }

    public function testCancellingTheDeletionDoesNotNeedThePassword(): void
    {
        foreach ([0, 1] as $vacation) {
            $this->setDeletion($vacation, TIMESTAMP);

            $this->sendSettings(0, '');

            $this->assertSame(0, $this->savedDeletion());
        }
    }

    public function testOtherSettingsDoNotNeedThePassword(): void
    {
        $this->setDeletion(0, 0);

        $this->sendSettings(0, '');

        $this->assertSame(2, (int) $this->savedUser()['planet_sort']);
    }

    private function setDeletion(int $vacation, int $scheduled): void
    {
        $GLOBALS['USER']['urlaubs_modus'] = $vacation;
        $GLOBALS['USER']['db_deaktjava'] = $scheduled;
        self::$db->update('UPDATE %%USERS%% SET db_deaktjava = :scheduled WHERE id = :id;',
            [':scheduled' => $scheduled, ':id' => $GLOBALS['USER']['id']]);
    }

    private function sendSettings(int $delete, string $password): void
    {
        $GLOBALS['_REQUEST'] = [
            'delete' => $delete,
            'password' => $password,
            'planetSort' => 2,
            'timezone' => $GLOBALS['USER']['timezone'],
        ];
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
