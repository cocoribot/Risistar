<?php

namespace Risistar\Tests\Integration;

use ReflectionMethod;
use ReflectionProperty;
use ShowAlliancePage;

/**
 * Base class for tests of the alliance admin actions. Players 0 and 1 are in the alliance, player 0 is the founder.
 */
abstract class AllianceActionTestCase extends GamePageTestCase
{
    protected int $allianceId = 0;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowAlliancePage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->allianceId = $this->createAlliance($this->users[0]['id']);
        foreach ([0, 1] as $index) {
            $this->setAlliance($index, $this->allianceId);
        }
        $this->setAlliance(2, 0);
        $this->setUser($this->users[0]);
    }

    protected function createAlliance(int $owner): int
    {
        self::$db->insert('INSERT INTO %%ALLIANCE%% (ally_name, ally_tag, ally_owner, ally_members, ally_register_time, ally_universe) VALUES (:name, :tag, :owner, 2, :time, 1);',
            [':name' => 'Test alliance ' . $owner, ':tag' => 'T' . $owner, ':owner' => $owner, ':time' => TIMESTAMP]);
        return (int) self::$db->lastInsertId();
    }

    protected function createRank(int $alliance, string $right): int
    {
        self::$db->insert('INSERT INTO %%ALLIANCE_RANK%% (allianceID, rankName, ' . $right . ') VALUES (:alliance, :name, 1);',
            [':alliance' => $alliance, ':name' => 'Test rank']);
        return (int) self::$db->lastInsertId();
    }

    protected function setRank(int $index, int $rank): void
    {
        self::$db->update('UPDATE %%USERS%% SET ally_rank_id = :rank WHERE id = :id;', [':rank' => $rank, ':id' => $this->users[$index]['id']]);
        $this->users[$index]['ally_rank_id'] = $rank;
    }

    protected function runAdminAction(): void
    {
        $page = $this->page(ShowAlliancePage::class);
        (new ReflectionProperty(ShowAlliancePage::class, 'hasAlliance'))->setValue($page, true);
        (new ReflectionMethod(ShowAlliancePage::class, 'setAllianceData'))->invoke($page, $this->allianceId);
        $this->callPage($page, 'admin');
    }

    protected function alliance(): array
    {
        return self::$db->selectSingle('SELECT * FROM %%ALLIANCE%% WHERE id = :id;', [':id' => $this->allianceId]);
    }

    private function setAlliance(int $index, int $alliance): void
    {
        self::$db->update('UPDATE %%USERS%% SET ally_id = :alliance, ally_rank_id = 0 WHERE id = :id;',
            [':alliance' => $alliance, ':id' => $this->users[$index]['id']]);
        $this->users[$index]['ally_id'] = $alliance;
        $this->users[$index]['ally_rank_id'] = 0;
    }
}
