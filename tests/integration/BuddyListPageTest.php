<?php

namespace Risistar\Tests\Integration;

use Language;
use ReflectionProperty;
use ShowBuddyListPage;
use Universe;

/**
 * Integration tests for buddy requests on the buddy list page.
 */
class BuddyListPageTest extends GamePageTestCase
{
    private array $users = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowBuddyListPage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = $this->players(3);
        foreach (['fr', 'en'] as $index => $lang) {
            self::$db->update('UPDATE %%USERS%% SET lang = :lang WHERE id = :id;', [':lang' => $lang, ':id' => $this->users[$index]['id']]);
            $this->users[$index]['lang'] = $lang;
        }
        $this->setUser($this->users[0]);
    }

    public function testSenderCannotAcceptTheirOwnRequest(): void
    {
        $buddyId = $this->createRequest(0, 1);
        $before = $this->messages();

        $this->callPage($this->buddyPage(), 'accept');

        $this->assertNotFalse($this->request($buddyId));
        $this->assertSame($before, $this->messages());
    }

    public function testOtherPlayerCannotAcceptTheRequest(): void
    {
        $buddyId = $this->createRequest(0, 1);
        $before = $this->messages();
        $this->setUser($this->users[2]);

        $this->callPage($this->buddyPage(), 'accept');

        $this->assertNotFalse($this->request($buddyId));
        $this->assertSame($before, $this->messages());
    }

    public function testRecipientAcceptsOnce(): void
    {
        $buddyId = $this->createRequest(0, 1);
        $before = $this->messages();
        $this->setUser($this->users[1]);

        $this->callPage($this->buddyPage(), 'accept');

        $this->assertFalse($this->request($buddyId));
        $this->assertCount(count($before) + 1, $this->messages());

        $this->callPage($this->buddyPage(), 'accept');

        $this->assertCount(count($before) + 1, $this->messages());
    }

    public function testRemovingAFriendSendsNoRejectMessage(): void
    {
        $friendId = $this->createRequest(0, 1);
        self::$db->delete('DELETE FROM %%BUDDY_REQUEST%% WHERE id = :id;', [':id' => $friendId]);
        $this->createRequest(2, 1);
        $before = $this->messages();
        $GLOBALS['_REQUEST'] = ['id' => $friendId];

        $this->callPage($this->buddyPage(), 'delete');

        $this->assertSame($before, $this->messages());
    }

    public function testRequestMessageUsesTheOtherPlayerLanguage(): void
    {
        $GLOBALS['_REQUEST'] = ['id' => $this->users[1]['id'], 'text' => 'Hello'];

        $this->callPage($this->buddyPage(), 'send');

        $this->assertSame($this->englishText('bu_new_request_title'), $this->lastMessage($this->users[1]['id']));
    }

    public function testAcceptMessageUsesTheOtherPlayerLanguage(): void
    {
        $this->createRequest(1, 0);

        $this->callPage($this->buddyPage(), 'accept');

        $this->assertSame($this->englishText('bu_accepted_request_title'), $this->lastMessage($this->users[1]['id']));
    }

    public function testRejectMessageUsesTheOtherPlayerLanguage(): void
    {
        $this->createRequest(1, 0);

        $this->callPage($this->buddyPage(), 'delete');

        $this->assertSame($this->englishText('bu_rejected_request_title'), $this->lastMessage($this->users[1]['id']));
    }

    /**
     * Creates a pending request from one player to another and puts its ID in the request.
     */
    private function createRequest(int $sender, int $owner): int
    {
        self::$db->insert('INSERT INTO %%BUDDY%% SET sender = :sender, owner = :owner, universe = :universe;',
            [':sender' => $this->users[$sender]['id'], ':owner' => $this->users[$owner]['id'], ':universe' => Universe::current()]);
        $buddyId = (int) self::$db->lastInsertId();
        self::$db->insert('INSERT INTO %%BUDDY_REQUEST%% SET id = :id, text = :text;', [':id' => $buddyId, ':text' => 'Hello']);
        $GLOBALS['_REQUEST'] = ['id' => $buddyId];
        return $buddyId;
    }

    private function buddyPage(): ShowBuddyListPage
    {
        $page = $this->page(ShowBuddyListPage::class, ['initTemplate', 'setWindow']);
        (new ReflectionProperty(ShowBuddyListPage::class, 'tplObj'))->setValue($page, new class {
            public function execscript($script)
            {
            }
        });
        return $page;
    }

    private function request(int $buddyId)
    {
        return self::$db->selectSingle('SELECT * FROM %%BUDDY_REQUEST%% WHERE id = :id;', [':id' => $buddyId]);
    }

    private function messages(): array
    {
        return self::$db->select('SELECT message_id FROM %%MESSAGES%% ORDER BY message_id;');
    }

    private function englishText(string $key): string
    {
        $language = new Language('en');
        $language->includeData(['INGAME']);
        return $language[$key];
    }

    private function lastMessage(int $owner): string
    {
        return self::$db->selectSingle('SELECT message_subject FROM %%MESSAGES%% WHERE message_owner = :owner ORDER BY message_id DESC LIMIT 1;',
            [':owner' => $owner], 'message_subject');
    }
}
