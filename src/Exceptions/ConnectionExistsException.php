<?php

declare(strict_types=1);

namespace CommonPHP\Database\Exceptions;

class ConnectionExistsException extends DatabaseException
{
    public static function forName(string $name): self
    {
        return new self('Database connection "' . $name . '" already exists.');
    }
}
