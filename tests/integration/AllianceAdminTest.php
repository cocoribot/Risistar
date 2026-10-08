<?php

namespace Risistar\Tests\Integration;

use ReflectionMethod;
use ReflectionProperty;
use ShowAlliancePage;
use Universe;

/**
 * Integration tests for the alliance admin actions and applications.
 * Players 0 and 1 are in the alliance, player 0 is the founder. Player 2 is in no alliance.
 */
class AllianceAdminTest extends GamePageTestCase
{
    private array $users = [];
    private int $allianceId = 0;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowAlliancePage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = $this->players(3);
        $this->allianceId = $this->createAlliance($this->users[0]['id']);
        $this->setAlliance(0, $this->allianceId);
        $this->setAlliance(1, $this->allianceId);
        $this->setAlliance(2, 0);
        $this->setUser($this->users[0]);
    }

    public function testEmptyActionOpensTheOverview(): void
    {
        $GLOBALS['_REQUEST'] = ['action' => ''];

        $this->runAdminAction();

        $this->assertSame('page.alliance.admin.overview.tpl', $this->displayed);
    }

    public function testPlayerWithoutAllianceCannotOpenTheAdminPages(): void
    {
        $this->setUser($this->users[2]);
        $GLOBALS['_REQUEST'] = ['action' => 'overview'];

        $this->callPage($this->page(ShowAlliancePage::class), 'admin');

        $this->assertNull($this->displayed);
    }

    public function testMemberCannotEditTheOverview(): void
    {
        $before = $this->alliance();
        $this->setUser($this->users[1]);

        $this->editOverview();

        $this->assertSame($before, $this->alliance());
    }

    public function testFounderCanEditTheOverview(): void
    {
        $this->editOverview();

        $this->assertSame('New description', $this->alliance()['ally_description']);
    }

    public function testMemberWithTheAdminRightCanEditTheOverview(): void
    {
        $this->setRank(1, $this->createRank($this->allianceId, 'ADMIN'));
        $this->setUser($this->users[1]);

        $this->editOverview();

        $this->assertSame('New description', $this->alliance()['ally_description']);
    }

    public function testFounderCannotTransferToAPlayerOutsideTheAlliance(): void
    {
        $this->assertTransferRefused($this->users[2]['id']);
    }

    public function testFounderCannotTransferToAMemberWithoutTheTransferRank(): void
    {
        $this->assertTransferRefused($this->users[1]['id']);
    }

    public function testRankOfAnotherAllianceDoesNotAllowTheTransfer(): void
    {
        $this->setRank(1, $this->createRank($this->createAlliance($this->users[2]['id']), 'TRANSFER'));

        $this->assertTransferRefused($this->users[1]['id']);
    }

    public function testFounderCanTransferToAMemberWithTheTransferRank(): void
    {
        $rank = $this->createRank($this->allianceId, 'TRANSFER');
        $this->setRank(1, $rank);
        $GLOBALS['_REQUEST'] = ['action' => 'transfer', 'newleader' => $this->users[1]['id']];

        $this->runAdminAction();

        $this->assertSame((int) $this->users[1]['id'], (int) $this->alliance()['ally_owner']);
        $this->assertSame(0, $this->rankOf(1));
        $this->assertSame($rank, $this->rankOf(0));
    }

    public function testTransferListOnlyShowsMembersWithTheTransferRank(): void
    {
        $this->setRank(1, $this->createRank($this->createAlliance($this->users[2]['id']), 'TRANSFER'));
        $GLOBALS['_REQUEST'] = ['action' => 'transfer'];

        $this->runAdminAction();

        $this->assertSame([], $this->assigned['transferUserList']);
    }

    public function testFounderCannotBeKicked(): void
    {
        $this->setRank(1, $this->createRank($this->allianceId, 'KICK'));
        $this->setUser($this->users[1]);
        $GLOBALS['_REQUEST'] = ['action' => 'membersKick', 'id' => $this->users[0]['id']];

        $this->runAdminAction();

        $this->assertSame($this->allianceId, $this->allianceOf(0));
    }

    public function testOtherAllianceApplicationCannotBeOpened(): void
    {
        $id = $this->createApplication($this->createAlliance($this->users[1]['id']));
        $GLOBALS['_REQUEST'] = ['action' => 'detailApply', 'id' => $id];

        $this->runAdminAction();

        $this->assertArrayNotHasKey('applyDetail', $this->assigned);
    }

    public function testOwnApplicationCanBeOpened(): void
    {
        $id = $this->createApplication($this->allianceId);
        $GLOBALS['_REQUEST'] = ['action' => 'detailApply', 'id' => $id];

        $this->runAdminAction();

        $this->assertSame($this->users[2]['username'], $this->assigned['applyDetail']['username']);
    }

    public function testOtherAllianceApplicationCannotBeAccepted(): void
    {
        $this->assertOtherAllianceApplicationUnchanged('yes');
    }

    public function testOtherAllianceApplicationCannotBeRejected(): void
    {
        $this->assertOtherAllianceApplicationUnchanged('no');
    }

    public function testOwnApplicationCanBeAccepted(): void
    {
        $id = $this->createApplication($this->allianceId);

        $this->answer($id, 'yes');

        $this->assertSame($this->allianceId, $this->allianceOf(2));
        $this->assertSame([], $this->application($id));
        $this->assertSame(3, (int) $this->alliance()['ally_members']);
    }

    public function testOwnApplicationCanBeRejected(): void
    {
        $id = $this->createApplication($this->allianceId);

        $this->answer($id, 'no');

        $this->assertSame(0, $this->allianceOf(2));
        $this->assertSame([], $this->application($id));
    }

    public function testPlayerInAnAllianceCannotApply(): void
    {
        $otherAlliance = $this->createAlliance($this->users[2]['id']);
        $this->setUser($this->users[1]);
        $GLOBALS['_REQUEST'] = ['id' => $otherAlliance, 'text' => 'Let me in'];
        $page = $this->page(ShowAlliancePage::class);
        (new ReflectionProperty(ShowAlliancePage::class, 'hasAlliance'))->setValue($page, true);

        $this->callPage($page, 'apply');

        $this->assertSame([], self::$db->select('SELECT * FROM %%ALLIANCE_REQUEST%% WHERE userId = :id;', [':id' => $this->users[1]['id']]));
    }

    private function editOverview(): void
    {
        $GLOBALS['_REQUEST'] = ['action' => 'overview', 'send' => 1, 'text' => 'New description', 'ally_max_members' => 10];
        $this->runAdminAction();
    }

    private function assertTransferRefused(int $target): void
    {
        $before = $this->alliance();
        $ranks = self::$db->select('SELECT id, ally_rank_id FROM %%USERS%% ORDER BY id;');
        $GLOBALS['_REQUEST'] = ['action' => 'transfer', 'newleader' => $target];

        $this->runAdminAction();

        $this->assertSame($before, $this->alliance());
        $this->assertSame($ranks, self::$db->select('SELECT id, ally_rank_id FROM %%USERS%% ORDER BY id;'));
    }

    private function assertOtherAllianceApplicationUnchanged(string $answer): void
    {
        $id = $this->createApplication($this->createAlliance($this->users[1]['id']));
        $application = $this->application($id);
        $player = $this->player(2);
        $alliance = $this->alliance();
        $messages = self::$db->select('SELECT message_id FROM %%MESSAGES%% ORDER BY message_id;');

        $this->answer($id, $answer);

        $this->assertSame($application, $this->application($id));
        $this->assertSame($player, $this->player(2));
        $this->assertSame($alliance, $this->alliance());
        $this->assertSame($messages, self::$db->select('SELECT message_id FROM %%MESSAGES%% ORDER BY message_id;'));
    }

    private function runAdminAction(): void
    {
        $page = $this->page(ShowAlliancePage::class);
        (new ReflectionProperty(ShowAlliancePage::class, 'hasAlliance'))->setValue($page, true);
        (new ReflectionMethod(ShowAlliancePage::class, 'setAllianceData'))->invoke($page, $this->allianceId);
        $this->callPage($page, 'admin');
    }

    private function answer(int $id, string $answer): void
    {
        $GLOBALS['_REQUEST'] = ['action' => 'sendAnswerToApply', 'id' => $id, 'answer' => $answer, 'text' => 'Response'];
        $this->runAdminAction();
    }

    private function createAlliance(int $owner): int
    {
        self::$db->insert('INSERT INTO %%ALLIANCE%% (ally_name, ally_tag, ally_owner, ally_members, ally_register_time, ally_universe) VALUES (:name, :tag, :owner, 2, :time, :universe);',
            [':name' => 'Test alliance ' . $owner, ':tag' => 'T' . $owner, ':owner' => $owner, ':time' => TIMESTAMP, ':universe' => Universe::current()]);
        return (int) self::$db->lastInsertId();
    }

    private function createRank(int $alliance, string $right): int
    {
        self::$db->insert('INSERT INTO %%ALLIANCE_RANK%% (allianceID, rankName, ' . $right . ') VALUES (:alliance, :name, 1);',
            [':alliance' => $alliance, ':name' => 'Test rank']);
        return (int) self::$db->lastInsertId();
    }

    private function createApplication(int $alliance): int
    {
        self::$db->insert('INSERT INTO %%ALLIANCE_REQUEST%% (userId, allianceId, text, time) VALUES (:user, :alliance, :text, :time);',
            [':user' => $this->users[2]['id'], ':alliance' => $alliance, ':text' => 'Private application', ':time' => TIMESTAMP]);
        return (int) self::$db->lastInsertId();
    }

    private function setAlliance(int $index, int $alliance): void
    {
        self::$db->update('UPDATE %%USERS%% SET ally_id = :alliance, ally_rank_id = 0 WHERE id = :id;',
            [':alliance' => $alliance, ':id' => $this->users[$index]['id']]);
        $this->users[$index]['ally_id'] = $alliance;
        $this->users[$index]['ally_rank_id'] = 0;
    }

    private function setRank(int $index, int $rank): void
    {
        self::$db->update('UPDATE %%USERS%% SET ally_rank_id = :rank WHERE id = :id;', [':rank' => $rank, ':id' => $this->users[$index]['id']]);
        $this->users[$index]['ally_rank_id'] = $rank;
    }

    private function alliance(): array
    {
        return self::$db->selectSingle('SELECT * FROM %%ALLIANCE%% WHERE id = :id;', [':id' => $this->allianceId]);
    }

    private function application(int $id): array
    {
        return self::$db->selectSingle('SELECT * FROM %%ALLIANCE_REQUEST%% WHERE applyID = :id;', [':id' => $id]) ?: [];
    }

    private function player(int $index): array
    {
        return self::$db->selectSingle('SELECT * FROM %%USERS%% WHERE id = :id;', [':id' => $this->users[$index]['id']]);
    }

    private function allianceOf(int $index): int
    {
        return (int) $this->player($index)['ally_id'];
    }

    private function rankOf(int $index): int
    {
        return (int) $this->player($index)['ally_rank_id'];
    }
}
