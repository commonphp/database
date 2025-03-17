<?php

use Neuron\Database\DatabaseManager;
use Neuron\Database\Drivers\AliasDatabaseDriver;
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

// Register primary database connection
$database->connect('primary', MysqlDatabaseDriver::class, [
    'database' => 'primary_db',
    'host' => 'localhost',
    'username' => 'user',
    'password' => 'password',
]);

// Register an alias connection pointing to primary
$database->connect('secondary', AliasDatabaseDriver::class, [
    'database' => $database,
    'target' => 'primary'
]);

// Using the alias connection transparently
$result = $database->fetchOne('SELECT COUNT(*) AS total FROM users', [], 'secondary');

echo "Total users: " . $result['total'] . "\n";
