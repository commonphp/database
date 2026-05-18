# Custom Driver

```php
<?php

declare(strict_types=1);

use CommonPHP\Database\Contracts\AbstractDatabaseDriver;
use CommonPHP\Database\Enums\FetchMode;

final class ArrayBackedDatabaseDriver extends AbstractDatabaseDriver
{
    public function __construct(
        private array $rows = [],
        private string|false $lastId = false,
    ) {
    }

    public function execute(string $query, array $parameters = []): int|bool
    {
        return true;
    }

    public function fetchOne(string $query, array $parameters = []): array|false
    {
        return $this->rows[$query][0] ?? false;
    }

    public function fetchAll(
        string $query,
        array $parameters = [],
        FetchMode $fetchMode = FetchMode::FETCH_ASSOC,
    ): array {
        return $this->rows[$query] ?? [];
    }

    public function beginTransaction(): void
    {
    }

    public function commit(): void
    {
    }

    public function rollBack(): void
    {
    }

    public function lastInsertId(): string|false
    {
        return $this->lastId;
    }

    public function ping(): bool
    {
        return true;
    }
}
```

This example is intentionally tiny. Real drivers should convert engine/client failures into database exceptions and map `FetchMode` to the underlying library.
