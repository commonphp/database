# comphp/database

The **comphp/database** library provides a flexible, modular, and extensible way to manage database connections and execute queries in PHP. Designed to integrate seamlessly with the rest of the CommonPHP ecosystem, it supports:

- Multiple connections and drivers
- Alias-based connections
- Query profiling
- Event-dispatching for database operations
- PSR-3 logging
- Extensibility through the **comphp/extensible** library

## Features

- **Connection Management** – Centrally manage different database connections (e.g., MySQL, alias drivers, custom drivers, etc.).
- **Extensible Drivers** – Register new drivers via attributes to easily expand your supported databases.
- **Query Execution** – Execute queries with structured methods to fetch single rows, multiple rows, or scalars.
- **Transaction Handling** – Built-in methods for wrapping multiple queries in a transaction.
- **PSR-14 Events** – Emit events (`ConnectedEvent`, `QueryExecutedEvent`) for deeper integration.
- **Profiling & Logging** – Enable query profiling and leverage PSR-3 for structured logs.

## Installation

Install via Composer:

```bash
composer require comphp/database
```

## Getting Started

Below is a basic example using **DatabaseManager**:

```php
<?php

use Neuron\Database\DatabaseManager;
use Neuron\Database\Drivers\MysqlDatabaseDriver;
use Neuron\Extensibility\ExtensionStore;
use Neuron\Extensibility\InstantiatorInterface;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

// Imagine these are created or injected by your DI container
/** @var LoggerInterface $logger */
/** @var InstantiatorInterface $instantiator */
/** @var EventDispatcherInterface $eventDispatcher */

$extensions = new ExtensionStore($instantiator, $logger, $eventDispatcher);
$database = new DatabaseManager($logger, $extensions, $eventDispatcher);

// Create a new connection
$database->connect('_default_', MysqlDatabaseDriver::class, [
    'database'  => 'my_database',
    'host'      => 'localhost',
    'username'  => 'root',
    'password'  => 'root',
]);

// Fetch rows
$rows = $database->fetchAll('SELECT * FROM users');

foreach ($rows as $row) {
    echo "User: " . $row['name'] . "\n";
}
```

## Examples

See [examples/](examples) for usage demos:

- **basic-usage.php** – Initializing a connection and querying.
- **transaction-usage.php** – Wrapping multiple queries in transactions.
- **alias-driver-usage.php** – Creating an alias-based driver.
- **query-profiling.php** – Enabling profiling and listening to `QueryExecutedEvent`.
- **error-handling.php** – Handling exceptions gracefully.

## Usage

### Connecting to Multiple Databases

```php
$database->connect('mysql_main', MysqlDatabaseDriver::class, [
    'database' => 'main_db',
    'host' => 'localhost',
    'username' => 'root',
    'password' => ''
]);

$database->connect('sqlite_backup', AliasDatabaseDriver::class, [
    'database' => $database,
    'target' => 'mysql_main'
]);

// Use them
$backupRows = $database->fetchAll('SELECT * FROM logs', [], 'sqlite_backup');
```

### Transactions

```php
$database->transaction(function (\Neuron\Database\DatabaseDriverInterface $driver) {
    $driver->execute('INSERT INTO accounts ...');
    $driver->execute('UPDATE ledger ...');
});
```

### Profiling

```php
$database->enableProfiling();
// All queries now dispatch QueryExecutedEvent with timing info
```

## Driver Extensibility

`comphp/database` integrates with **comphp/extensible**. To create your own driver:

```php
use Neuron\Database\AbstractDatabaseDriver;
use Neuron\Database\DatabaseDriver;
use Neuron\Database\DatabaseDriverInterface;

#[DatabaseDriver]
class YourCustomDriver extends AbstractDatabaseDriver implements DatabaseDriverInterface
{
    // implement count(), fetchOne(), fetchAll(), etc.
}

// Then register
$extensions->getRegistry()->register(DatabaseDriver::class, YourCustomDriver::class);
```

## Testing

1. Install dev dependencies (including PHPUnit):
   ```bash
   composer install
   ```
2. Run tests:
   ```bash
   vendor/bin/phpunit
   ```

Check out [test/](test) for unit tests that demonstrate usage.

## Contributing

We welcome contributions! See [CONTRIBUTING.md](contributing.md) for guidelines.

## License

This project is released under the MIT License. See [LICENSE.md](license.md) for details.
