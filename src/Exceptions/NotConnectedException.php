<?php

namespace Neuron\Database\Exceptions;

use Neuron\Database\DatabaseException;
use Throwable;

class NotConnectedException extends DatabaseException
{
    public function __construct(string $connectionName, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('The connection name `'.$connectionName.'` does not exist', $code, $previous);
    }
}