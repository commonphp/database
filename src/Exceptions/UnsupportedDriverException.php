<?php

namespace Neuron\Database\Exceptions;

use Neuron\Database\DatabaseException;
use Throwable;

class UnsupportedDriverException extends DatabaseException
{
    public function __construct(string $driverClass, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('The database driver `'.$driverClass.'` is not a supported database driver', $code, $previous);
    }
}