<?php

/** @noinspection PhpUnused */

namespace Neuron\Database;

/**
 * Allows for quick-access execution after calling DatabaseInterface->prepare(...). Useful for running multiple
 * queries outside a transaction.
 */
final class Query
{
    /** @var string The actual query */
    private string $query;

    /** @var array Parameters passed to the query */
    private array $parameters;

    /** @var DatabaseDriverInterface Instance of the driver in use */
    private DatabaseDriverInterface $driver;

    /**
     * @param string $query The actual query
     * @param array $parameters The parameters to pass with the query
     * @param DatabaseDriverInterface $driver The driver to use with the query
     */
    public function __construct(string $query, array $parameters, DatabaseDriverInterface $driver)
    {
        $this->query = $query;
        $this->parameters = $parameters;
        $this->driver = $driver;
    }

    /**
     * Get the actual query
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Get the parameters in the query
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Return the number of records in the result
     *
     * @return int The number of records in the result
     */
    public function count(): int
    {
        return $this->driver->count($this->query, $this->parameters);
    }

    /**
     * Executes an SQL query.
     *
     * @return int|bool The last inserted ID if an INSERT statement is executed, or
     *                 true if execution was successful, false otherwise.
     */
    public function execute(): int|bool
    {
        return $this->driver->execute($this->query, $this->parameters);
    }

    /**
     * Returns the first column of the first row from a query result.
     *
     * @return mixed The first column value.
     */
    public function fetchScalar(mixed $default = null): mixed
    {
        return $this->driver->fetchScalar($this->query, $this->parameters, $default);
    }

    /**
     * Fetches a single row from the database.
     *
     * @return array|false An associative array containing the row data, or false if no row is found.
     */
    public function fetchOne(): array|false
    {
        return $this->driver->fetchOne($this->query, $this->parameters);
    }

    /**
     * Fetches all rows from the database.
     *
     * @param FetchMode $fetchMode The fetch mode (default: FetchMode::FETCH_ASSOC).
     * @return array An array of associative arrays containing the result set.
     */
    public function fetchAll(FetchMode $fetchMode = FetchMode::FETCH_ASSOC): array
    {
        return $this->driver->fetchAll($this->query, $this->parameters, $fetchMode);
    }
}