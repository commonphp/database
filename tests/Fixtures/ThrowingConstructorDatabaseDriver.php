<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Fixtures;

use RuntimeException;

final class ThrowingConstructorDatabaseDriver extends MemoryDatabaseDriver
{
    public function __construct()
    {
        throw new RuntimeException('constructor failed');
    }
}
