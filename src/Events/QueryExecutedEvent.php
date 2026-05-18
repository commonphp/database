<?php

declare(strict_types=1);

namespace CommonPHP\Database\Events;

use CommonPHP\Database\Contracts\DatabaseDriverInterface;
use CommonPHP\Runtime\Contracts\AbstractEvent;

final class QueryExecutedEvent extends AbstractEvent
{
    /**
     * @param array<string|int, mixed> $parameters
     * @param array<string, mixed>|false $errors
     */
    public function __construct(
        public readonly string $action,
        public readonly string $query,
        public readonly array $parameters,
        public readonly string $connectionName,
        public readonly DatabaseDriverInterface $driver,
        public readonly float $duration,
        public readonly array|false $errors = false,
    ) {
    }

    public function succeeded(): bool
    {
        return $this->errors === false;
    }

    public function failed(): bool
    {
        return !$this->succeeded();
    }
}
