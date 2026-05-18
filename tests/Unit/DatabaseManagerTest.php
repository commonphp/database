<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Unit;

use CommonPHP\Database\ConnectionDefinition;
use CommonPHP\Database\ConnectionRegistry;
use CommonPHP\Database\Contracts\DatabaseInterface;
use CommonPHP\Database\DatabaseManager;
use CommonPHP\Database\Enums\ParameterType;
use CommonPHP\Database\Events\ConnectedEvent;
use CommonPHP\Database\Events\QueryExecutedEvent;
use CommonPHP\Database\Exceptions\ConnectionExistsException;
use CommonPHP\Database\Exceptions\ConnectionNotFoundException;
use CommonPHP\Database\Exceptions\DatabaseDriverException;
use CommonPHP\Database\Exceptions\QueryException;
use CommonPHP\Database\Exceptions\TransactionException;
use CommonPHP\Database\QueryResult;
use CommonPHP\Database\Tests\Fixtures\ArrayLogger;
use CommonPHP\Database\Tests\Fixtures\MemoryDatabaseDriver;
use CommonPHP\Database\Tests\Fixtures\ThrowingDatabaseDriver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseManagerTest extends TestCase
{
    public function testItRegistersAndLazilyCreatesConnections(): void
    {
        $manager = new DatabaseManager();
        $connected = null;

        $manager->subscribe(ConnectedEvent::class, static function (ConnectedEvent $event) use (&$connected): void {
            $connected = $event;
        });

        $manager->connect('Main', MemoryDatabaseDriver::class, [
            'rows' => [
                'select users' => [
                    ['id' => 1, 'name' => 'Ada'],
                ],
            ],
        ], default: true);

        self::assertInstanceOf(DatabaseInterface::class, $manager);
        self::assertTrue($manager->hasConnection('main'));
        self::assertFalse($manager->connections()->isResolved('main'));

        $rows = $manager->fetchAll('select users');

        self::assertSame([['id' => 1, 'name' => 'Ada']], $rows);
        self::assertTrue($manager->connections()->isResolved('main'));
        self::assertInstanceOf(ConnectedEvent::class, $connected);
        self::assertSame('main', $connected->connectionName);
    }

    public function testConnectedEventOnlyFiresOnFirstResolution(): void
    {
        $manager = DatabaseManager::connection('main', MemoryDatabaseDriver::class);
        $connections = 0;

        $manager->subscribe(ConnectedEvent::class, static function () use (&$connections): void {
            ++$connections;
        });

        $manager->with('main');
        $manager->with('main');

        self::assertSame(1, $connections);
    }

    public function testConnectionRegistryProtectsNamesAndDefaults(): void
    {
        $registry = new ConnectionRegistry();
        $driver = new MemoryDatabaseDriver();

        $registry->add('Reporting', $driver);

        self::assertSame('reporting', $registry->defaultName());
        self::assertSame($driver, $registry->get());

        $this->expectException(ConnectionExistsException::class);

        $registry->add('reporting', new MemoryDatabaseDriver());
    }

    public function testMissingConnectionThrowsClearException(): void
    {
        $manager = new DatabaseManager();

        $this->expectException(ConnectionNotFoundException::class);

        $manager->with('missing');
    }

    public function testPreparedQueriesDelegateThroughTheManager(): void
    {
        $driver = new MemoryDatabaseDriver([
            'select users where id = :id' => [
                ['id' => 7, 'name' => 'Grace'],
            ],
        ]);

        $manager = DatabaseManager::connection('main', $driver);
        $query = $manager
            ->prepare('select users where id = :id')
            ->bind('id', 7);

        self::assertSame(ParameterType::Named, $query->parameterType());
        self::assertSame(['id' => 7, 'name' => 'Grace'], $query->fetchOne());
        self::assertSame(7, $query->fetchScalar());
        self::assertSame([['id' => 7, 'name' => 'Grace']], $query->result()->all());
    }

    public function testManagerDelegatesAllQueryOperationsToSelectedConnections(): void
    {
        $main = new MemoryDatabaseDriver([
            'select rows' => [
                ['id' => 1],
                ['id' => 2],
            ],
        ], insertId: 'main-id');
        $reporting = new MemoryDatabaseDriver([
            'select rows' => [
                ['id' => 9],
            ],
        ], insertId: 'reporting-id', alive: false);

        $manager = new DatabaseManager();
        $manager->connect('main', $main, default: true);
        $manager->connect('reporting', $reporting);

        self::assertSame(2, $manager->count('select rows'));
        self::assertSame(1, $manager->execute('update rows'));
        self::assertSame(1, $manager->fetchScalar('select rows'));
        self::assertSame(['id' => 1], $manager->fetchOne('select rows'));
        self::assertSame([['id' => 1], ['id' => 2]], $manager->fetchAll('select rows'));
        self::assertSame('main-id', $manager->lastInsertId());
        self::assertTrue($manager->ping());

        self::assertSame(1, $manager->count('select rows', connection: 'reporting'));
        self::assertSame(9, $manager->fetchScalar('select rows', connection: 'reporting'));
        self::assertSame('reporting-id', $manager->lastInsertId('reporting'));
        self::assertFalse($manager->ping('reporting'));
    }

    public function testProfilingEmitsQueryEvents(): void
    {
        $manager = DatabaseManager::connection('main', new MemoryDatabaseDriver([
            'select 1' => [
                ['value' => 1],
            ],
        ]))->enableProfiling();
        $events = [];

        $manager->subscribe(QueryExecutedEvent::class, static function (QueryExecutedEvent $event) use (&$events): void {
            $events[] = $event;
        });

        self::assertSame(1, $manager->fetchScalar('select 1'));

        self::assertCount(1, $events);
        self::assertSame('fetch scalar', $events[0]->action);
        self::assertSame('main', $events[0]->connectionName);
        self::assertTrue($events[0]->succeeded());
    }

    public function testProfilingCanBeDisabled(): void
    {
        $manager = DatabaseManager::connection('main', new MemoryDatabaseDriver([
            'select 1' => [['value' => 1]],
        ]));
        $events = [];

        $manager->subscribe(QueryExecutedEvent::class, static function (QueryExecutedEvent $event) use (&$events): void {
            $events[] = $event;
        });

        self::assertFalse($manager->isProfiling());
        self::assertSame(1, $manager->fetchScalar('select 1'));
        self::assertSame([], $events);

        self::assertSame($manager, $manager->enableProfiling());
        self::assertTrue($manager->isProfiling());
        self::assertSame($manager, $manager->disableProfiling());
        self::assertFalse($manager->isProfiling());
    }

    public function testProfileQueryLogsAndEmitsManualSuccessAndFailureEvents(): void
    {
        $logger = new ArrayLogger();
        $driver = new MemoryDatabaseDriver();
        $manager = new DatabaseManager(logger: $logger);
        $events = [];

        $manager->enableProfiling();
        $manager->subscribe(QueryExecutedEvent::class, static function (QueryExecutedEvent $event) use (&$events): void {
            $events[] = $event;
        });

        $manager->profileQuery('manual', 'select 1', [], $driver, 0.01, connectionName: 'main');
        $manager->profileQuery('manual', 'bad', ['id' => 1], $driver, 0.02, ['message' => 'failed'], 'main');

        self::assertCount(2, $events);
        self::assertTrue($events[0]->succeeded());
        self::assertTrue($events[1]->failed());
        self::assertSame('debug', $logger->records[0]['level']);
        self::assertSame('error', $logger->records[1]['level']);
        self::assertSame('main', $logger->records[1]['context']['connection']);
    }

    public function testProfileQueryDoesNothingWhenProfilingIsDisabled(): void
    {
        $logger = new ArrayLogger();
        $manager = new DatabaseManager(logger: $logger);
        $events = [];

        $manager->subscribe(QueryExecutedEvent::class, static function (QueryExecutedEvent $event) use (&$events): void {
            $events[] = $event;
        });
        $manager->profileQuery('manual', 'select 1', [], new MemoryDatabaseDriver(), 0.01);

        self::assertSame([], $events);
        self::assertSame([], $logger->records);
    }

    public function testQueryWithoutExecutorIsRejected(): void
    {
        $this->expectException(QueryException::class);

        (new \CommonPHP\Database\Query('select 1'))->execute();
    }

    public function testTransactionCommitsAndRollsBack(): void
    {
        $driver = new MemoryDatabaseDriver();
        $manager = DatabaseManager::connection('main', $driver);

        $result = $manager->transaction(static function (MemoryDatabaseDriver $connection): string {
            $connection->execute('insert user');

            return 'done';
        });

        self::assertSame('done', $result);
        self::assertSame(1, $driver->began);
        self::assertSame(1, $driver->committed);
        self::assertSame(0, $driver->rolledBack);

        try {
            $manager->transaction(static function (): never {
                throw new RuntimeException('nope');
            });
        } catch (TransactionException) {
        }

        self::assertSame(2, $driver->began);
        self::assertSame(1, $driver->committed);
        self::assertSame(1, $driver->rolledBack);
    }

    public function testTransactionsCanTargetNamedConnections(): void
    {
        $main = new MemoryDatabaseDriver();
        $reporting = new MemoryDatabaseDriver();
        $manager = new DatabaseManager();
        $manager->connect('main', $main, default: true);
        $manager->connect('reporting', $reporting);

        $manager->transaction(static fn (): string => 'ok', 'reporting');

        self::assertSame(0, $main->began);
        self::assertSame(1, $reporting->began);
        self::assertSame(1, $reporting->committed);
    }

    public function testRuntimeQueryFailuresAreWrappedAndProfiled(): void
    {
        $manager = DatabaseManager::connection(
            'main',
            new ThrowingDatabaseDriver(new RuntimeException('driver down'), 'execute'),
        )->enableProfiling();
        $events = [];

        $manager->subscribe(QueryExecutedEvent::class, static function (QueryExecutedEvent $event) use (&$events): void {
            $events[] = $event;
        });

        try {
            $manager->execute('bad sql');
            self::fail('Expected query exception.');
        } catch (QueryException $exception) {
            self::assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }

        self::assertCount(1, $events);
        self::assertTrue($events[0]->failed());
        self::assertSame(RuntimeException::class, $events[0]->errors['exception']);
    }

    public function testDatabaseQueryExceptionsAreNotWrappedAgain(): void
    {
        $exception = new QueryException('already database-shaped');
        $manager = DatabaseManager::connection(
            'main',
            new ThrowingDatabaseDriver($exception, 'fetchAll'),
        );

        $this->expectExceptionObject($exception);

        $manager->fetchAll('bad sql');
    }

    public function testDriverOperationFailuresAreWrapped(): void
    {
        $manager = DatabaseManager::connection(
            'main',
            new ThrowingDatabaseDriver(new RuntimeException('driver down'), 'ping'),
        );

        $this->expectException(DatabaseDriverException::class);
        $this->expectExceptionMessage('ping');

        $manager->ping();
    }

    public function testConnectionDefinitionsCanBeCreatedFromConfig(): void
    {
        $definition = ConnectionDefinition::fromConfig([
            'driver' => MemoryDatabaseDriver::class,
            'rows' => ['select 1' => [['value' => 1]]],
            'options' => ['insertId' => '9'],
            'default' => true,
        ], 'Primary');

        self::assertSame('primary', $definition->name());
        self::assertSame(MemoryDatabaseDriver::class, $definition->driverClass());
        self::assertSame([
            'rows' => ['select 1' => [['value' => 1]]],
            'insertId' => '9',
        ], $definition->options());
        self::assertTrue($definition->isDefault());
    }

    public function testManagerCanLoadSingleConnectionConfig(): void
    {
        $manager = DatabaseManager::fromConfig([
            'driver' => MemoryDatabaseDriver::class,
            'rows' => [
                'select configured' => [
                    ['value' => 'ok'],
                ],
            ],
        ]);

        self::assertSame('ok', $manager->fetchScalar('select configured'));
    }

    public function testQueryResultExposesRowsScalarsAndAffectedRows(): void
    {
        $result = new QueryResult([
            ['id' => 1, 'name' => 'Ada'],
            ['id' => 2, 'name' => 'Grace'],
        ], affectedRows: 2, lastInsertId: '12');

        self::assertCount(2, $result);
        self::assertSame(['id' => 1, 'name' => 'Ada'], $result->first());
        self::assertSame(1, $result->scalar());
        self::assertSame(2, $result->affectedRows());
        self::assertSame('12', $result->lastInsertId());
        self::assertFalse($result->isEmpty());
    }
}
