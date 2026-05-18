<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Fixtures;

use CommonPHP\Database\Contracts\AbstractDatabaseDriver;
use CommonPHP\Database\Enums\FetchMode;
use Throwable;

final class ThrowingDatabaseDriver extends AbstractDatabaseDriver
{
    public function __construct(
        private readonly Throwable $throwable,
        private readonly ?string $operation = null,
    ) {
    }

    public function getName(): string
    {
        return 'throwing';
    }

    public function execute(string $query, array $parameters = []): int|bool
    {
        $this->throwIf('execute');

        return true;
    }

    public function fetchOne(string $query, array $parameters = []): array|false
    {
        $this->throwIf('fetchOne');

        return ['value' => 1];
    }

    public function fetchAll(
        string $query,
        array $parameters = [],
        FetchMode $fetchMode = FetchMode::FETCH_ASSOC,
    ): array {
        $this->throwIf('fetchAll');

        return [['value' => 1]];
    }

    public function beginTransaction(): void
    {
        $this->throwIf('beginTransaction');
    }

    public function commit(): void
    {
        $this->throwIf('commit');
    }

    public function rollBack(): void
    {
        $this->throwIf('rollBack');
    }

    public function lastInsertId(): string|false
    {
        $this->throwIf('lastInsertId');

        return '1';
    }

    public function ping(): bool
    {
        $this->throwIf('ping');

        return true;
    }

    private function throwIf(string $operation): void
    {
        if ($this->operation === null || $this->operation === $operation) {
            throw $this->throwable;
        }
    }
}
