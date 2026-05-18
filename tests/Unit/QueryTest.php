<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Unit;

use CommonPHP\Database\DatabaseManager;
use CommonPHP\Database\Enums\FetchMode;
use CommonPHP\Database\Enums\ParameterType;
use CommonPHP\Database\Exceptions\QueryException;
use CommonPHP\Database\Query;
use CommonPHP\Database\Tests\Fixtures\MemoryDatabaseDriver;
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase
{
    public function testItExposesSqlParametersConnectionAndStringValue(): void
    {
        $query = new Query('select * from users', [1], connection: 'main');

        self::assertSame('select * from users', $query->sql());
        self::assertSame('select * from users', $query->getQuery());
        self::assertSame('select * from users', (string) $query);
        self::assertSame([1], $query->parameters());
        self::assertSame([1], $query->getParameters());
        self::assertSame('main', $query->connection());
        self::assertSame(ParameterType::Positional, $query->parameterType());
    }

    public function testItIsImmutableWhenChangingParametersAndConnection(): void
    {
        $query = new Query('select * from users', ['id' => 1], connection: 'main');

        $changedParameters = $query->withParameters(['id' => 2]);
        $bound = $query->bind('role', 'admin');
        $changedConnection = $query->on('reporting');

        self::assertSame(['id' => 1], $query->parameters());
        self::assertSame('main', $query->connection());
        self::assertSame(['id' => 2], $changedParameters->parameters());
        self::assertSame(['id' => 1, 'role' => 'admin'], $bound->parameters());
        self::assertSame('reporting', $changedConnection->connection());
    }

    public function testItCanExecuteAgainstADirectDriver(): void
    {
        $driver = new MemoryDatabaseDriver([
            'select users' => [
                ['id' => 1],
                ['id' => 2],
            ],
        ], insertId: '88');
        $query = Query::forDriver('select users', [], $driver);

        self::assertSame(2, $query->count());
        self::assertSame(['id' => 1], $query->fetchOne());
        self::assertSame(1, $query->fetchScalar());
        self::assertSame([['id' => 1], ['id' => 2]], $query->fetchAll(FetchMode::FETCH_NUM));
        self::assertSame(FetchMode::FETCH_NUM, $driver->lastFetchMode);
        self::assertSame(1, $query->execute());
    }

    public function testItCanExecuteAgainstAManagerAndSpecificConnection(): void
    {
        $manager = new DatabaseManager();
        $manager->connect('main', new MemoryDatabaseDriver([
            'select users' => [['id' => 1]],
        ]), default: true);
        $manager->connect('reporting', new MemoryDatabaseDriver([
            'select users' => [['id' => 9]],
        ]));

        $query = (new Query('select users'))->using($manager, 'reporting');

        self::assertSame(['id' => 9], $query->fetchOne());
        self::assertSame([['id' => 9]], $query->result()->all());
    }

    public function testUsingKeepsExistingConnectionWhenNoReplacementIsProvided(): void
    {
        $manager = DatabaseManager::connection('main', new MemoryDatabaseDriver());
        $query = (new Query('select 1', connection: 'reporting'))->using($manager);

        self::assertSame('reporting', $query->connection());
    }

    public function testQueriesWithoutExecutorsCannotRun(): void
    {
        $this->expectException(QueryException::class);

        (new Query('select 1'))->count();
    }
}
