<?php

namespace Risistar\Tests\Integration;

use ShowNotesPage;

/**
 * Integration tests for note ownership on the notes page.
 */
class NotesOwnershipTest extends GamePageTestCase
{
    private int $noteId = 0;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowNotesPage.class.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$db->insert('INSERT INTO %%NOTES%% SET owner = :owner, universe = 1, time = :time, priority = 1, title = :title, text = :text;',
            [':owner' => $this->users[0]['id'], ':time' => TIMESTAMP, ':title' => 'Private title', ':text' => 'Private text']);
        $this->noteId = self::$db->lastInsertId();
        $GLOBALS['_REQUEST'] = ['id' => $this->noteId, 'title' => 'Changed', 'text' => 'Changed', 'priority' => 2];
    }

    public function testOtherPlayerCannotEditTheNote(): void
    {
        $before = $this->note();
        $this->setUser($this->users[1]);

        $this->callPage($this->page(ShowNotesPage::class), 'insert');

        $this->assertSame($before, $this->note());
    }

    public function testOwnerCanEditTheNote(): void
    {
        $this->callPage($this->page(ShowNotesPage::class), 'insert');

        $note = $this->note();
        $this->assertSame('Changed', $note['title']);
        $this->assertSame('Changed', $note['text']);
        $this->assertSame(2, (int) $note['priority']);
    }

    public function testOwnerCanCreateANote(): void
    {
        $GLOBALS['_REQUEST'] = ['title' => 'New note', 'text' => 'New text'];

        $this->callPage($this->page(ShowNotesPage::class), 'insert');

        $note = self::$db->selectSingle('SELECT * FROM %%NOTES%% WHERE id = :id;', [':id' => self::$db->lastInsertId()]);
        $this->assertSame((int) $this->users[0]['id'], (int) $note['owner']);
        $this->assertSame('New text', $note['text']);
    }

    private function note()
    {
        return self::$db->selectSingle('SELECT * FROM %%NOTES%% WHERE id = :id;', [':id' => $this->noteId]);
    }
}
