<?php

namespace Neuron\Database;

use Neuron\Database\Exceptions\DriverAlreadyNamedException;
use Neuron\Database\Exceptions\DriverNotNamedException;
use Psr\Log\LoggerInterface;

abstract class AbstractDatabaseDriver implements DatabaseDriverInterface
{
    private string $name;
    protected readonly LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     * @throws DriverAlreadyNamedException
     */
    public function setName(string $name): void
    {
        if (isset($this->name)) {
            $this->logger->error('The driver is already named', [
                'name' => $name,
                'current' => $this->name
            ]);
            throw new DriverAlreadyNamedException($name);
        }
        $this->name = $name;
    }

    /**
     * @inheritDoc
     * @throws DriverNotNamedException
     */
    public function getName(): string
    {
        if (!isset($this->name)) {
            $this->logger->error('The driver is not named', [
                'class' => get_class($this),
            ]);
            throw new DriverNotNamedException();
        }
        return $this->name;
    }
}