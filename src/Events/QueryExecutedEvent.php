<?php

namespace Neuron\Database\Events;

use Neuron\Database\DatabaseDriverInterface;
use Neuron\Events\AbstractEvent;

class QueryExecutedEvent extends AbstractEvent
{
    public function __construct(string $action, string $query, array $parameters, DatabaseDriverInterface $connection, float $duration, array|false $errors)
    {
        parent::__construct([
            'action' => $action,
            'query' => $query,
            'parameters' => $parameters,
            'connection' => $connection,
            'duration' => $duration,
            'errors' => $errors
        ]);
    }
}