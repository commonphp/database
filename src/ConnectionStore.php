<?php

namespace Neuron\Database;

use Neuron\Database\Events\ConnectedEvent;
use Neuron\Database\Exceptions\DuplicateNameException;
use Neuron\Database\Exceptions\InvalidNameException;
use Neuron\Database\Exceptions\NotConnectedException;
use Neuron\Database\Exceptions\UnsupportedDriverException;
use Neuron\Extensibility\ExtensionsInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final class ConnectionStore
{
    private LoggerInterface $logger;
    private ExtensionsInterface $extensions;
    private EventDispatcherInterface $eventDispatcher;

    /** @var array<string, DatabaseDriverInterface> */
    private array $connections = [];

    public function __construct(LoggerInterface $logger, ExtensionsInterface $extensions, EventDispatcherInterface $eventDispatcher)
    {
        $this->logger = $logger;
        $this->extensions = $extensions;
        $this->eventDispatcher = $eventDispatcher;
    }

    private function sanitizeName(string $name): string
    {
        $name = strtolower(trim($name));
        if (strlen($name) == 0) {
            $this->logger->error('Database connection name cannot be empty.');
            throw new InvalidNameException($name);
        }
        return $name;
    }

    public function add(string $name, string $driverClass, array $options = []): void
    {
        $name = $this->sanitizeName($name);
        if (isset($this->connections[$name])) {
            $this->logger->error('The supplied database connection name is already in use.', ['name' => $name]);
            throw new DuplicateNameException($name);
        }
        if (!$this->extensions->has($driverClass)) {
            $this->logger->error('The requested driver class has not been registered with the driver registry', ['name' => $name, 'driverClass' => $driverClass]);
            throw new UnsupportedDriverException($driverClass);
        }
        $this->connections[$name] = ['class' => $driverClass, 'options' => $options];
    }

    public function get(string $name): DatabaseDriverInterface
    {
        $name = $this->sanitizeName($name);
        if (!isset($this->connections[$name])) {
            $this->logger->error('There is no database connection with the supplied name.', ['name' => $name]);
            throw new NotConnectedException($name);
        }
        $instance = $this->connections[$name];
        if (is_array($instance)) {
            $className = $instance['class'];
            $options = $instance['options'];
            /** @var DatabaseDriverInterface $instance */
            $instance = $this->extensions->create($className, $options);
            $this->connections[$name] = $instance;
            $this->eventDispatcher->dispatch(new ConnectedEvent($name, $instance));
        }
        return $instance;
    }

    public function has(string $name): bool
    {
        $name = $this->sanitizeName($name);
        return isset($this->connections[$name]);
    }
}