<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Unit;

use CommonPHP\Database\ConnectionDefinition;
use CommonPHP\Database\Enums\FetchMode;
use CommonPHP\Database\Enums\ParameterType;
use CommonPHP\Database\Events\ConnectedEvent;
use CommonPHP\Database\Events\QueryExecutedEvent;
use CommonPHP\Database\Exceptions\ConnectionException;
use CommonPHP\Database\Exceptions\ConnectionExistsException;
use CommonPHP\Database\Exceptions\ConnectionNotFoundException;
use CommonPHP\Database\Exceptions\DatabaseDriverException;
use CommonPHP\Database\Exceptions\DatabaseException;
use CommonPHP\Database\Exceptions\InvalidConnectionNameException;
use CommonPHP\Database\Exceptions\QueryException;
use CommonPHP\Database\Exceptions\TransactionException;
use CommonPHP\Database\Tests\Fixtures\MemoryDatabaseDriver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EnumsEventsExceptionsTest extends TestCase
{
    public function testFetchModeHelpersIdentifyModeFamilies(): void
    {
        self::assertSame(FetchMode::FETCH_ASSOC, FetchMode::default());
        self::assertTrue(FetchMode::FETCH_ASSOC->isAssociative());
        self::assertTrue(FetchMode::FETCH_NAMED->isAssociative());
        self::assertTrue(FetchMode::FETCH_NUM->isNumeric());
        self::assertTrue(FetchMode::FETCH_OBJ->isObject());
        self::assertTrue(FetchMode::FETCH_CLASS->isObject());
        self::assertTrue(FetchMode::FETCH_INTO->isObject());
        self::assertFalse(FetchMode::FETCH_COLUMN->isAssociative());
        self::assertFalse(FetchMode::FETCH_ASSOC->isNumeric());
        self::assertFalse(FetchMode::FETCH_ASSOC->isObject());
    }

    public function testParameterTypeDetectsEmptyNamedAndPositionalParameters(): void
    {
        self::assertSame(ParameterType::Empty, ParameterType::detect([]));
        self::assertSame(ParameterType::Named, ParameterType::detect(['id' => 1]));
        self::assertSame(ParameterType::Named, ParameterType::detect([0 => 1, 'id' => 2]));
        self::assertSame(ParameterType::Positional, ParameterType::detect([1, 2]));

        self::assertTrue(ParameterType::Empty->isEmpty());
        self::assertTrue(ParameterType::Named->isNamed());
        self::assertTrue(ParameterType::Positional->isPositional());
        self::assertFalse(ParameterType::Named->isEmpty());
    }

    public function testConnectedEventCarriesConnectionDetails(): void
    {
        $driver = new MemoryDatabaseDriver();
        $definition = new ConnectionDefinition('main', $driver);
        $event = new ConnectedEvent('main', $driver, $definition);

        self::assertSame(ConnectedEvent::class, $event->getName());
        self::assertSame('main', $event->connectionName);
        self::assertSame($driver, $event->driver);
        self::assertSame($definition, $event->definition);
    }

    public function testQueryExecutedEventCarriesSuccessAndFailureDetails(): void
    {
        $driver = new MemoryDatabaseDriver();
        $success = new QueryExecutedEvent('fetch all', 'select 1', [], 'main', $driver, 0.1);
        $failure = new QueryExecutedEvent(
            'execute',
            'bad',
            ['id' => 1],
            'main',
            $driver,
            0.2,
            ['message' => 'failed'],
        );

        self::assertSame(QueryExecutedEvent::class, $success->getName());
        self::assertTrue($success->succeeded());
        self::assertFalse($success->failed());
        self::assertFalse($failure->succeeded());
        self::assertTrue($failure->failed());
        self::assertSame(['id' => 1], $failure->parameters);
        self::assertSame(['message' => 'failed'], $failure->errors);
    }

    public function testExceptionFactoriesProduceUsefulMessagesAndPreviousExceptions(): void
    {
        $previous = new RuntimeException('previous');

        $exceptions = [
            ConnectionException::forCreation('main', MemoryDatabaseDriver::class, $previous),
            ConnectionException::forInvalidConfiguration('bad config'),
            ConnectionException::forOperation('connect', 'main', $previous),
            ConnectionExistsException::forName('main'),
            ConnectionNotFoundException::forName('missing'),
            DatabaseDriverException::forClass('BadDriver'),
            DatabaseDriverException::forOperation('ping', 'main', $previous),
            InvalidConnectionNameException::forName(''),
            QueryException::forOperation('execute', str_repeat('select value ', 30), $previous),
            QueryException::notExecutable('select 1'),
            TransactionException::forConnection('main', $previous),
            TransactionException::forOperation('commit', 'main', $previous),
            TransactionException::inactive('commit', 'main'),
        ];

        foreach ($exceptions as $exception) {
            self::assertInstanceOf(DatabaseException::class, $exception);
            self::assertNotSame('', $exception->getMessage());
        }

        self::assertSame($previous, $exceptions[0]->getPrevious());
        self::assertLessThanOrEqual(220, strlen($exceptions[8]->getMessage()));
    }
}
