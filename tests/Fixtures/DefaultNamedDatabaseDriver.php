<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Fixtures;

use CommonPHP\Database\Contracts\AbstractDatabaseDriver;
use CommonPHP\Database\Enums\FetchMode;

final class DefaultNamedDatabaseDriver extends AbstractDatabaseDriver
{
    public function execute(string $query, array $parameters = []): int|bool
    {
        return true;
    }

    public function fetchOne(string $query, array $parameters = []): array|false
    {
        return false;
    }

    public function fetchAll(
        string $query,
        array $parameters = [],
        FetchMode $fetchMode = FetchMode::FETCH_ASSOC,
    ): array {
        return [];
    }

    public function beginTransaction(): void
    {
    }

    public function commit(): void
    {
    }

    public function rollBack(): void
    {
    }

    public function lastInsertId(): string|false
    {
        return false;
    }

    public function ping(): bool
    {
        return true;
    }
}
