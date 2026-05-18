<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Unit;

use CommonPHP\Database\QueryResult;
use PHPUnit\Framework\TestCase;

final class QueryResultTest extends TestCase
{
    public function testItStoresRowsFromAnyIterable(): void
    {
        $rows = (static function (): iterable {
            yield ['id' => 1];
            yield ['id' => 2];
        })();

        $result = QueryResult::rows($rows, affectedRows: 2);

        self::assertSame([['id' => 1], ['id' => 2]], $result->all());
        self::assertSame([['id' => 1], ['id' => 2]], iterator_to_array($result));
        self::assertSame(2, $result->affectedRows());
        self::assertCount(2, $result);
        self::assertFalse($result->isEmpty());
    }

    public function testAffectedResultsCarryAffectedRowsAndInsertIds(): void
    {
        $result = QueryResult::affected(3, '44');

        self::assertSame([], $result->all());
        self::assertSame(3, $result->affectedRows());
        self::assertSame('44', $result->lastInsertId());
        self::assertTrue($result->isEmpty());
    }

    public function testFirstReturnsDefaultForEmptyResults(): void
    {
        self::assertSame('fallback', (new QueryResult())->first('fallback'));
    }

    public function testScalarReadsTheFirstArrayValue(): void
    {
        self::assertSame(1, (new QueryResult([['id' => 1, 'name' => 'Ada']]))->scalar());
    }

    public function testScalarReadsTheFirstPublicObjectValue(): void
    {
        $row = new class {
            public int $id = 5;
            public string $name = 'Ada';
        };

        self::assertSame(5, (new QueryResult([$row]))->scalar());
    }

    public function testScalarReturnsScalarRowsDirectly(): void
    {
        self::assertSame('value', (new QueryResult(['value']))->scalar());
    }

    public function testScalarReturnsDefaultForEmptyArraysObjectsAndNullRows(): void
    {
        self::assertSame('fallback', (new QueryResult([[]]))->scalar('fallback'));
        self::assertSame('fallback', (new QueryResult([new class {
        }]))->scalar('fallback'));
        self::assertSame('fallback', (new QueryResult([null]))->scalar('fallback'));
        self::assertSame('fallback', (new QueryResult())->scalar('fallback'));
    }
}
