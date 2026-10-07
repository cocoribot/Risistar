<?php

namespace Risistar\Tests\Integration;

/**
 * Integration tests for the ADMIN right on the alliance overview.
 */
class AlliancePermissionsTest extends AllianceActionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['_REQUEST'] = ['action' => 'overview', 'send' => 1, 'text' => 'New description', 'ally_max_members' => 10];
    }

    public function testMemberCannotEditTheOverview(): void
    {
        $before = $this->alliance();
        $this->setUser($this->users[1]);

        $this->runAdminAction();

        $this->assertSame($before, $this->alliance());
    }

    public function testFounderCanEditTheOverview(): void
    {
        $this->runAdminAction();

        $this->assertSame('New description', $this->alliance()['ally_description']);
    }

    public function testMemberWithTheAdminRightCanEditTheOverview(): void
    {
        $this->setRank(1, $this->createRank($this->allianceId, 'ADMIN'));
        $this->setUser($this->users[1]);

        $this->runAdminAction();

        $this->assertSame('New description', $this->alliance()['ally_description']);
    }
}
