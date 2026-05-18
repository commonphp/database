# Drivers

Database drivers adapt the core database contracts to a real database engine or client library.

The core package does not include a concrete database engine driver.

## Contract

Drivers implement `DatabaseDriverInterface`.

```php
namespace CommonPHP\Database\Contracts;

interface DatabaseDriverInterface extends DriverInterface
{
    public function count(string $query, array $parameters = []): int;
    public function execute(string $query, array $parameters = []): int|bool;
    public function fetchScalar(string $query, array $parameters = [], mixed $default = null): mixed;
    public function fetchOne(string $query, array $parameters = []): array|false;
    public function fetchAll(string $query, array $parameters = [], FetchMode $fetchMode = FetchMode::FETCH_ASSOC): array;
    public function transaction(callable $callback): mixed;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollBack(): void;
    public function lastInsertId(): string|false;
    public function ping(): bool;
}
```

## AbstractDatabaseDriver

`AbstractDatabaseDriver` provides useful defaults:

- `getName()` returns `static::class`;
- `prepare()` creates an executable `Query`;
- `count()` counts `fetchAll()` results;
- `fetchScalar()` reads the first value from `fetchOne()`;
- `transaction()` delegates to `Transaction::run()`;
- protected `parameterType()` detects named, positional, or empty parameters;
- protected `fetchModeValue()` returns the enum value.

Drivers can override defaults when the underlying engine has a more efficient implementation.

## Minimal Driver Shape

```php
use CommonPHP\Database\Contracts\AbstractDatabaseDriver;
use CommonPHP\Database\Enums\FetchMode;

final class ExampleDatabaseDriver extends AbstractDatabaseDriver
{
    public function __construct(
        private readonly ExampleClient $client,
    ) {
    }

    public function execute(string $query, array $parameters = []): int|bool
    {
        return $this->client->execute($query, $parameters);
    }

    public function fetchOne(string $query, array $parameters = []): array|false
    {
        return $this->client->fetchOne($query, $parameters);
    }

    public function fetchAll(
        string $query,
        array $parameters = [],
        FetchMode $fetchMode = FetchMode::FETCH_ASSOC,
    ): array {
        return $this->client->fetchAll($query, $parameters, $fetchMode->value);
    }

    public function beginTransaction(): void
    {
        $this->client->beginTransaction();
    }

    public function commit(): void
    {
        $this->client->commit();
    }

    public function rollBack(): void
    {
        $this->client->rollBack();
    }

    public function lastInsertId(): string|false
    {
        return $this->client->lastInsertId();
    }

    public function ping(): bool
    {
        return $this->client->ping();
    }
}
```

## Driver Responsibilities

Drivers should own:

- connection strings and client setup;
- parameter binding;
- engine-specific fetch mode mapping;
- transaction calls;
- driver-specific exception conversion;
- any query compilation that belongs to that engine.

Drivers should not own:

- application runtime bootstrapping;
- HTTP request parsing;
- validation;
- authentication;
- session lifecycle;
- UI rendering.
