<?php

declare(strict_types=1);

namespace CommonPHP\Database\Exceptions;

use CommonPHP\Database\Contracts\DatabaseDriverInterface;
use Throwable;

class DatabaseDriverException extends DatabaseException
{
    public static function forClass(string $driverClass): self
    {
        return new self('Database driver "' . $driverClass . '" must implement ' . DatabaseDriverInterface::class . '.');
    }

    public static function forOperation(string $operation, string $connection, Throwable $previous): self
    {
        return new self(
            'Database driver operation "' . $operation . '" failed for connection "' . $connection . '".',
            previous: $previous,
        );
    }
}
