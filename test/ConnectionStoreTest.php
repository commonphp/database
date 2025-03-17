<?php

namespace NeuronTests\Database;

use Neuron\Database\ConnectionStore;
use Neuron\Database\DatabaseDriver;
use Neuron\Database\DatabaseManager;
use Neuron\Database\Exceptions\DuplicateNameException;
use Neuron\Database\Exceptions\NotConnectedException;
use Neuron\Extensibility\ExtensionsInterface;
use Neuron\Extensibility\ExtensionStore;
use Neuron\Extensibility\InstantiatorInterface;
use NeuronTests\Database\Drivers\MockDatabaseDriver;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class ConnectionStoreTest extends TestCase
{
    private ConnectionStore $store;
    private InstantiatorInterface $instantiator;
    private LoggerInterface $logger;
    private ExtensionsInterface $extensions;
    private DatabaseManager $database;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->instantiator = $this->createMock(InstantiatorInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->extensions = new ExtensionStore(
            $this->instantiator,
            $this->logger,
            $this->eventDispatcher
        );

        $this->database = new DatabaseManager(
            $this->logger,
            $this->extensions,
            $this->eventDispatcher
        );

        $this->store = $this->database->getConnectionStore();

        $this->extensions
            ->getRegistry()
            ->register(DatabaseDriver::class, MockDatabaseDriver::class);
    }

    public function testAddThrowsDuplicateNameException(): void
    {
        $this->store->add('test', MockDatabaseDriver::class);

        $this->expectException(DuplicateNameException::class);
        $this->store->add('test', MockDatabaseDriver::class);
    }

    public function testGetThrowsNotConnectedException(): void
    {
        $this->expectException(NotConnectedException::class);
        $this->store->get('nonexistent');
    }

    public function testHasReturnsCorrectly(): void
    {
        $this->store->add('existing', MockDatabaseDriver::class);

        $this->assertTrue($this->store->has('existing'));
        $this->assertFalse($this->store->has('nonexisting'));
    }
}
