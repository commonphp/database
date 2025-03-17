<?php

use Neuron\Database\DatabaseException;
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
    'database' => 'nonexistent_db', // Intentional wrong DB name
    'host' => 'localhost',
    'username' => 'user',
    'password' => 'wrongpassword',
]);

try {
    $database->execute('SELECT * FROM users');
} catch (DatabaseException $e) {
    echo "Database error: " . $e->getMessage();
}
