<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Unit;

use CommonPHP\Database\Enums\FetchMode;
use CommonPHP\Database\Enums\ParameterType;
use CommonPHP\Database\Query;
use CommonPHP\Database\Tests\Fixtures\DefaultNamedDatabaseDriver;
use CommonPHP\Database\Tests\Fixtures\ExposedDatabaseDriver;
use CommonPHP\Database\Tests\Fixtures\MemoryDatabaseDriver;
use PHPUnit\Framework\TestCase;

final class AbstractDatabaseDriverTest extends TestCase
{
    public function testDefaultNameIsTheClassName(): void
    {
        self::assertSame(DefaultNamedDatabaseDriver::class, (new DefaultNamedDatabaseDriver())->getName());
    }

    public function testPrepareCreatesAnExecutableQueryForTheDriver(): void
    {
        $driver = new MemoryDatabaseDriver([
            'select value' => [['value' => 10]],
        ]);
        $query = $driver->prepare('select value');

        self::assertInstanceOf(Query::class, $query);
        self::assertSame(10, $query->fetchScalar());
    }

    public function testDefaultCountUsesFetchAll(): void
    {
        $driver = new MemoryDatabaseDriver([
            'select many' => [['id' => 1], ['id' => 2]],
        ]);

        self::assertSame(2, $driver->count('select many'));
    }

    public function testDefaultFetchScalarUsesFirstColumnOrDefault(): void
    {
        $driver = new MemoryDatabaseDriver([
            'select value' => [['value' => 'first', 'other' => 'second']],
            'select empty' => [[]],
        ]);

        self::assertSame('first', $driver->fetchScalar('select value'));
        self::assertSame('fallback', $driver->fetchScalar('select missing', default: 'fallback'));
        self::assertSame('fallback', $driver->fetchScalar('select empty', default: 'fallback'));
    }

    public function testProtectedHelpersCanNormalizeParametersAndFetchModes(): void
    {
        $driver = new ExposedDatabaseDriver();

        self::assertSame(ParameterType::Named, $driver->exposedParameterType(['id' => 1]));
        self::assertSame(ParameterType::Positional, $driver->exposedParameterType([1]));
        self::assertSame(FetchMode::FETCH_OBJ->value, $driver->exposedFetchModeValue(FetchMode::FETCH_OBJ));
    }

    public function testDefaultTransactionRunsThroughTransactionHelper(): void
    {
        $driver = new MemoryDatabaseDriver();

        $result = $driver->transaction(static fn (MemoryDatabaseDriver $connection): string => $connection->getName());

        self::assertSame('memory', $result);
        self::assertSame(1, $driver->began);
        self::assertSame(1, $driver->committed);
    }
}
