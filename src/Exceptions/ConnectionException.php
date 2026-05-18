<?php

declare(strict_types=1);

namespace CommonPHP\Database\Exceptions;

use Throwable;

class ConnectionException extends DatabaseException
{
    public static function forCreation(string $name, string $driverClass, Throwable $previous): self
    {
        return new self(
            'Database connection "' . $name . '" could not create driver "' . $driverClass . '".',
            previous: $previous,
        );
    }

    public static function forInvalidConfiguration(string $message): self
    {
        return new self($message);
    }

    public static function forOperation(string $operation, string $name, Throwable $previous): self
    {
        return new self(
            'Database connection "' . $name . '" failed during "' . $operation . '".',
            previous: $previous,
        );
    }
}
