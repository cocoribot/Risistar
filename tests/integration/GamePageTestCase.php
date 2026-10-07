<?php

namespace Risistar\Tests\Integration;

use Language;
use ReflectionProperty;
use RuntimeException;
use Theme;

/**
 * Base class for tests that call game page actions directly, inside a transaction that is rolled back.
 */
abstract class GamePageTestCase extends IntegrationTestCase
{
    protected array $users = [];
    protected array $assigned = [];
    protected ?string $displayed = null;
    private array $globals = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/AbstractGamePage.class.php';
    }

    protected function setUp(): void
    {
        $this->requireDatabase();
        foreach (['USER', 'LNG', 'THEME', 'PLANET', 'resource', 'reslist', '_REQUEST', '_POST', '_GET'] as $name) {
            $this->globals[$name] = $GLOBALS[$name] ?? null;
        }
        // vars.php is loaded inside a function by the bootstrap, so the resource entries are not global
        $GLOBALS['resource'] += [901 => 'metal', 902 => 'crystal', 903 => 'deuterium', 911 => 'energy', 921 => 'darkmatter'];
        $GLOBALS['reslist']['ressources'] = [901, 902, 903, 911, 921];
        $GLOBALS['reslist']['resstype'] = [1 => [901, 902, 903], 2 => [911], 3 => [921]];
        self::$db->beginTransaction();
        $this->users = self::$db->select('SELECT * FROM %%USERS%% WHERE authlevel = :level ORDER BY id ASC LIMIT 3;',
            [':level' => 0]);
        if (count($this->users) < 3) {
            $this->markTestSkipped('The test database needs 3 players.');
        }
        $this->setUser($this->users[0]);
        $GLOBALS['THEME'] = new Theme();
        $GLOBALS['_REQUEST'] = $GLOBALS['_POST'] = $GLOBALS['_GET'] = [];
    }

    protected function tearDown(): void
    {
        if (self::$db !== null && self::$db->getTransactionDepth() > 0) {
            self::$db->rollBackAll();
            (new ReflectionProperty(self::$db, 'requestRolledBack'))->setValue(self::$db, false);
        }
        foreach ($this->globals as $name => $value) {
            if ($value === null) {
                unset($GLOBALS[$name]);
            } else {
                $GLOBALS[$name] = $value;
            }
        }
    }

    protected function setUser(array $user): void
    {
        $GLOBALS['USER'] = $user;
        $GLOBALS['LNG'] = new Language($user['lang']);
        $GLOBALS['LNG']->includeData(['L18N', 'INGAME', 'TECH', 'CUSTOM']);
        $GLOBALS['PLANET'] = self::$db->selectSingle('SELECT * FROM %%PLANETS%% WHERE id = :id;',
            [':id' => $user['id_planet']]);
    }

    /**
     * Page without its constructor. display(), redirectTo() and printMessage() stop it
     * like the real ones, which exit.
     */
    protected function page(string $class, array $methods = [])
    {
        $page = $this->getMockBuilder($class)->disableOriginalConstructor()
            ->onlyMethods(array_merge(['assign', 'display', 'redirectTo', 'printMessage'], $methods))->getMock();
        $page->method('assign')->willReturnCallback(function ($data) {
            $this->assigned = array_merge($this->assigned, $data);
        });
        $page->method('display')->willReturnCallback(function ($file) {
            $this->displayed = $file;
            throw new RuntimeException('page-stopped');
        });
        $page->method('redirectTo')->willThrowException(new RuntimeException('page-stopped'));
        $page->method('printMessage')->willThrowException(new RuntimeException('page-stopped'));
        return $page;
    }

    protected function callPage($page, string $method): void
    {
        try {
            $page->{$method}();
        } catch (RuntimeException $error) {
            $this->assertSame('page-stopped', $error->getMessage());
        }
    }
}
