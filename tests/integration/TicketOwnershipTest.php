<?php

namespace Risistar\Tests\Integration;

use ReflectionProperty;
use ShowTicketPage;
use SupportTickets;

/**
 * Integration tests for ticket ownership on the support ticket page.
 */
class TicketOwnershipTest extends GamePageTestCase
{
    private array $users = [];
    private int $ticketId = 0;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once self::rootPath() . 'includes/pages/game/ShowTicketPage.class.php';
        require_once self::rootPath() . 'includes/classes/class.SupportTickets.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = $this->players(2);
        $tickets = new SupportTickets();
        $this->ticketId = $tickets->createTicket($this->users[0]['id'], 1, 'Private ticket');
        $tickets->createAnswer($this->ticketId, $this->users[0]['id'], $this->users[0]['username'],
            'Private ticket', 'Private message', 0);
        $GLOBALS['_REQUEST'] = ['id' => $this->ticketId, 'message' => 'Reply'];
    }

    public function testOtherPlayerCannotReadTheTicket(): void
    {
        $this->setUser($this->users[1]);

        $this->callPage($this->ticketPage(), 'view');

        $this->assertArrayNotHasKey('answerList', $this->assigned);
    }

    public function testOtherPlayerCannotAnswerTheTicket(): void
    {
        $before = $this->answers();
        $this->setUser($this->users[1]);

        $this->callPage($this->ticketPage(), 'send');

        $this->assertSame($before, $this->answers());
    }

    public function testOwnerCanReadAndAnswer(): void
    {
        $this->callPage($this->ticketPage(), 'view');

        $this->assertSame('Private message', reset($this->assigned['answerList'])['message']);

        $this->callPage($this->ticketPage(), 'send');

        $answers = $this->answers();
        $this->assertCount(2, $answers);
        $this->assertSame('Reply', $answers[1]['message']);
    }

    public function testOwnerCannotAnswerAClosedTicket(): void
    {
        self::$db->update('UPDATE %%TICKETS%% SET status = 2 WHERE ticketID = :id;', [':id' => $this->ticketId]);
        $before = $this->answers();

        $this->callPage($this->ticketPage(), 'send');

        $this->assertSame($before, $this->answers());
    }

    private function answers(): array
    {
        return self::$db->select('SELECT * FROM %%TICKETS_ANSWER%% WHERE ticketID = :id ORDER BY answerID;', [':id' => $this->ticketId]);
    }

    private function ticketPage(): ShowTicketPage
    {
        $page = $this->page(ShowTicketPage::class);
        (new ReflectionProperty(ShowTicketPage::class, 'ticketObj'))->setValue($page, new SupportTickets());
        return $page;
    }
}
