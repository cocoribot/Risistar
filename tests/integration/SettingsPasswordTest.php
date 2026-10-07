<?php

namespace Risistar\Tests\Integration;

use PlayerUtil;
use ShowSettingsPage;

/**
 * Integration tests for changing the password on the settings page.
 */
class SettingsPasswordTest extends GamePageTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowSettingsPage.class.php';
    }

    public function testNewPasswordWithAccentsCanBeUsedToLogIn(): void
    {
        $GLOBALS['USER']['password'] = PlayerUtil::cryptPassword('ancien-mot-de-passe');
        $GLOBALS['_REQUEST'] = [
            'password' => 'ancien-mot-de-passe',
            'newpassword' => 'étoile-à-noël',
            'newpassword2' => 'étoile-à-noël',
            'timezone' => $GLOBALS['USER']['timezone'],
        ];

        $this->callPage($this->page(ShowSettingsPage::class), 'send');

        $saved = self::$db->selectSingle('SELECT password FROM %%USERS%% WHERE id = :id;', [':id' => $GLOBALS['USER']['id']], 'password');
        $this->assertTrue(password_verify('étoile-à-noël', $saved));
    }

    public function testCurrentPasswordWithAccentsIsAccepted(): void
    {
        $GLOBALS['USER']['password'] = PlayerUtil::cryptPassword('ancien-é');
        $GLOBALS['_REQUEST'] = [
            'password' => 'ancien-é',
            'newpassword' => 'nouveau',
            'newpassword2' => 'nouveau',
            'timezone' => $GLOBALS['USER']['timezone'],
        ];

        $this->callPage($this->page(ShowSettingsPage::class), 'send');

        $saved = self::$db->selectSingle('SELECT password FROM %%USERS%% WHERE id = :id;', [':id' => $GLOBALS['USER']['id']], 'password');
        $this->assertTrue(password_verify('nouveau', $saved));
    }
}
