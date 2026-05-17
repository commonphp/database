<?php

declare(strict_types=1);

namespace CommonPHP\Database;

use CommonPHP\Database\Contracts\DatabaseInterface;
use CommonPHP\Runtime\Contracts\DriverPoolTrait;

class DatabaseManager implements DatabaseInterface
{
    use DriverPoolTrait;
}