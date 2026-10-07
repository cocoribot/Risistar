<?php

namespace Risistar\Tests\Integration;

/**
 * Integration tests for choosing the alliance admin action.
 */
class AllianceActionDispatchTest extends AllianceActionTestCase
{
    public function testEmptyActionOpensTheOverview(): void
    {
        $GLOBALS['_REQUEST'] = ['action' => ''];

        $this->runAdminAction();

        $this->assertSame('page.alliance.admin.overview.tpl', $this->displayed);
    }
}
