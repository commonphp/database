# Profiling

```php
<?php

declare(strict_types=1);

use CommonPHP\Database\Events\QueryExecutedEvent;

$database->enableProfiling();

$database->subscribe(QueryExecutedEvent::class, function (QueryExecutedEvent $event): void {
    if ($event->failed()) {
        error_log('Database query failed on ' . $event->connectionName);

        return;
    }

    error_log(sprintf(
        '[%s] %s took %.4f seconds',
        $event->connectionName,
        $event->action,
        $event->duration,
    ));
});

$database->fetchAll('select * from users');
```

Profiling logs through the manager's PSR-3 logger and emits `QueryExecutedEvent` for subscribers.
