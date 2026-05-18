<?php

declare(strict_types=1);

namespace CommonPHP\Database\Contracts;

use CommonPHP\Database\ConnectionDefinition;
use CommonPHP\Database\ConnectionRegistry;
use CommonPHP\Database\Enums\FetchMode;
use CommonPHP\Database\Query;

interface DatabaseInterface
{
    public const DEFAULT_CONNECTION = ConnectionDefinition::DEFAULT_NAME;

    /**
     * @param class-string<DatabaseDriverInterface>|DatabaseDriverInterface $driver
     * @param array<string|int, mixed> $options
     */
    public function connect(
        string $name,
        string|DatabaseDriverInterface $driver,
        array $options = [],
        bool $default = false,
    ): static;

    public function register(ConnectionDefinition $definition, bool $default = false): static;

    public function connections(): ConnectionRegistry;

    public function hasConnection(string $connection): bool;

    public function with(?string $connection = null): DatabaseDriverInterface;

    public function prepare(string $query, array $parameters = [], ?string $connection = null): Query;

    public function count(string $query, array $parameters = [], ?string $connection = null): int;

    public function execute(string $query, array $parameters = [], ?string $connection = null): int|bool;

    public function fetchScalar(
        string $query,
        array $parameters = [],
        mixed $default = null,
        ?string $connection = null,
    ): mixed;

    /**
     * @return array<string|int, mixed>|false
     */
    public function fetchOne(string $query, array $parameters = [], ?string $connection = null): array|false;

    /**
     * @return list<array<string|int, mixed>|object|scalar|null>
     */
    public function fetchAll(
        string $query,
        array $parameters = [],
        FetchMode $fetchMode = FetchMode::FETCH_ASSOC,
        ?string $connection = null,
    ): array;

    public function transaction(callable $callback, ?string $connection = null): mixed;

    public function lastInsertId(?string $connection = null): string|false;

    public function ping(?string $connection = null): bool;
}
