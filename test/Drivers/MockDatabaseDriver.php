<?php

namespace NeuronTests\Database\Drivers;

use Neuron\Database\AbstractDatabaseDriver;
use Neuron\Database\DatabaseDriver;
use Neuron\Database\DatabaseDriverInterface;
use Neuron\Database\FetchMode;

#[DatabaseDriver]
class MockDatabaseDriver extends AbstractDatabaseDriver implements DatabaseDriverInterface
{
    public function count(string $query, array $parameters = []): int
    {
        return 1;
    }

    public function execute(string $query, array $parameters = []): int|bool
    {
        return 123; // e.g. pretend an INSERT returned lastInsertId = 123
    }

    public function fetchScalar(string $query, array $parameters = [], mixed $default = null): mixed
    {
        return 'fake-scalar';
    }

    public function fetchOne(string $query, array $parameters = []): array|false
    {
        return ['id' => 1, 'name' => 'Fake John Doe'];
    }

    public function fetchAll(string $query, array $parameters = [], FetchMode $fetchMode = FetchMode::FETCH_ASSOC): array
    {
        return [
            ['id' => 1, 'name' => 'Fake John Doe'],
            ['id' => 2, 'name' => 'Fake Jane Doe']
        ];
    }

    public function transaction(callable $callback): mixed
    {
        // Simulate a transaction by just invoking callback
        return $callback($this);
    }

    public function lastInsertId(): string|false
    {
        return '123';
    }

    public function ping(): bool
    {
        return true;
    }
}
