<?php

namespace Risistar\Tests\Integration;

use ShowRaportPage;
use Universe;

/**
 * Integration tests for opening combat reports from the battle hall.
 */
class BattleReportAccessTest extends GamePageTestCase
{
    private string $reportId = '';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowRaportPage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->reportId = md5('battle-report-access-test');
        self::$db->insert('INSERT INTO %%RW%% (rid, raport, time, attacker, defender) VALUES (:id, :report, :time, :attacker, :defender);', [
            ':id' => $this->reportId,
            ':report' => serialize(['time' => TIMESTAMP, 'result' => 'a', 'rounds' => [],
                'debris' => [901 => 0, 902 => 0], 'steal' => [901 => 0, 902 => 0, 903 => 0]]),
            ':time' => TIMESTAMP,
            ':attacker' => $this->users[0]['id'],
            ':defender' => $this->users[1]['id'],
        ]);
        $GLOBALS['_REQUEST'] = ['raport' => $this->reportId];
        $this->setUser($this->users[2]);
    }

    public function testReportNotInTheBattleHallIsNotShown(): void
    {
        $this->openFromBattleHall();

        $this->assertArrayNotHasKey('Raport', $this->assigned);
    }

    public function testReportInTheBattleHallIsShown(): void
    {
        self::$db->insert('INSERT INTO %%TOPKB%% (rid, units, result, time, universe) VALUES (:id, 100, :result, :time, :universe);',
            [':id' => $this->reportId, ':result' => 'a', ':time' => TIMESTAMP, ':universe' => Universe::current()]);

        $this->openFromBattleHall();

        $this->assertSame('a', $this->assigned['Raport']['result']);
    }

    public function testMissingReportIsNotShown(): void
    {
        $GLOBALS['_REQUEST']['raport'] = md5('missing-report');

        $this->openFromBattleHall();

        $this->assertArrayNotHasKey('Raport', $this->assigned);
    }

    private function openFromBattleHall(): void
    {
        $this->callPage($this->page(ShowRaportPage::class, ['setWindow']), 'battlehall');
    }
}
