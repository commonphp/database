<?php

namespace Neuron\Database;

use Exception;
use Throwable;

/**
 * Base exception class for database-related errors.
 */
class DatabaseException extends Exception
{
    /**
     * Constructs a DatabaseException.
     *
     * @param string $message Exception message.
     * @param int $code Exception code.
     * @param Throwable|null $previous Previous exception.
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}