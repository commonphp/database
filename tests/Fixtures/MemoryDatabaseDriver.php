<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Fixtures;

use CommonPHP\Database\Contracts\AbstractDatabaseDriver;
use CommonPHP\Database\Enums\FetchMode;

class MemoryDatabaseDriver extends AbstractDatabaseDriver
{
    /**
     * @var list<array{action: string, query?: string, parameters?: array<string|int, mixed>}>
     */
    public array $log = [];

    public int $began = 0;

    public int $committed = 0;

    public int $rolledBack = 0;

    public ?FetchMode $lastFetchMode = null;

    /**
     * @param array<string, list<array<string|int, mixed>>> $rows
     */
    public function __construct(
        private array $rows = [],
        private string|false $insertId = '1',
        private bool $alive = true,
    ) {
    }

    public function getName(): string
    {
        return 'memory';
    }

    public function execute(string $query, array $parameters = []): int|bool
    {
        $this->log[] = ['action' => 'execute', 'query' => $query, 'parameters' => $parameters];

        return 1;
    }

    public function fetchOne(string $query, array $parameters = []): array|false
    {
        $this->log[] = ['action' => 'fetch one', 'query' => $query, 'parameters' => $parameters];

        return $this->rows[$query][0] ?? false;
    }

    public function fetchAll(
        string $query,
        array $parameters = [],
        FetchMode $fetchMode = FetchMode::FETCH_ASSOC,
    ): array {
        $this->log[] = ['action' => 'fetch all', 'query' => $query, 'parameters' => $parameters];
        $this->lastFetchMode = $fetchMode;

        return $this->rows[$query] ?? [];
    }

    public function beginTransaction(): void
    {
        ++$this->began;
    }

    public function commit(): void
    {
        ++$this->committed;
    }

    public function rollBack(): void
    {
        ++$this->rolledBack;
    }

    public function lastInsertId(): string|false
    {
        return $this->insertId;
    }

    public function ping(): bool
    {
        return $this->alive;
    }

}
