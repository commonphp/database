<?php

use Neuron\Database\DatabaseManager;
use Neuron\Database\Drivers\MysqlDatabaseDriver;
use Neuron\Extensibility\ExtensionStore;
use Neuron\Extensibility\InstantiatorInterface;
use Neuron\Database\Events\QueryExecutedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/** @var LoggerInterface $logger */
/** @var InstantiatorInterface $instantiator */
/** @var EventDispatcherInterface $eventDispatcher */

$extensions = new ExtensionStore($instantiator, $logger, $eventDispatcher);
$database = new DatabaseManager($logger, $extensions, $eventDispatcher);

// Connect to your database
$database->connect('_default_', MysqlDatabaseDriver::class, [
    'database' => 'my_database',
    'host' => 'localhost',
    'username' => 'user',
    'password' => 'password',
]);

// Enable profiling
$database->enableProfiling();

// Listen to query executed events for profiling output
$eventDispatcher->listen(QueryExecutedEvent::class, function (QueryExecutedEvent $event) {
    echo sprintf(
        "[%s] Query executed: %s | Duration: %.4f seconds\n",
        strtoupper($event->action),
        $event->query,
        $event->duration
    );
});

// Execute queries
$database->fetchAll('SELECT * FROM users LIMIT 10');
$database->execute('UPDATE users SET last_login = NOW() WHERE id = ?', [1]);
