<?php

declare(strict_types=1);

namespace CommonPHP\Database;

use CommonPHP\Database\Contracts\DatabaseDriverInterface;
use CommonPHP\Database\Exceptions\DatabaseException;
use CommonPHP\Database\Exceptions\TransactionException;
use Throwable;

final class Transaction
{
    private bool $active = false;

    private bool $completed = false;

    private function __construct(
        private readonly DatabaseDriverInterface $driver,
    ) {
    }

    public static function begin(DatabaseDriverInterface $driver): self
    {
        $transaction = new self($driver);

        try {
            $driver->beginTransaction();
        } catch (DatabaseException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw TransactionException::forOperation('begin', $driver->getName(), $throwable);
        }

        $transaction->active = true;

        return $transaction;
    }

    public static function run(DatabaseDriverInterface $driver, callable $callback): mixed
    {
        $transaction = self::begin($driver);

        try {
            $result = $callback($driver, $transaction);
            $transaction->commit();

            return $result;
        } catch (Throwable $throwable) {
            if ($transaction->isActive()) {
                $transaction->rollBack();
            }

            if ($throwable instanceof DatabaseException) {
                throw $throwable;
            }

            throw TransactionException::forConnection($driver->getName(), $throwable);
        }
    }

    public function driver(): DatabaseDriverInterface
    {
        return $this->driver;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function commit(): void
    {
        $this->assertActive('commit');

        try {
            $this->driver->commit();
            $this->active = false;
            $this->completed = true;
        } catch (DatabaseException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw TransactionException::forOperation('commit', $this->driver->getName(), $throwable);
        }
    }

    public function rollBack(): void
    {
        $this->assertActive('roll back');

        try {
            $this->driver->rollBack();
            $this->active = false;
            $this->completed = true;
        } catch (DatabaseException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw TransactionException::forOperation('roll back', $this->driver->getName(), $throwable);
        }
    }

    private function assertActive(string $operation): void
    {
        if (!$this->active) {
            throw TransactionException::inactive($operation, $this->driver->getName());
        }
    }
}
