# Basic Connections

```php
<?php

declare(strict_types=1);

use CommonPHP\Database\DatabaseManager;

$database = new DatabaseManager();

$database->connect('main', AppDatabaseDriver::class, [
    'host' => '127.0.0.1',
    'dbname' => 'app',
    'username' => 'app',
    'password' => 'secret',
], default: true);

$database->connect('reporting', AppDatabaseDriver::class, [
    'host' => '10.0.0.20',
    'dbname' => 'reports',
    'username' => 'reporter',
    'password' => 'secret',
]);

$users = $database->fetchAll('select * from users');

$report = $database->fetchAll(
    'select * from daily_user_report where report_date = :date',
    ['date' => '2026-05-18'],
    connection: 'reporting',
);
```

Connections are lazy when registered by driver class. The driver is created when the connection is first used.
