<?php

namespace Neuron\Database;

use Neuron\Database\Drivers\AliasDatabaseDriver;
use Neuron\Database\Drivers\MysqlDatabaseDriver;
use Neuron\Database\Events\QueryExecutedEvent;
use Neuron\Extensibility\ExtensionsInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final class DatabaseManager implements DatabaseInterface
{
    public readonly ConnectionStore $connections;

    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;

    private bool $profiling = false;

    public function __construct(LoggerInterface $logger, ExtensionsInterface $extensions, EventDispatcherInterface $eventDispatcher)
    {
        $extensions->getTypeRegistry()->register(DatabaseDriver::class);
        $extensions->getRegistry()->register(DatabaseDriver::class, AliasDatabaseDriver::class);
        $extensions->getRegistry()->register(DatabaseDriver::class, MysqlDatabaseDriver::class);

        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->connections = new ConnectionStore($logger, $extensions, $eventDispatcher);
    }

    /**
     * @inheritDoc
     */
    public function getConnectionStore(): ConnectionStore
    {
        return $this->connections;
    }

    /**
     * @inheritDoc
     */
    public function connect(string $name, string $driverClass, array $options = []): void
    {
        $this->connections->add($name, $driverClass, $options);
    }

    /**
     * @inheritDoc
     */
    public function with(string $connection): DatabaseDriverInterface
    {
        return $this->connections->get($connection);
    }

    /**
     * @inheritDoc
     */
    public function prepare(string $query, array $parameters = [], string $connection = '_default_'): Query
    {
        return new Query($query, $parameters, $this->with($connection));
    }

    /**
     * @inheritDoc
     */
    public function count(string $query, array $parameters = [], string $connection = '_default_'): int
    {
        return $this->with($connection)->count($query, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function execute(string $query, array $parameters = [], string $connection = '_default_'): int|bool
    {
        return $this->with($connection)->execute($query, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function fetchScalar(string $query, array $parameters = [], mixed $default = null, string $connection = '_default_'): mixed
    {
        return $this->with($connection)->fetchScalar($query, $parameters, $default);
    }

    /**
     * @inheritDoc
     */
    public function fetchOne(string $query, array $parameters = [], string $connection = '_default_'): array|false
    {
        return $this->with($connection)->fetchOne($query, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function fetchAll(string $query, array $parameters = [], FetchMode $fetchMode = FetchMode::FETCH_ASSOC, string $connection = '_default_'): array
    {
        return $this->with($connection)->fetchAll($query, $parameters, $fetchMode);
    }

    /**
     * @inheritDoc
     */
    public function transaction(callable $callback, string $connection = '_default_'): mixed
    {
        return $this->with($connection)->transaction($callback);
    }

    /**
     * @inheritDoc
     */
    public function lastInsertId(string $connection = '_default_'): string|false
    {
        return $this->with($connection)->lastInsertId();
    }

    /**
     * @inheritDoc
     */
    public function ping(string $connection = '_default_'): bool
    {
        return $this->with($connection)->ping();
    }

    /**
     * @inheritDoc
     */
    public function profileQuery(string $action, string $query, array $parameters, DatabaseDriverInterface $connection, float $duration, array|false $errors): void
    {
        if (!$this->profiling) return;
        $this->logger->debug("[".$connection->getName()."] ".strtoupper($action).": $query", $parameters);
        $this->eventDispatcher->dispatch(new QueryExecutedEvent($action, $query, $parameters, $connection, $duration, $errors));
    }

    /**
     * @inheritDoc
     */
    public function enableProfiling(): void
    {
        $this->profiling = true;
    }

    /**
     * @inheritDoc
     */
    public function disableProfiling(): void
    {
        $this->profiling = false;
    }

    /**
     * @inheritDoc
     */
    public function isProfiling(): bool
    {
        return $this->profiling;
    }
}