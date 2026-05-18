<?php

declare(strict_types=1);

namespace CommonPHP\Database\Exceptions;

use Throwable;

class TransactionException extends DatabaseException
{
    public static function forConnection(string $connection, Throwable $previous): self
    {
        return new self(
            'Database transaction failed for connection "' . $connection . '".',
            previous: $previous,
        );
    }

    public static function forOperation(string $operation, string $connection, Throwable $previous): self
    {
        return new self(
            'Database transaction could not ' . $operation . ' for connection "' . $connection . '".',
            previous: $previous,
        );
    }

    public static function inactive(string $operation, string $connection): self
    {
        return new self(
            'Cannot ' . $operation . ' inactive database transaction for connection "' . $connection . '".',
        );
    }
}
