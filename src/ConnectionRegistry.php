<?php

declare(strict_types=1);

namespace CommonPHP\Database;

use CommonPHP\Database\Contracts\DatabaseDriverInterface;
use CommonPHP\Database\Exceptions\ConnectionException;
use CommonPHP\Database\Exceptions\ConnectionExistsException;
use CommonPHP\Database\Exceptions\ConnectionNotFoundException;
use Countable;
use IteratorAggregate;
use Throwable;
use Traversable;

/**
 * @implements IteratorAggregate<string, ConnectionDefinition>
 */
final class ConnectionRegistry implements Countable, IteratorAggregate
{
    /**
     * @var array<string, ConnectionDefinition>
     */
    private array $definitions = [];

    /**
     * @var array<string, DatabaseDriverInterface>
     */
    private array $drivers = [];

    private ?string $defaultName = null;

    /**
     * @param iterable<ConnectionDefinition|array<string, mixed>> $connections
     */
    public function __construct(iterable $connections = [])
    {
        foreach ($connections as $name => $connection) {
            if ($connection instanceof ConnectionDefinition) {
                $this->register($connection, $connection->isDefault());

                continue;
            }

            if (is_array($connection)) {
                $this->register(ConnectionDefinition::fromConfig(
                    $connection,
                    is_string($name) ? $name : null,
                ));

                continue;
            }

            throw ConnectionException::forInvalidConfiguration(
                'Database connection registry items must be connection definitions or arrays.',
            );
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config): self
    {
        $registry = new self();

        if (array_key_exists('driver', $config)) {
            return $registry->register(ConnectionDefinition::fromConfig($config));
        }

        $connections = $config['connections'] ?? $config;

        if (!is_iterable($connections)) {
            throw ConnectionException::forInvalidConfiguration('Database connections configuration must be iterable.');
        }

        foreach ($connections as $name => $connection) {
            if ($connection instanceof ConnectionDefinition) {
                $registry->register($connection, $connection->isDefault());

                continue;
            }

            if (!is_array($connection)) {
                throw ConnectionException::forInvalidConfiguration('Each database connection must be an array definition.');
            }

            $connectionName = is_string($name) && !is_numeric($name)
                ? $name
                : ($connection['name'] ?? ($registry->count() === 0 ? ConnectionDefinition::DEFAULT_NAME : 'connection_' . ($registry->count() + 1)));

            if (!is_string($connectionName)) {
                throw ConnectionException::forInvalidConfiguration('Database connection names must be strings.');
            }

            $definition = ConnectionDefinition::fromConfig($connection, $connectionName);
            $registry->register($definition, $definition->isDefault());
        }

        return $registry;
    }

    /**
     * @param class-string<DatabaseDriverInterface>|DatabaseDriverInterface $driver
     * @param array<string|int, mixed> $options
     */
    public function add(
        string $name,
        string|DatabaseDriverInterface $driver,
        array $options = [],
        bool $default = false,
    ): self {
        return $this->register(new ConnectionDefinition($name, $driver, $options, $default), $default);
    }

    public function register(ConnectionDefinition $definition, bool $default = false): self
    {
        $name = $definition->name();

        if ($this->has($name)) {
            throw ConnectionExistsException::forName($name);
        }

        $this->definitions[$name] = $definition;

        if ($definition->hasDriverInstance()) {
            $driver = $definition->driverInstance();

            if ($driver !== null) {
                $this->drivers[$name] = $driver;
            }
        }

        if ($this->defaultName === null || $default || $definition->isDefault()) {
            $this->defaultName = $name;
        }

        return $this;
    }

    public function remove(string $name): self
    {
        $name = ConnectionDefinition::normalizeName($name);
        unset($this->definitions[$name], $this->drivers[$name]);

        if ($this->defaultName === $name) {
            $this->defaultName = array_key_first($this->definitions);
        }

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->definitions[ConnectionDefinition::normalizeName($name)]);
    }

    public function definition(?string $name = null): ConnectionDefinition
    {
        $name = $this->resolveName($name);

        return $this->definitions[$name] ?? throw ConnectionNotFoundException::forName($name);
    }

    public function get(?string $name = null): DatabaseDriverInterface
    {
        $name = $this->resolveName($name);

        if (!isset($this->definitions[$name])) {
            throw ConnectionNotFoundException::forName($name);
        }

        $this->drivers[$name] ??= $this->createDriver($this->definitions[$name]);

        return $this->drivers[$name];
    }

    public function isResolved(string $name): bool
    {
        return isset($this->drivers[ConnectionDefinition::normalizeName($name)]);
    }

    public function setDefault(string $name): self
    {
        $this->defaultName = $this->definition($name)->name();

        return $this;
    }

    public function defaultName(): string
    {
        return $this->defaultName ?? throw ConnectionNotFoundException::forName(ConnectionDefinition::DEFAULT_NAME);
    }

    public function defaultDefinition(): ConnectionDefinition
    {
        return $this->definition($this->defaultName());
    }

    /**
     * @return array<string, ConnectionDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * @return array<string, DatabaseDriverInterface>
     */
    public function resolved(): array
    {
        return $this->drivers;
    }

    public function clear(): self
    {
        $this->definitions = [];
        $this->drivers = [];
        $this->defaultName = null;

        return $this;
    }

    public function count(): int
    {
        return count($this->definitions);
    }

    public function getIterator(): Traversable
    {
        yield from $this->definitions;
    }

    private function resolveName(?string $name): string
    {
        return $name === null
            ? $this->defaultName()
            : ConnectionDefinition::normalizeName($name);
    }

    private function createDriver(ConnectionDefinition $definition): DatabaseDriverInterface
    {
        $driverClass = $definition->driverClass();

        try {
            return new $driverClass(...$definition->options());
        } catch (Throwable $throwable) {
            throw ConnectionException::forCreation($definition->name(), $driverClass, $throwable);
        }
    }
}
