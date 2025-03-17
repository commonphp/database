<?php

namespace NeuronTests\Database;

use Neuron\Database\DatabaseDriver;
use Neuron\Database\DatabaseManager;
use Neuron\Database\Drivers\MysqlDatabaseDriver;
use Neuron\Extensibility\ExtensionStore;
use Neuron\Extensibility\InstantiatorInterface;
use NeuronTests\Database\Drivers\MockDatabaseDriver;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class DatabaseManagerTest extends TestCase
{
    private LoggerInterface $logger;
    private InstantiatorInterface $instantiator;
    private EventDispatcherInterface $eventDispatcher;
    private ExtensionStore $extensions;
    private DatabaseManager $database;

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

        $this->extensions
            ->getRegistry()
            ->register(DatabaseDriver::class, MockDatabaseDriver::class);
    }

    public function testCanRegisterMysqlDriver(): void
    {
        $this->database->connect('_default_', MockDatabaseDriver::class, [
            'database' => 'test_db',
            'username' => 'user',
            'password' => 'pass'
        ]);

        $this->assertTrue($this->database->getConnectionStore()->has('_default_'));
    }

    public function testWithReturnsDriverInstance(): void
    {
        $mockDriver = $this->createMock(MysqlDatabaseDriver::class);

        $this->instantiator->expects($this->once())
            ->method('instantiate')
            ->willReturn($mockDriver);

        $this->database->connect('_default_', MockDatabaseDriver::class, [
            'database' => 'test_db',
            'username' => 'user',
            'password' => 'pass'
        ]);

        $driver = $this->database->with('_default_');

        $this->assertSame($mockDriver, $driver);
    }

    public function testFetchAllReturnsArray(): void
    {
        $mockDriver = $this->createMock(MockDatabaseDriver::class);
        $mockDriver->method('fetchAll')
            ->willReturn([['id' => 1, 'name' => 'John Doe']]);

        $this->instantiator->method('instantiate')
            ->willReturn($mockDriver);

        $this->database->connect('_default_', MockDatabaseDriver::class);

        $result = $this->database->fetchAll('SELECT * FROM users');

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('John Doe', $result[0]['name']);
    }

    public function testExecuteReturnsLastInsertId(): void
    {
        $mockDriver = $this->createMock(MysqlDatabaseDriver::class);
        $mockDriver->method('execute')
            ->willReturn(123);

        $this->instantiator->method('instantiate')
            ->willReturn($mockDriver);

        $this->database->connect('_default_', MockDatabaseDriver::class);

        $result = $this->database->execute('INSERT INTO users (name) VALUES (?)', ['Jane Doe']);

        $this->assertEquals(123, $result);
    }

    public function testTransactionRollsBackOnException(): void
    {
        $mockDriver = $this->createMock(MockDatabaseDriver::class);
        $mockDriver->method('transaction')
            ->will($this->throwException(new \Exception('Transaction failed')));

        $this->instantiator->method('instantiate')
            ->willReturn($mockDriver);

        $this->database->connect('_default_', MockDatabaseDriver::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transaction failed');

        $this->database->transaction(function () {
            throw new \Exception('Transaction failed');
        });
    }

    public function testProfilingTogglesCorrectly(): void
    {
        $this->assertFalse($this->database->isProfiling());

        $this->database->enableProfiling();
        $this->assertTrue($this->database->isProfiling());

        $this->database->disableProfiling();
        $this->assertFalse($this->database->isProfiling());
    }
}
