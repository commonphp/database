<?php

namespace Neuron\Database;

/**
 * A connection driver for the database
 */
interface DatabaseDriverInterface
{
    /**
     * Set the name of the connection
     * WARNING: This should only be used by the database manager
     *
     * @param string $name The name of the connection
     * @return void
     */
    public function setName(string $name): void;

    /**
     * Get the name of the connection
     *
     * @return string The name of the connection
     */
    public function getName(): string;

    /**
     * Return the number of records in the result
     *
     * @param string $query The SQL query to execute.
     * @param array $parameters Parameters to bind to the query.
     * @return int The number of records in the result
     */
    public function count(string $query, array $parameters = []): int;

    /**
     * Executes an SQL query.
     *
     * @param string $query The SQL query to execute.
     * @param array $parameters Parameters to bind to the query.
     * @return int|bool The last inserted ID if an INSERT statement is executed, or
     *                 true if execution was successful, false otherwise.
     */
    public function execute(string $query, array $parameters = []): int|bool;

    /**
     * Returns the first column of the first row from a query result.
     *
     * @param string $query The SQL query to execute.
     * @param array $parameters Parameters to bind to the query.
     * @return mixed The first column value.
     */
    public function fetchScalar(string $query, array $parameters = [], mixed $default = null): mixed;

    /**
     * Fetches a single row from the database.
     *
     * @param string $query The SQL query to execute.
     * @param array $parameters Parameters to bind to the query.
     * @return array|false An associative array containing the row data, or false if no row is found.
     */
    public function fetchOne(string $query, array $parameters = []): array|false;

    /**
     * Fetches all rows from the database.
     *
     * @param string $query The SQL query to execute.
     * @param array $parameters Parameters to bind to the query.
     * @param FetchMode $fetchMode The fetch mode (default: FetchMode::FETCH_ASSOC).
     * @return array An array of associative arrays containing the result set.
     */
    public function fetchAll(string $query, array $parameters = [], FetchMode $fetchMode = FetchMode::FETCH_ASSOC): array;

    /**
     * Executes a transaction with automatic commit or rollback.
     *
     * @param callable $callback A function that executes multiple queries within a transaction.
     * @return mixed The return value of the callback function if the transaction succeeds.
     */
    public function transaction(callable $callback): mixed;

    /**
     * Gets the last inserted ID for an INSERT operation.
     *
     * @return string|false The last inserted ID.
     */
    public function lastInsertId(): string|false;

    /**
     * Checks if the specified database connection is active.
     *
     * @return bool True if the connection is active, false otherwise.
     */
    public function ping(): bool;
}