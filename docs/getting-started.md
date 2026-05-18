# Getting Started

CommonPHP Database gives applications a small, engine-neutral API for named database connections and query execution.

## Install

```bash
composer require comphp/database
```

Install a database driver package separately. The core package defines the manager, contracts, events, result helpers, and exceptions; it does not open MySQL, SQL Server, or other engine connections by itself.

In this monorepo, the package is also available through the workspace path repository and the root Composer autoloader.

## Create A Manager

```php
<?php

declare(strict_types=1);

use CommonPHP\Database\DatabaseManager;
use Vendor\Database\ExampleDatabaseDriver;

$database = DatabaseManager::connection('main', ExampleDatabaseDriver::class, [
    'host' => '127.0.0.1',
    'dbname' => 'app',
    'username' => 'app',
    'password' => 'secret',
]);
```

Driver instances are created lazily. The driver class is not constructed until the connection is used.

## Run Queries

```php
$users = $database->fetchAll('select * from users where active = :active', [
    'active' => true,
]);

$name = $database->fetchScalar('select name from users where id = :id', [
    'id' => 1,
], default: 'unknown');
```

Use `prepare()` when a query is passed around before execution:

```php
$query = $database
    ->prepare('select * from users where id = :id')
    ->bind('id', 1);

$user = $query->fetchOne();
```

## Use Transactions

```php
$database->transaction(function ($connection): void {
    $connection->execute('insert into users (name) values (:name)', [
        'name' => 'Ada',
    ]);

    $connection->execute('insert into audit_log (message) values (:message)', [
        'message' => 'User created',
    ]);
});
```

The transaction commits when the callback returns. If the callback throws, the transaction rolls back.
