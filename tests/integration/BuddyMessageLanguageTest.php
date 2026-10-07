<?php

namespace Risistar\Tests\Integration;

use Language;
use ReflectionProperty;
use ShowBuddyListPage;

/**
 * Integration tests for the language of buddy list messages.
 */
class BuddyMessageLanguageTest extends GamePageTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowBuddyListPage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setLanguage(0, 'fr');
        $this->setLanguage(1, 'en');
    }

    public function testRequestMessageUsesTheRecipientLanguage(): void
    {
        $GLOBALS['_REQUEST'] = ['id' => $this->users[1]['id'], 'text' => 'Hello'];

        $this->callPage($this->buddyPage(), 'send');

        $this->assertSame($this->text('bu_new_request_title'), $this->lastMessage($this->users[1]['id']));
    }

    public function testAcceptMessageUsesTheSenderLanguage(): void
    {
        $this->createRequest($this->users[1]['id'], $this->users[0]['id']);

        $this->callPage($this->buddyPage(), 'accept');

        $this->assertSame($this->text('bu_accepted_request_title'), $this->lastMessage($this->users[1]['id']));
    }

    public function testRejectMessageUsesTheOtherPlayerLanguage(): void
    {
        $this->createRequest($this->users[1]['id'], $this->users[0]['id']);

        $this->callPage($this->buddyPage(), 'delete');

        $this->assertSame($this->text('bu_rejected_request_title'), $this->lastMessage($this->users[1]['id']));
    }

    private function setLanguage(int $index, string $lang): void
    {
        self::$db->update('UPDATE %%USERS%% SET lang = :lang WHERE id = :id;', [':lang' => $lang, ':id' => $this->users[$index]['id']]);
        $this->users[$index]['lang'] = $lang;
        if ($index === 0) {
            $this->setUser($this->users[0]);
        }
    }

    private function createRequest(int $sender, int $owner): void
    {
        self::$db->insert('INSERT INTO %%BUDDY%% SET sender = :sender, owner = :owner, universe = 1;', [':sender' => $sender, ':owner' => $owner]);
        $GLOBALS['_REQUEST'] = ['id' => self::$db->lastInsertId()];
        self::$db->insert('INSERT INTO %%BUDDY_REQUEST%% SET id = :id, text = :text;', [':id' => $GLOBALS['_REQUEST']['id'], ':text' => 'Hello']);
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

    private function text(string $key): string
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
