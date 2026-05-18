# Usage

The package has three common usage styles: direct `DatabaseManager` methods, prepared `Query` objects, and lower-level driver access for engine-specific behavior.

## Manager Methods

`DatabaseManager` is the primary entry point.

```php
use CommonPHP\Database\DatabaseManager;

$database = new DatabaseManager();
$database->connect('main', AppDatabaseDriver::class, [
    'database' => 'app',
]);

$rows = $database->fetchAll('select * from users');
$count = $database->count('select * from users');
$id = $database->lastInsertId();
```

The manager delegates to the selected driver and wraps unexpected query failures in database exceptions.

## Multiple Connections

```php
$database
    ->connect('main', AppDatabaseDriver::class, $mainOptions, default: true)
    ->connect('reporting', AppDatabaseDriver::class, $reportingOptions);

$liveUsers = $database->fetchAll('select * from users');
$reportRows = $database->fetchAll('select * from daily_user_report', connection: 'reporting');
```

When no connection is provided, the default connection is used.

## Prepared Query Objects

`prepare()` creates an immutable query object that can be bound, moved to another connection, and executed later.

```php
$query = $database
    ->prepare('select * from users where role = :role')
    ->bind('role', 'admin');

$rows = $query->fetchAll();
```

Changing parameters or connections returns a new `Query` instance:

```php
$admins = $query->bind('role', 'admin');
$editors = $query->bind('role', 'editor');
```

## Query Results

Drivers return arrays for `fetchOne()` and `fetchAll()`. Code that needs a tiny wrapper can call `Query::result()`.

```php
$result = $database
    ->prepare('select id, name from users')
    ->result();

$firstRow = $result->first();
$firstValue = $result->scalar();
```

`QueryResult` is iterable and countable.

## Driver Access

Use `with()` when an integration needs the driver itself:

```php
$driver = $database->with('main');

if ($driver->ping()) {
    // The driver says the connection is available.
}
```

Keep engine-specific APIs inside driver packages. Application code should prefer manager methods unless it truly needs driver-only behavior.
