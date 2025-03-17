<?php

namespace Neuron\Database\Exceptions;

use Neuron\Database\DatabaseException;
use Throwable;

class DriverNotNamedException extends DatabaseException
{
    public function __construct(int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('A connection exists with no name.', $code, $previous);
    }
}