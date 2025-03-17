<?php

namespace Neuron\Database\Exceptions;

use Neuron\Database\DatabaseException;
use Throwable;

class DuplicateNameException extends DatabaseException
{
    public function __construct(string $connectionName, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('The connection name `'.$connectionName.'` is already in use', $code, $previous);
    }
}