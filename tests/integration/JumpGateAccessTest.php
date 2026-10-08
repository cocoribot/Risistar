<?php

namespace Risistar\Tests\Integration;

use RuntimeException;
use ShowInformationPage;

/**
 * Integration tests for sending ships through a jump gate.
 */
class JumpGateAccessTest extends GamePageTestCase
{
    private array $target = [];
    private array $response = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowInformationPage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $target = self::$db->selectSingle('SELECT * FROM %%PLANETS%% WHERE id_owner = :owner AND id != :id AND destruyed = 0 LIMIT 1;',
            [':owner' => $GLOBALS['USER']['id'], ':id' => $GLOBALS['PLANET']['id']]);
        if (empty($target)) {
            $this->markTestSkipped('The test database needs a player with two planets.');
        }
        $this->target = $target;
        self::$db->update('UPDATE %%PLANETS%% SET planet_type = :type, sprungtor = 1, last_jump_time = 0, small_ship_cargo = 0 WHERE id = :id;',
            [':type' => '3', ':id' => $this->target['id']]);
        self::$db->update('UPDATE %%PLANETS%% SET sprungtor = 0, last_jump_time = 0, small_ship_cargo = 10 WHERE id = :id;',
            [':id' => $GLOBALS['PLANET']['id']]);
        $GLOBALS['PLANET']['sprungtor'] = 0;
        $GLOBALS['PLANET']['last_jump_time'] = 0;
        $GLOBALS['PLANET']['small_ship_cargo'] = 10;
        $GLOBALS['_REQUEST'] = ['jmpto' => $this->target['id'], 'ship' => [202 => 5]];
    }

    public function testPlanetWithoutAGateCannotSendShips(): void
    {
        $this->assertDeniedWithoutChanges();
    }

    public function testMoonWithoutAGateCannotSendShips(): void
    {
        $GLOBALS['PLANET']['planet_type'] = 3;
        $this->assertDeniedWithoutChanges();
    }

    public function testMoonGateSendsShips(): void
    {
        $this->readyOrigin();
        $this->send();
        $this->assertFalse($this->response['error']);
        $this->assertEquals(5, $this->planet($GLOBALS['PLANET']['id'])['small_ship_cargo']);
        $this->assertEquals(5, $this->planet($this->target['id'])['small_ship_cargo']);
        $this->assertEquals(TIMESTAMP, $this->planet($this->target['id'])['last_jump_time']);
    }

    public function testMissingTargetCannotSendShips(): void
    {
        $this->readyOrigin();
        $GLOBALS['_REQUEST']['jmpto'] = 999999;
        $this->assertDeniedWithoutChanges();
    }

    private function readyOrigin(): void
    {
        $GLOBALS['PLANET']['planet_type'] = 3;
        $GLOBALS['PLANET']['sprungtor'] = 1;
        self::$db->update('UPDATE %%PLANETS%% SET planet_type = :type, sprungtor = 1 WHERE id = :id;',
            [':type' => '3', ':id' => $GLOBALS['PLANET']['id']]);
    }

    private function assertDeniedWithoutChanges(): void
    {
        $before = self::$db->select('SELECT * FROM %%PLANETS%% ORDER BY id;');
        $this->send();
        $this->assertTrue($this->response['error']);
        $this->assertSame($before, self::$db->select('SELECT * FROM %%PLANETS%% ORDER BY id;'));
    }

    private function planet(int $id): array
    {
        return self::$db->selectSingle('SELECT * FROM %%PLANETS%% WHERE id = :id;', [':id' => $id]);
    }

    private function send(): void
    {
        $page = $this->page(ShowInformationPage::class, ['sendJSON']);
        $page->method('sendJSON')->willReturnCallback(function ($data) {
            $this->response = $data;
            throw new RuntimeException('page-stopped');
        });
        $this->callPage($page, 'sendFleet');
    }
}
