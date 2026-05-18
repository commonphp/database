<?php

declare(strict_types=1);

namespace CommonPHP\Database\Contracts;

use CommonPHP\Database\Enums\FetchMode;
use CommonPHP\Database\Enums\ParameterType;
use CommonPHP\Database\Query;
use CommonPHP\Database\Transaction;

abstract class AbstractDatabaseDriver implements DatabaseDriverInterface
{
    public function getName(): string
    {
        return static::class;
    }

    public function prepare(string $query, array $parameters = []): Query
    {
        return Query::forDriver($query, $parameters, $this);
    }

    public function count(string $query, array $parameters = []): int
    {
        return count($this->fetchAll($query, $parameters));
    }

    public function fetchScalar(string $query, array $parameters = [], mixed $default = null): mixed
    {
        $row = $this->fetchOne($query, $parameters);

        if ($row === false || $row === []) {
            return $default;
        }

        foreach ($row as $value) {
            return $value;
        }

        return $default;
    }

    public function transaction(callable $callback): mixed
    {
        return Transaction::run($this, $callback);
    }

    protected function parameterType(array $parameters): ParameterType
    {
        return ParameterType::detect($parameters);
    }

    protected function fetchModeValue(FetchMode $fetchMode): int
    {
        return $fetchMode->value;
    }
}
