<?php

namespace Risistar\Tests\Integration;

use GameRequest;

/**
 * Integration tests for the end of a game request: the economy is saved inside the
 * request transaction, before the session, and never after a failed nested action.
 */
class GameRequestTest extends IntegrationTestCase
{
    private array $calls = [];
    private int $planetId = 0;
    private float $metal = 0.0;
    private bool $registered = false;

    protected function setUp(): void
    {
        $this->requireDatabase();

        $planet = self::$db->selectSingle('SELECT id, metal FROM %%PLANETS%% ORDER BY id ASC LIMIT 1;');
        if (!$planet) {
            $this->markTestSkipped('Need at least one planet in the database.');
        }
        $this->planetId = (int) $planet['id'];
        $this->metal = (float) $planet['metal'];
        $this->calls = [];
        // The test calls finish() itself; PHPUnit must not run it again at exit.
        $this->registered = $this->state('registered');
        $this->setState('registered', true);
    }

    protected function tearDown(): void
    {
        if (self::$db === null) {
            return;
        }

        if (self::$db->getTransactionDepth() > 0) {
            self::$db->rollBackAll();
        }
        (new \ReflectionProperty(self::$db, 'requestRolledBack'))->setValue(self::$db, false);
        $this->setState('economy', null);
        $this->setState('session', null);
        $this->setState('registered', $this->registered);
        self::$db->update(
            'UPDATE %%PLANETS%% SET metal = :metal WHERE id = :id;',
            [':metal' => $this->metal, ':id' => $this->planetId]
        );
    }

    public function testPageStoppingEarlySavesItsEconomyInsideTheRequest(): void
    {
        self::$db->beginTransaction();
        GameRequest::economy(fn() => $this->saveEconomy());

        GameRequest::finish();

        $this->assertSame([1], $this->calls, 'The planet lock from the request is still held.');
        $this->assertSame(0, self::$db->getTransactionDepth());
        $this->assertEqualsWithDelta($this->metal + 1, $this->planetMetal(), 0.1);
    }

    public function testEconomyIsSavedBeforeTheSession(): void
    {
        self::$db->beginTransaction();
        GameRequest::economy(fn() => $this->calls[] = 'economy');
        GameRequest::session(fn() => $this->calls[] = 'session');

        GameRequest::finish();

        $this->assertSame(['economy', 'session'], $this->calls);
    }

    public function testUnfinishedNestedActionSavesNothing(): void
    {
        // A fatal error inside a nested action leaves the request at depth 2.
        self::$db->beginTransaction();
        self::$db->beginTransaction();
        GameRequest::economy(fn() => $this->saveEconomy());

        GameRequest::finish();

        $this->assertSame([], $this->calls);
        $this->assertEqualsWithDelta($this->metal, $this->planetMetal(), 0.1);
    }

    private function saveEconomy(): void
    {
        $this->calls[] = self::$db->getTransactionDepth();
        self::$db->update('UPDATE %%PLANETS%% SET metal = metal + 1 WHERE id = :id;', [':id' => $this->planetId]);
    }

    private function planetMetal(): float
    {
        return (float) self::$db->selectSingle('SELECT metal FROM %%PLANETS%% WHERE id = :id;', [':id' => $this->planetId], 'metal');
    }

    private function state(string $name)
    {
        return (new \ReflectionProperty(GameRequest::class, $name))->getValue();
    }

    private function setState(string $name, $value): void
    {
        (new \ReflectionProperty(GameRequest::class, $name))->setValue(null, $value);
    }
}
