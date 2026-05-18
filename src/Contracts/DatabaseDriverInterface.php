<?php

declare(strict_types=1);

namespace CommonPHP\Database\Contracts;

use CommonPHP\Database\Enums\FetchMode;
use CommonPHP\Runtime\Contracts\DriverInterface;

interface DatabaseDriverInterface extends DriverInterface
{
    public function count(string $query, array $parameters = []): int;

    public function execute(string $query, array $parameters = []): int|bool;

    public function fetchScalar(string $query, array $parameters = [], mixed $default = null): mixed;

    /**
     * @return array<string|int, mixed>|false
     */
    public function fetchOne(string $query, array $parameters = []): array|false;

    /**
     * @return list<array<string|int, mixed>|object|scalar|null>
     */
    public function fetchAll(
        string $query,
        array $parameters = [],
        FetchMode $fetchMode = FetchMode::FETCH_ASSOC,
    ): array;

    public function transaction(callable $callback): mixed;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    public function lastInsertId(): string|false;

    public function ping(): bool;
}
