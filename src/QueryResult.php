<?php

declare(strict_types=1);

namespace CommonPHP\Database;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, mixed>
 */
final class QueryResult implements Countable, IteratorAggregate
{
    /**
     * @var list<mixed>
     */
    private array $rows;

    /**
     * @param iterable<mixed> $rows
     */
    public function __construct(
        iterable $rows = [],
        private readonly int $affectedRows = 0,
        private readonly string|false|null $lastInsertId = null,
    ) {
        $this->rows = [];

        foreach ($rows as $row) {
            $this->rows[] = $row;
        }
    }

    /**
     * @param iterable<mixed> $rows
     */
    public static function rows(iterable $rows, int $affectedRows = 0): self
    {
        return new self($rows, $affectedRows);
    }

    public static function affected(int $affectedRows, string|false|null $lastInsertId = null): self
    {
        return new self([], $affectedRows, $lastInsertId);
    }

    /**
     * @return list<mixed>
     */
    public function all(): array
    {
        return $this->rows;
    }

    public function first(mixed $default = false): mixed
    {
        return $this->rows[0] ?? $default;
    }

    public function scalar(mixed $default = null): mixed
    {
        $row = $this->first(null);

        if ($row === null) {
            return $default;
        }

        if (is_array($row)) {
            foreach ($row as $value) {
                return $value;
            }

            return $default;
        }

        if (is_object($row)) {
            foreach (get_object_vars($row) as $value) {
                return $value;
            }

            return $default;
        }

        return $row;
    }

    public function isEmpty(): bool
    {
        return $this->rows === [];
    }

    public function affectedRows(): int
    {
        return $this->affectedRows;
    }

    public function lastInsertId(): string|false|null
    {
        return $this->lastInsertId;
    }

    public function count(): int
    {
        return count($this->rows);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->rows);
    }
}
