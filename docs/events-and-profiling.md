# Events And Profiling

Database uses Runtime's `EventEmitterTrait` for lightweight package events.

## ConnectedEvent

`ConnectedEvent` is emitted when a lazily-defined connection is first resolved.

```php
use CommonPHP\Database\Events\ConnectedEvent;

$database->subscribe(ConnectedEvent::class, function (ConnectedEvent $event): void {
    $event->connectionName;
    $event->driver;
    $event->definition;
});
```

Connections registered with an already-created driver instance are already resolved, so they do not emit the lazy connection event on first `with()`.

## QueryExecutedEvent

`QueryExecutedEvent` is emitted only when profiling is enabled.

```php
use CommonPHP\Database\Events\QueryExecutedEvent;

$database->enableProfiling();

$database->subscribe(QueryExecutedEvent::class, function (QueryExecutedEvent $event): void {
    $event->action;
    $event->query;
    $event->parameters;
    $event->connectionName;
    $event->driver;
    $event->duration;
    $event->errors;
});
```

The event exposes `succeeded()` and `failed()` helpers.

## Profiling

Profiling is disabled by default.

```php
$database->enableProfiling();
$database->disableProfiling();
$database->isProfiling();
```

When profiling is enabled, the manager logs successful queries at debug level and failed queries at error level using the injected PSR-3 logger.

```php
$database = new DatabaseManager(logger: $logger);
$database->enableProfiling();
```

## Manual Profiling

Driver packages can use `profileQuery()` if they execute work outside the manager's direct query helpers.

```php
$database->profileQuery(
    action: 'bulk import',
    query: 'copy users from stdin',
    parameters: [],
    connection: $driver,
    duration: 0.25,
    errors: false,
    connectionName: 'main',
);
```

When profiling is disabled, `profileQuery()` returns without logging or emitting events.
