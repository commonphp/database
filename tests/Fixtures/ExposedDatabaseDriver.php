<?php

declare(strict_types=1);

namespace CommonPHP\Database\Tests\Fixtures;

use CommonPHP\Database\Enums\FetchMode;
use CommonPHP\Database\Enums\ParameterType;

final class ExposedDatabaseDriver extends MemoryDatabaseDriver
{
    public function exposedParameterType(array $parameters): ParameterType
    {
        return $this->parameterType($parameters);
    }

    public function exposedFetchModeValue(FetchMode $fetchMode): int
    {
        return $this->fetchModeValue($fetchMode);
    }
}
