<?php

declare(strict_types=1);

namespace CommonPHP\Database;

use CommonPHP\Database\Contracts\DatabaseDriverInterface;
use CommonPHP\Database\Contracts\DatabaseInterface;
use CommonPHP\Database\Enums\FetchMode;
use CommonPHP\Database\Enums\ParameterType;
use CommonPHP\Database\Exceptions\QueryException;
use Stringable;

final readonly class Query implements Stringable
{
    /**
     * @param array<string|int, mixed> $parameters
     */
    public function __construct(
        private string $query,
        private array $parameters = [],
        private DatabaseInterface|DatabaseDriverInterface|null $executor = null,
        private ?string $connection = null,
    ) {
    }

    /**
     * @param array<string|int, mixed> $parameters
     */
    public static function forDriver(
        string $query,
        array $parameters,
        DatabaseDriverInterface $driver,
    ): self {
        return new self($query, $parameters, $driver);
    }

    public function sql(): string
    {
        return $this->query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return array<string|int, mixed>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array<string|int, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function connection(): ?string
    {
        return $this->connection;
    }

    public function parameterType(): ParameterType
    {
        return ParameterType::detect($this->parameters);
    }

    /**
     * @param array<string|int, mixed> $parameters
     */
    public function withParameters(array $parameters): self
    {
        return new self($this->query, $parameters, $this->executor, $this->connection);
    }

    public function bind(string|int $key, mixed $value): self
    {
        $parameters = $this->parameters;
        $parameters[$key] = $value;

        return $this->withParameters($parameters);
    }

    public function on(?string $connection): self
    {
        return new self($this->query, $this->parameters, $this->executor, $connection);
    }

    public function using(DatabaseInterface|DatabaseDriverInterface $executor, ?string $connection = null): self
    {
        return new self($this->query, $this->parameters, $executor, $connection ?? $this->connection);
    }

    public function count(): int
    {
        $executor = $this->executor();

        if ($executor instanceof DatabaseInterface) {
            return $executor->count($this->query, $this->parameters, $this->connection);
        }

        return $executor->count($this->query, $this->parameters);
    }

    public function execute(): int|bool
    {
        $executor = $this->executor();

        if ($executor instanceof DatabaseInterface) {
            return $executor->execute($this->query, $this->parameters, $this->connection);
        }

        return $executor->execute($this->query, $this->parameters);
    }

    public function fetchScalar(mixed $default = null): mixed
    {
        $executor = $this->executor();

        if ($executor instanceof DatabaseInterface) {
            return $executor->fetchScalar($this->query, $this->parameters, $default, $this->connection);
        }

        return $executor->fetchScalar($this->query, $this->parameters, $default);
    }

    /**
     * @return array<string|int, mixed>|false
     */
    public function fetchOne(): array|false
    {
        $executor = $this->executor();

        if ($executor instanceof DatabaseInterface) {
            return $executor->fetchOne($this->query, $this->parameters, $this->connection);
        }

        return $executor->fetchOne($this->query, $this->parameters);
    }

    /**
     * @return list<array<string|int, mixed>|object|scalar|null>
     */
    public function fetchAll(FetchMode $fetchMode = FetchMode::FETCH_ASSOC): array
    {
        $executor = $this->executor();

        if ($executor instanceof DatabaseInterface) {
            return $executor->fetchAll($this->query, $this->parameters, $fetchMode, $this->connection);
        }

        return $executor->fetchAll($this->query, $this->parameters, $fetchMode);
    }

    public function result(FetchMode $fetchMode = FetchMode::FETCH_ASSOC): QueryResult
    {
        return QueryResult::rows($this->fetchAll($fetchMode));
    }

    public function __toString(): string
    {
        return $this->query;
    }

    private function executor(): DatabaseInterface|DatabaseDriverInterface
    {
        if ($this->executor === null) {
            throw QueryException::notExecutable($this->query);
        }

        return $this->executor;
    }
}
