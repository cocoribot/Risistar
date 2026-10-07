<?php

namespace Risistar\Tests\Integration;

/**
 * Integration tests for transferring the alliance to another member.
 */
class AllianceTransferEligibilityTest extends AllianceActionTestCase
{
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

        $this->assertEquals($this->users[1]['id'], $this->alliance()['ally_owner']);
        $this->assertEquals(0, $this->rankOf(1));
        $this->assertEquals($rank, $this->rankOf(0));
    }

    public function testTransferListOnlyShowsMembersWithTheTransferRank(): void
    {
        $this->setRank(1, $this->createRank($this->createAlliance($this->users[2]['id']), 'TRANSFER'));
        $GLOBALS['_REQUEST'] = ['action' => 'transfer'];

        $this->runAdminAction();

        $this->assertSame([], $this->assigned['transferUserList']);
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

    private function rankOf(int $index): int
    {
        return (int) self::$db->selectSingle('SELECT ally_rank_id FROM %%USERS%% WHERE id = :id;', [':id' => $this->users[$index]['id']], 'ally_rank_id');
    }
}
