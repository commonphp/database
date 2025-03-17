<?php

use Neuron\Database\DatabaseManager;
use Neuron\Database\Drivers\MysqlDatabaseDriver;
use Neuron\Extensibility\ExtensionStore;
use Neuron\Extensibility\InstantiatorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/** @var LoggerInterface $logger */
/** @var InstantiatorInterface $instantiator */
/** @var EventDispatcherInterface $eventDispatcher */

$extensions = new ExtensionStore($instantiator, $logger, $eventDispatcher);
$database = new DatabaseManager($logger, $extensions, $eventDispatcher);

$database->connect('_default_', MysqlDatabaseDriver::class, [
    'database' => 'my_database',
    'host' => 'localhost',
    'username' => 'user',
    'password' => 'password',
]);

try {
    $database->transaction(function ($db) {
        $db->execute('INSERT INTO accounts (name, balance) VALUES (?, ?)', ['Checking', 500]);
        $db->execute('INSERT INTO transactions (account_id, amount) VALUES (?, ?)', [1, 500]);
    });

    echo "Transaction completed successfully.\n";
} catch (Throwable $e) {
    echo "Transaction failed: " . $e->getMessage();
}
