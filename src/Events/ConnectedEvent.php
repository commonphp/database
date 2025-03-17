<?php

namespace Neuron\Database\Events;

use Neuron\Database\DatabaseDriverInterface;
use Neuron\Events\AbstractEvent;

class ConnectedEvent extends AbstractEvent
{
    public function __construct(string $name, DatabaseDriverInterface $connection)
    {
        parent::__construct([
            'name' => $name,
            'connection' => $connection
        ]);
    }
}