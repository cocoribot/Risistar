<?php

namespace Risistar\Tests\Integration;

/**
 * Integration tests for Database::afterCommit(): a callback runs only if the writes
 * made next to it are committed, also inside nested transactions.
 */
class DatabaseAfterCommitTest extends IntegrationTestCase
{
    private array $calls = [];
    private int $planetId = 0;
    private float $metal = 0.0;

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
        self::$db->update(
            'UPDATE %%PLANETS%% SET metal = :metal WHERE id = :id;',
            [':metal' => $this->metal, ':id' => $this->planetId]
        );
    }

    public function testCallbackWaitsForTheMainCommit(): void
    {
        self::$db->beginTransaction();
        self::$db->afterCommit(fn() => $this->calls[] = 'outer');
        self::$db->beginTransaction();
        self::$db->afterCommit(fn() => $this->calls[] = 'nested');
        self::$db->commit();

        $this->assertSame([], $this->calls, 'Closing a nested transaction is not a commit.');

        self::$db->commit();

        $this->assertSame(['outer', 'nested'], $this->calls);
    }

    public function testNestedRollbackDropsOnlyItsOwnCallbacks(): void
    {
        self::$db->beginTransaction();
        $this->addMetal(100);
        self::$db->afterCommit(fn() => $this->calls[] = 'kept');

        self::$db->beginTransaction();
        $this->addMetal(1000);
        self::$db->afterCommit(fn() => $this->calls[] = 'dropped');
        self::$db->rollBack();

        self::$db->commit();

        $this->assertSame(['kept'], $this->calls);
        $this->assertEqualsWithDelta($this->metal + 100, $this->currentMetal(), 0.1);
    }

    public function testMainRollbackDropsCallbacksOfClosedNestedTransactions(): void
    {
        self::$db->beginTransaction();
        self::$db->beginTransaction();
        $this->addMetal(1000);
        self::$db->afterCommit(fn() => $this->calls[] = 'nested');
        self::$db->commit();
        self::$db->rollBack();

        $this->assertSame([], $this->calls);
        $this->assertEqualsWithDelta($this->metal, $this->currentMetal(), 0.1);
        $this->assertTrue(self::$db->wasRequestRolledBack());
    }

    public function testRollBackAllDropsEveryPendingCallback(): void
    {
        self::$db->beginTransaction();
        self::$db->afterCommit(fn() => $this->calls[] = 'outer');
        self::$db->beginTransaction();
        self::$db->afterCommit(fn() => $this->calls[] = 'nested');

        self::$db->rollBackAll();
        self::$db->beginTransaction();
        self::$db->commit();

        $this->assertSame([], $this->calls, 'A new transaction must not run callbacks from a cancelled one.');
    }

    public function testCallbackRunsImmediatelyOutsideTransactions(): void
    {
        self::$db->afterCommit(fn() => $this->calls[] = 'now');

        $this->assertSame(['now'], $this->calls);
    }

    private function addMetal(float $amount): void
    {
        self::$db->update(
            'UPDATE %%PLANETS%% SET metal = metal + :amount WHERE id = :id;',
            [':amount' => $amount, ':id' => $this->planetId]
        );
    }

    private function currentMetal(): float
    {
        return (float) self::$db->selectSingle('SELECT metal FROM %%PLANETS%% WHERE id = :id;', [':id' => $this->planetId], 'metal');
    }
}
