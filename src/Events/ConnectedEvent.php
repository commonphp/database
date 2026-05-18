<?php

declare(strict_types=1);

namespace CommonPHP\Database\Events;

use CommonPHP\Database\ConnectionDefinition;
use CommonPHP\Database\Contracts\DatabaseDriverInterface;
use CommonPHP\Runtime\Contracts\AbstractEvent;

final class ConnectedEvent extends AbstractEvent
{
    public function __construct(
        public readonly string $connectionName,
        public readonly DatabaseDriverInterface $driver,
        public readonly ?ConnectionDefinition $definition = null,
    ) {
    }
}
