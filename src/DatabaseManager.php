<?php

declare(strict_types=1);

namespace CommonPHP\Database;

use CommonPHP\Database\Contracts\DatabaseDriverInterface;
use CommonPHP\Database\Contracts\DatabaseInterface;
use CommonPHP\Database\Enums\FetchMode;
use CommonPHP\Database\Events\ConnectedEvent;
use CommonPHP\Database\Events\QueryExecutedEvent;
use CommonPHP\Database\Exceptions\DatabaseDriverException;
use CommonPHP\Database\Exceptions\DatabaseException;
use CommonPHP\Database\Exceptions\QueryException;
use CommonPHP\Database\Exceptions\TransactionException;
use CommonPHP\Runtime\Contracts\EventEmitterTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

final class DatabaseManager implements DatabaseInterface
{
    use EventEmitterTrait;

    private bool $profiling = false;

    public function __construct(
        private ConnectionRegistry $connections = new ConnectionRegistry(),
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * @param class-string<DatabaseDriverInterface>|DatabaseDriverInterface $driver
     * @param array<string|int, mixed> $options
     */
    public static function connection(
        string $name,
        string|DatabaseDriverInterface $driver,
        array $options = [],
        bool $default = true,
    ): self {
        return (new self())->connect($name, $driver, $options, $default);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config, ?LoggerInterface $logger = null): self
    {
        return new self(ConnectionRegistry::fromConfig($config), $logger ?? new NullLogger());
    }

    /**
     * @param class-string<DatabaseDriverInterface>|DatabaseDriverInterface $driver
     * @param array<string|int, mixed> $options
     */
    public function connect(
        string $name,
        string|DatabaseDriverInterface $driver,
        array $options = [],
        bool $default = false,
    ): static {
        return $this->register(new ConnectionDefinition($name, $driver, $options, $default), $default);
    }

    public function register(ConnectionDefinition $definition, bool $default = false): static
    {
        $this->connections->register($definition, $default);

        return $this;
    }

    public function connections(): ConnectionRegistry
    {
        return $this->connections;
    }

    public function hasConnection(string $connection): bool
    {
        return $this->connections->has($connection);
    }

    public function with(?string $connection = null): DatabaseDriverInterface
    {
        $name = $this->resolveConnectionName($connection);
        $wasResolved = $this->connections->isResolved($name);
        $driver = $this->connections->get($name);

        if (!$wasResolved) {
            $this->emit(new ConnectedEvent($name, $driver, $this->connections->definition($name)));
        }

        return $driver;
    }

    public function prepare(string $query, array $parameters = [], ?string $connection = null): Query
    {
        return new Query($query, $parameters, $this, $connection);
    }

    public function count(string $query, array $parameters = [], ?string $connection = null): int
    {
        return $this->runQuery(
            'count',
            $query,
            $parameters,
            $connection,
            static fn (DatabaseDriverInterface $driver): int => $driver->count($query, $parameters),
        );
    }

    public function execute(string $query, array $parameters = [], ?string $connection = null): int|bool
    {
        return $this->runQuery(
            'execute',
            $query,
            $parameters,
            $connection,
            static fn (DatabaseDriverInterface $driver): int|bool => $driver->execute($query, $parameters),
        );
    }

    public function fetchScalar(
        string $query,
        array $parameters = [],
        mixed $default = null,
        ?string $connection = null,
    ): mixed {
        return $this->runQuery(
            'fetch scalar',
            $query,
            $parameters,
            $connection,
            static fn (DatabaseDriverInterface $driver): mixed => $driver->fetchScalar($query, $parameters, $default),
        );
    }

    public function fetchOne(string $query, array $parameters = [], ?string $connection = null): array|false
    {
        return $this->runQuery(
            'fetch one',
            $query,
            $parameters,
            $connection,
            static fn (DatabaseDriverInterface $driver): array|false => $driver->fetchOne($query, $parameters),
        );
    }

    public function fetchAll(
        string $query,
        array $parameters = [],
        FetchMode $fetchMode = FetchMode::FETCH_ASSOC,
        ?string $connection = null,
    ): array {
        return $this->runQuery(
            'fetch all',
            $query,
            $parameters,
            $connection,
            static fn (DatabaseDriverInterface $driver): array => $driver->fetchAll($query, $parameters, $fetchMode),
        );
    }

    public function transaction(callable $callback, ?string $connection = null): mixed
    {
        $name = $this->resolveConnectionName($connection);
        $driver = $this->with($name);

        try {
            return $driver->transaction($callback);
        } catch (DatabaseException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw TransactionException::forConnection($name, $throwable);
        }
    }

    public function lastInsertId(?string $connection = null): string|false
    {
        return $this->runDriverOperation(
            'last insert id',
            $connection,
            static fn (DatabaseDriverInterface $driver): string|false => $driver->lastInsertId(),
        );
    }

    public function ping(?string $connection = null): bool
    {
        return $this->runDriverOperation(
            'ping',
            $connection,
            static fn (DatabaseDriverInterface $driver): bool => $driver->ping(),
        );
    }

    public function enableProfiling(): static
    {
        $this->profiling = true;

        return $this;
    }

    public function disableProfiling(): static
    {
        $this->profiling = false;

        return $this;
    }

    public function isProfiling(): bool
    {
        return $this->profiling;
    }

    /**
     * @param array<string|int, mixed> $parameters
     * @param array<string, mixed>|false $errors
     */
    public function profileQuery(
        string $action,
        string $query,
        array $parameters,
        DatabaseDriverInterface $connection,
        float $duration,
        array|false $errors = false,
        ?string $connectionName = null,
    ): void {
        if (!$this->profiling) {
            return;
        }

        $connectionName ??= $connection->getName();
        $event = new QueryExecutedEvent($action, $query, $parameters, $connectionName, $connection, $duration, $errors);
        $context = [
            'connection' => $connectionName,
            'driver' => $connection->getName(),
            'query' => $query,
            'parameters' => $parameters,
            'duration' => $duration,
            'errors' => $errors,
        ];

        if ($errors === false) {
            $this->logger->debug('Database query executed.', $context);
        } else {
            $this->logger->error('Database query failed.', $context);
        }

        $this->emit($event);
    }

    private function resolveConnectionName(?string $connection): string
    {
        return $connection === null
            ? $this->connections->defaultName()
            : ConnectionDefinition::normalizeName($connection);
    }

    /**
     * @param array<string|int, mixed> $parameters
     */
    private function runQuery(
        string $action,
        string $query,
        array $parameters,
        ?string $connection,
        callable $callback,
    ): mixed {
        $name = $this->resolveConnectionName($connection);
        $driver = $this->with($name);
        $start = microtime(true);
        $errors = false;

        try {
            return $callback($driver);
        } catch (DatabaseException $exception) {
            $errors = $this->errorContext($exception);

            throw $exception;
        } catch (Throwable $throwable) {
            $errors = $this->errorContext($throwable);

            throw QueryException::forOperation($action, $query, $throwable);
        } finally {
            $this->profileQuery($action, $query, $parameters, $driver, microtime(true) - $start, $errors, $name);
        }
    }

    private function runDriverOperation(?string $operation, ?string $connection, callable $callback): mixed
    {
        $name = $this->resolveConnectionName($connection);
        $driver = $this->with($name);

        try {
            return $callback($driver);
        } catch (DatabaseException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw DatabaseDriverException::forOperation($operation ?? 'operate', $name, $throwable);
        }
    }

    /**
     * @return array{exception: class-string<Throwable>, message: string, code: int|string}
     */
    private function errorContext(Throwable $throwable): array
    {
        return [
            'exception' => $throwable::class,
            'message' => $throwable->getMessage(),
            'code' => $throwable->getCode(),
        ];
    }
}
