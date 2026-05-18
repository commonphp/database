<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Unit;

use CommonPHP\Database\ConnectionDefinition;
use CommonPHP\Database\ConnectionRegistry;
use CommonPHP\Database\Exceptions\ConnectionException;
use CommonPHP\Database\Exceptions\ConnectionExistsException;
use CommonPHP\Database\Exceptions\ConnectionNotFoundException;
use CommonPHP\Database\Tests\Fixtures\MemoryDatabaseDriver;
use CommonPHP\Database\Tests\Fixtures\ThrowingConstructorDatabaseDriver;
use PHPUnit\Framework\TestCase;

final class ConnectionRegistryTest extends TestCase
{
    public function testItCanBeBuiltFromDefinitionsAndConfigArrays(): void
    {
        $driver = new MemoryDatabaseDriver();
        $registry = new ConnectionRegistry([
            new ConnectionDefinition('main', $driver),
            'reporting' => [
                'driver' => MemoryDatabaseDriver::class,
                'insertId' => '55',
                'default' => true,
            ],
        ]);

        self::assertCount(2, $registry);
        self::assertSame('reporting', $registry->defaultName());
        self::assertSame($driver, $registry->get('main'));
        self::assertSame(['main', 'reporting'], array_keys($registry->all()));
        self::assertSame(['main', 'reporting'], array_keys(iterator_to_array($registry)));
    }

    public function testItBuildsFromAConnectionListConfig(): void
    {
        $registry = ConnectionRegistry::fromConfig([
            'connections' => [
                'main' => ['driver' => MemoryDatabaseDriver::class],
                ['driver' => MemoryDatabaseDriver::class],
                ['name' => 'archive', 'driver' => MemoryDatabaseDriver::class, 'default' => true],
            ],
        ]);

        self::assertSame(['main', 'connection_2', 'archive'], array_keys($registry->all()));
        self::assertSame('archive', $registry->defaultName());
    }

    public function testItBuildsFromASingleConnectionConfig(): void
    {
        $registry = ConnectionRegistry::fromConfig([
            'driver' => MemoryDatabaseDriver::class,
            'insertId' => '77',
        ]);

        self::assertSame(ConnectionDefinition::DEFAULT_NAME, $registry->defaultName());
        self::assertSame(['insertId' => '77'], $registry->defaultDefinition()->options());
    }

    public function testItRegistersAddsResolvesAndCachesDrivers(): void
    {
        $registry = new ConnectionRegistry();

        self::assertSame($registry, $registry->add('main', MemoryDatabaseDriver::class));
        self::assertTrue($registry->has('MAIN'));
        self::assertFalse($registry->isResolved('main'));

        $first = $registry->get('main');
        $second = $registry->get('main');

        self::assertSame($first, $second);
        self::assertTrue($registry->isResolved('main'));
        self::assertSame(['main' => $first], $registry->resolved());
    }

    public function testDuplicateConnectionsAreRejected(): void
    {
        $registry = new ConnectionRegistry([
            'main' => ['driver' => MemoryDatabaseDriver::class],
        ]);

        $this->expectException(ConnectionExistsException::class);

        $registry->add('main', MemoryDatabaseDriver::class);
    }

    public function testMissingConnectionsAreRejected(): void
    {
        $this->expectException(ConnectionNotFoundException::class);

        (new ConnectionRegistry())->get('missing');
    }

    public function testDefaultNameRequiresAConnection(): void
    {
        $this->expectException(ConnectionNotFoundException::class);

        (new ConnectionRegistry())->defaultName();
    }

    public function testSetDefaultRequiresExistingConnection(): void
    {
        $this->expectException(ConnectionNotFoundException::class);

        (new ConnectionRegistry())->setDefault('missing');
    }

    public function testRemoveAdjustsTheDefaultAndClearsResolvedDrivers(): void
    {
        $registry = new ConnectionRegistry([
            'main' => ['driver' => MemoryDatabaseDriver::class],
            'reporting' => ['driver' => MemoryDatabaseDriver::class, 'default' => true],
        ]);

        $registry->get('reporting');
        self::assertTrue($registry->isResolved('reporting'));

        self::assertSame($registry, $registry->remove('reporting'));

        self::assertFalse($registry->has('reporting'));
        self::assertFalse($registry->isResolved('reporting'));
        self::assertSame('main', $registry->defaultName());
    }

    public function testClearRemovesDefinitionsAndResolvedDrivers(): void
    {
        $registry = new ConnectionRegistry([
            'main' => ['driver' => MemoryDatabaseDriver::class],
        ]);
        $registry->get('main');

        self::assertSame($registry, $registry->clear());
        self::assertSame([], $registry->all());
        self::assertSame([], $registry->resolved());
        self::assertCount(0, $registry);
    }

    public function testInvalidRegistryItemsAreRejected(): void
    {
        $this->expectException(ConnectionException::class);

        new ConnectionRegistry(['bad']);
    }

    public function testInvalidConnectionListConfigIsRejected(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('must be iterable');

        ConnectionRegistry::fromConfig(['connections' => 'bad']);
    }

    public function testDriverCreationFailuresAreWrapped(): void
    {
        $registry = new ConnectionRegistry([
            'main' => ['driver' => ThrowingConstructorDatabaseDriver::class],
        ]);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('could not create driver');

        $registry->get('main');
    }
}
