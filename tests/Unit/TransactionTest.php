<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Unit;

use CommonPHP\Database\Exceptions\QueryException;
use CommonPHP\Database\Exceptions\TransactionException;
use CommonPHP\Database\Tests\Fixtures\MemoryDatabaseDriver;
use CommonPHP\Database\Tests\Fixtures\ThrowingDatabaseDriver;
use CommonPHP\Database\Transaction;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TransactionTest extends TestCase
{
    public function testBeginCommitAndRollBackUpdateTransactionState(): void
    {
        $driver = new MemoryDatabaseDriver();
        $transaction = Transaction::begin($driver);

        self::assertSame($driver, $transaction->driver());
        self::assertTrue($transaction->isActive());
        self::assertFalse($transaction->isCompleted());
        self::assertSame(1, $driver->began);

        $transaction->commit();

        self::assertFalse($transaction->isActive());
        self::assertTrue($transaction->isCompleted());
        self::assertSame(1, $driver->committed);
    }

    public function testRollbackCompletesAnActiveTransaction(): void
    {
        $driver = new MemoryDatabaseDriver();
        $transaction = Transaction::begin($driver);

        $transaction->rollBack();

        self::assertFalse($transaction->isActive());
        self::assertTrue($transaction->isCompleted());
        self::assertSame(1, $driver->rolledBack);
    }

    public function testInactiveTransactionsCannotBeCommitted(): void
    {
        $transaction = Transaction::begin(new MemoryDatabaseDriver());
        $transaction->commit();

        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('inactive');

        $transaction->commit();
    }

    public function testInactiveTransactionsCannotBeRolledBack(): void
    {
        $transaction = Transaction::begin(new MemoryDatabaseDriver());
        $transaction->rollBack();

        $this->expectException(TransactionException::class);

        $transaction->rollBack();
    }

    public function testRunCommitsSuccessfulCallbacks(): void
    {
        $driver = new MemoryDatabaseDriver();

        $result = Transaction::run($driver, static function (MemoryDatabaseDriver $connection, Transaction $transaction): string {
            self::assertTrue($transaction->isActive());
            $connection->execute('insert row');

            return 'ok';
        });

        self::assertSame('ok', $result);
        self::assertSame(1, $driver->began);
        self::assertSame(1, $driver->committed);
        self::assertSame(0, $driver->rolledBack);
    }

    public function testRunRollsBackAndWrapsRuntimeCallbackFailures(): void
    {
        $driver = new MemoryDatabaseDriver();

        try {
            Transaction::run($driver, static function (): never {
                throw new RuntimeException('bad callback');
            });
            self::fail('Expected transaction exception.');
        } catch (TransactionException $exception) {
            self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }

        self::assertSame(1, $driver->rolledBack);
    }

    public function testRunRollsBackAndRethrowsDatabaseExceptions(): void
    {
        $driver = new MemoryDatabaseDriver();
        $databaseException = new QueryException('query failed');

        try {
            Transaction::run($driver, static function () use ($databaseException): never {
                throw $databaseException;
            });
            self::fail('Expected query exception.');
        } catch (QueryException $exception) {
            self::assertSame($databaseException, $exception);
        }

        self::assertSame(1, $driver->rolledBack);
    }

    public function testBeginFailuresAreWrapped(): void
    {
        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('begin');

        Transaction::begin(new ThrowingDatabaseDriver(new RuntimeException('begin failed'), 'beginTransaction'));
    }

    public function testCommitFailuresAreWrappedAndRunRollsBack(): void
    {
        $driver = new ThrowingDatabaseDriver(new RuntimeException('commit failed'), 'commit');

        try {
            Transaction::run($driver, static fn (): string => 'ok');
            self::fail('Expected transaction exception.');
        } catch (TransactionException $exception) {
            self::assertStringContainsString('commit', $exception->getMessage());
        }
    }

    public function testRollbackFailuresAreWrapped(): void
    {
        $transaction = Transaction::begin(new ThrowingDatabaseDriver(new RuntimeException('rollback failed'), 'rollBack'));

        $this->expectException(TransactionException::class);
        $this->expectExceptionMessage('roll back');

        $transaction->rollBack();
    }
}
