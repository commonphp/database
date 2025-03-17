<?php

use Neuron\Database\DatabaseManager;
use Neuron\Database\Drivers\MysqlDatabaseDriver;
use Neuron\Database\FetchMode;
use Neuron\Extensibility\ExtensionStore;
use Neuron\Extensibility\InstantiatorInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

// Assume these instances are created or injected via your application's DI container
/** @var LoggerInterface $logger */
/** @var InstantiatorInterface $instantiator */
/** @var EventDispatcherInterface $eventDispatcher */

// Setup extensibility
$extensions = new ExtensionStore($instantiator, $logger, $eventDispatcher);

// Create a new database manager
$database = new DatabaseManager($logger, $extensions, $eventDispatcher);

// Connect to the database using MysqlDatabaseDriver
$database->connect('_default_', MysqlDatabaseDriver::class, [
    'database' => 'my_database',
    'host' => 'localhost',
    'username' => 'user',
    'password' => 'password',
]);

// Execute a basic query
$result = $database->fetchAll('SELECT * FROM users LIMIT 5');

foreach ($result as $user) {
    echo "User ID: {$user['id']}, Name: {$user['name']}\n";
}
