# Transactions

Transactions are owned by the driver, with a small core helper for consistent commit and rollback behavior.

## Manager Transactions

```php
$database->transaction(function ($connection): void {
    $connection->execute('insert into accounts (name) values (:name)', [
        'name' => 'Primary',
    ]);

    $connection->execute('insert into audit_log (message) values (:message)', [
        'message' => 'Account created',
    ]);
});
```

The manager selects the default connection unless a connection name is provided.

```php
$database->transaction($callback, connection: 'reporting');
```

## Driver Transactions

Drivers implement:

- `beginTransaction()`
- `commit()`
- `rollBack()`
- `transaction(callable $callback)`

`AbstractDatabaseDriver::transaction()` delegates to `Transaction::run()`.

## Transaction Helper

```php
use CommonPHP\Database\Transaction;

$transaction = Transaction::begin($driver);

try {
    $driver->execute('insert into users (name) values (:name)', [
        'name' => 'Ada',
    ]);

    $transaction->commit();
} catch (Throwable $throwable) {
    if ($transaction->isActive()) {
        $transaction->rollBack();
    }

    throw $throwable;
}
```

The helper exposes:

- `driver()`
- `isActive()`
- `isCompleted()`
- `commit()`
- `rollBack()`

## Automatic Commit And Rollback

`Transaction::run()` begins the transaction, invokes the callback, commits on success, and rolls back on failure.

```php
$result = Transaction::run($driver, function ($connection, Transaction $transaction): string {
    $connection->execute('update users set active = 1');

    return 'done';
});
```

Runtime failures from the callback are wrapped in `TransactionException`. Existing database exceptions are rolled back and rethrown.
