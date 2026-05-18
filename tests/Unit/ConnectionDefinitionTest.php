<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Unit;

use CommonPHP\Database\ConnectionDefinition;
use CommonPHP\Database\Exceptions\ConnectionException;
use CommonPHP\Database\Exceptions\DatabaseDriverException;
use CommonPHP\Database\Exceptions\InvalidConnectionNameException;
use CommonPHP\Database\Tests\Fixtures\MemoryDatabaseDriver;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ConnectionDefinitionTest extends TestCase
{
    public function testItNormalizesAndExposesConnectionDefinitionValues(): void
    {
        $definition = new ConnectionDefinition(' Main ', MemoryDatabaseDriver::class, ['insertId' => '42'], true);

        self::assertSame('main', $definition->name());
        self::assertSame('main', (string) $definition);
        self::assertSame(MemoryDatabaseDriver::class, $definition->driverClass());
        self::assertSame(['insertId' => '42'], $definition->options());
        self::assertFalse($definition->hasDriverInstance());
        self::assertNull($definition->driverInstance());
        self::assertTrue($definition->isDefault());
    }

    public function testItAcceptsDriverInstances(): void
    {
        $driver = new MemoryDatabaseDriver();
        $definition = new ConnectionDefinition('default', $driver);

        self::assertSame(MemoryDatabaseDriver::class, $definition->driverClass());
        self::assertTrue($definition->hasDriverInstance());
        self::assertSame($driver, $definition->driverInstance());
    }

    public function testDriverInstancesCannotAlsoReceiveOptions(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('cannot define constructor options');

        new ConnectionDefinition('main', new MemoryDatabaseDriver(), ['rows' => []]);
    }

    public function testInvalidDriverClassIsRejected(): void
    {
        $this->expectException(DatabaseDriverException::class);

        new ConnectionDefinition('main', stdClass::class);
    }

    public function testNamesCannotBeEmpty(): void
    {
        $this->expectException(InvalidConnectionNameException::class);

        ConnectionDefinition::normalizeName('   ');
    }

    public function testNamesCannotContainNullBytes(): void
    {
        $this->expectException(InvalidConnectionNameException::class);

        ConnectionDefinition::normalizeName("bad\0name");
    }

    public function testItBuildsDefinitionsFromConfig(): void
    {
        $definition = ConnectionDefinition::fromConfig([
            'name' => 'Primary',
            'driver' => MemoryDatabaseDriver::class,
            'rows' => ['select 1' => [['value' => 1]]],
            'options' => ['insertId' => '9'],
            'default' => true,
        ]);

        self::assertSame('primary', $definition->name());
        self::assertSame([
            'rows' => ['select 1' => [['value' => 1]]],
            'insertId' => '9',
        ], $definition->options());
        self::assertTrue($definition->isDefault());
    }

    public function testConfigRequiresAStringNameWhenProvided(): void
    {
        $this->expectException(InvalidConnectionNameException::class);

        ConnectionDefinition::fromConfig([
            'name' => 123,
            'driver' => MemoryDatabaseDriver::class,
        ]);
    }

    public function testConfigRequiresADriver(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('must define a database driver');

        ConnectionDefinition::fromConfig(['name' => 'main']);
    }

    public function testConfigOptionsMustBeAnArray(): void
    {
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('options must be an array');

        ConnectionDefinition::fromConfig([
            'driver' => MemoryDatabaseDriver::class,
            'options' => 'bad',
        ]);
    }
}
