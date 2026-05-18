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
use CommonPHP\Database\Exceptions\QueryException;
use CommonPHP\Database\Exceptions\TransactionException;
use CommonPHP\Database\QueryResult;
use CommonPHP\Database\Tests\Fixtures\MemoryDatabaseDriver;
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
