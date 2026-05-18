# Transactions

```php
<?php

declare(strict_types=1);

$database->transaction(function ($connection): void {
    $connection->execute('insert into users (name, email) values (:name, :email)', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $connection->execute('insert into audit_log (message) values (:message)', [
        'message' => 'Created user ada@example.com',
    ]);
});
```

Target a named connection when the work belongs somewhere other than the default connection:

```php
$database->transaction(function ($connection): void {
    $connection->execute('delete from staging_imports where imported = 1');
}, connection: 'reporting');
```

If the callback throws, the helper rolls the transaction back.
