<?php

declare(strict_types=1);

namespace CommonPHP\Database\Exceptions;

use Throwable;

class QueryException extends DatabaseException
{
    public static function forOperation(string $operation, string $query, Throwable $previous): self
    {
        return new self(
            'Database query operation "' . $operation . '" failed for query: ' . self::summarize($query),
            previous: $previous,
        );
    }

    public static function notExecutable(string $query): self
    {
        return new self('Database query has no manager or driver to execute it: ' . self::summarize($query));
    }

    private static function summarize(string $query): string
    {
        $query = trim(preg_replace('/\s+/', ' ', $query) ?? $query);

        return strlen($query) > 160 ? substr($query, 0, 157) . '...' : $query;
    }
}
