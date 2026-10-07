<?php

namespace Risistar\Tests\Integration;

/**
 * Integration tests for answering alliance applications.
 */
class AllianceApplicationScopeTest extends AllianceActionTestCase
{
    public function testAnotherAlliancesApplicationCannotBeAccepted(): void
    {
        $this->assertForeignApplicationUnchanged('yes');
    }

    public function testAnotherAlliancesApplicationCannotBeRejected(): void
    {
        $this->assertForeignApplicationUnchanged('no');
    }

    public function testOwnApplicationCanBeAccepted(): void
    {
        $id = $this->createApplication($this->allianceId);
        $this->answer($id, 'yes');
        $member = self::$db->selectSingle('SELECT ally_id FROM %%USERS%% WHERE id = :id;', [':id' => $this->users[2]['id']], 'ally_id');
        $this->assertEquals($this->allianceId, $member);
        $this->assertEmpty($this->application($id));
        $this->assertEquals(3, $this->alliance()['ally_members']);
    }

    public function testOwnApplicationCanBeRejected(): void
    {
        $id = $this->createApplication($this->allianceId);
        $this->answer($id, 'no');
        $member = self::$db->selectSingle('SELECT ally_id FROM %%USERS%% WHERE id = :id;', [':id' => $this->users[2]['id']], 'ally_id');
        $this->assertEquals($this->users[2]['ally_id'], $member);
        $this->assertEmpty($this->application($id));
    }

    private function assertForeignApplicationUnchanged(string $answer): void
    {
        $id = $this->createApplication($this->createAlliance($this->users[1]['id']));
        $request = $this->application($id);
        $member = self::$db->selectSingle('SELECT * FROM %%USERS%% WHERE id = :id;', [':id' => $this->users[2]['id']]);
        $alliance = $this->alliance();
        $messages = self::$db->select('SELECT message_id FROM %%MESSAGES%% ORDER BY message_id;');
        $this->answer($id, $answer);
        $this->assertSame($request, $this->application($id));
        $this->assertSame($member, self::$db->selectSingle('SELECT * FROM %%USERS%% WHERE id = :id;', [':id' => $this->users[2]['id']]));
        $this->assertSame($alliance, $this->alliance());
        $this->assertSame($messages, self::$db->select('SELECT message_id FROM %%MESSAGES%% ORDER BY message_id;'));
    }

    private function createApplication(int $alliance): int
    {
        self::$db->insert('INSERT INTO %%ALLIANCE_REQUEST%% (userId, allianceId, text, time) VALUES (:user, :alliance, :text, :time);',
            [':user' => $this->users[2]['id'], ':alliance' => $alliance, ':text' => 'Private application', ':time' => TIMESTAMP]);
        return (int) self::$db->lastInsertId();
    }

    private function answer(int $id, string $answer): void
    {
        $GLOBALS['_REQUEST'] = ['action' => 'sendAnswerToApply', 'id' => $id, 'answer' => $answer, 'text' => 'Response'];
        $this->runAdminAction();
    }

    private function application(int $id): array
    {
        return self::$db->selectSingle('SELECT * FROM %%ALLIANCE_REQUEST%% WHERE applyID = :id;', [':id' => $id]) ?: [];
    }
}
