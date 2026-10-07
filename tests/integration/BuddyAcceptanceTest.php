<?php

namespace Risistar\Tests\Integration;

use ShowBuddyListPage;

/**
 * Integration tests for accepting buddy requests.
 */
class BuddyAcceptanceTest extends GamePageTestCase
{
    private int $buddyId = 0;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowBuddyListPage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$db->insert('INSERT INTO %%BUDDY%% SET sender = :sender, owner = :owner, universe = 1;',
            [':sender' => $this->users[0]['id'], ':owner' => $this->users[1]['id']]);
        $this->buddyId = self::$db->lastInsertId();
        self::$db->insert('INSERT INTO %%BUDDY_REQUEST%% SET id = :id, text = :text;', [':id' => $this->buddyId, ':text' => 'Hello']);
        $GLOBALS['_REQUEST'] = ['id' => $this->buddyId];
    }

    public function testSenderCannotAcceptTheirOwnRequest(): void
    {
        $before = $this->messages();

        $this->callPage($this->page(ShowBuddyListPage::class), 'accept');

        $this->assertNotFalse($this->request());
        $this->assertSame($before, $this->messages());
    }

    public function testOtherPlayerCannotAcceptTheRequest(): void
    {
        $before = $this->messages();
        $this->setUser($this->users[2]);

        $this->callPage($this->page(ShowBuddyListPage::class), 'accept');

        $this->assertNotFalse($this->request());
        $this->assertSame($before, $this->messages());
    }

    public function testRecipientAcceptsOnce(): void
    {
        $before = $this->messages();
        $this->setUser($this->users[1]);

        $this->callPage($this->page(ShowBuddyListPage::class), 'accept');

        $this->assertFalse($this->request());
        $this->assertCount(count($before) + 1, $this->messages());

        $this->callPage($this->page(ShowBuddyListPage::class), 'accept');

        $this->assertCount(count($before) + 1, $this->messages());
    }

    private function request()
    {
        return self::$db->selectSingle('SELECT * FROM %%BUDDY_REQUEST%% WHERE id = :id;', [':id' => $this->buddyId]);
    }

    private function messages(): array
    {
        return self::$db->select('SELECT message_id FROM %%MESSAGES%% ORDER BY message_id;');
    }
}
