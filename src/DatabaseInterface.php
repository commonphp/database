<?php

namespace Neuron\Database;

/**
 * Provides an interface for managing the database connections
 */
interface DatabaseInterface
{
    /**
     * Get all active connections
     *
     * @return ConnectionStore The instance of the connection store
     */
    public function getConnectionStore(): ConnectionStore;

    /**
     * Create a new database connection
     *
     * @param string $name The name of the connection
     * @param string $driverClass The driver class to use
     * @param array $options Options to pass to the driver class
     * @return void
     */
    public function connect(string $name, string $driverClass, array $options = []): void;

    /**
     * Retrieves the specified database connection.
     *
     * @param string $connection The name of the connection to get.
     * @return DatabaseDriverInterface The database driver instance.
     */
    public function with(string $connection): DatabaseDriverInterface;

    /**
     * Prepares an SQL statement for execution.
     *
     * @param string $query The SQL query to prepare.
     * @param array $parameters Parameters to bind to the query.
     * @param string $connection The name of the connection to use.
     * @return Query The prepared query.
     */
    public function prepare(string $query, array $parameters = [], string $connection = '_default_'): Query;

    /**
     * Return the number of records in the result
     *
     * @param string $query The SQL query to execute.
     * @param array $parameters Parameters to bind to the query.
     * @param string $connection The name of the connection to use.
     * @return int The number of records in the result
     */
    public function count(string $query, array $parameters = [], string $connection = '_default_'): int;

    /**
     * Executes an SQL query.
     *
     * @param string $query The SQL query to execute.
     * @param array $parameters Parameters to bind to the query.
     * @param string $connection The name of the connection to use.
     * @return int|bool The last inserted ID if an INSERT statement is executed, or
     *                 true if execution was successful, false otherwise.
     */
    public function execute(string $query, array $parameters = [], string $connection = '_default_'): int|bool;

    /**
     * Returns the first column of the first row from a query result.
     *
     * @param string $query The SQL query to execute.
     * @param array $parameters Parameters to bind to the query.
     * @param string $connection The name of the connection to use.
     * @return mixed The first column value.
     */
    public function fetchScalar(string $query, array $parameters = [], mixed $default = null, string $connection = '_default_'): mixed;

    /**
     * Fetches a single row from the database.
     *
     * @param string $query The SQL query to execute.
     * @param array $parameters Parameters to bind to the query.
     * @param string $connection The name of the connection to use.
     * @return array|false An associative array containing the row data, or false if no row is found.
     */
    public function fetchOne(string $query, array $parameters = [], string $connection = '_default_'): array|false;

    /**
     * Fetches all rows from the database.
     *
     * @param string $query The SQL query to execute.
     * @param array $parameters Parameters to bind to the query.
     * @param FetchMode $fetchMode The fetch mode (default: FetchMode::FETCH_ASSOC).
     * @param string $connection The name of the connection to use.
     * @return array An array of associative arrays containing the result set.
     */
    public function fetchAll(string $query, array $parameters = [], FetchMode $fetchMode = FetchMode::FETCH_ASSOC, string $connection = '_default_'): array;

    /**
     * Executes a transaction with automatic commit or rollback.
     *
     * @param callable $callback A function that executes multiple queries within a transaction.
     * @param string $connection The name of the connection to use.
     * @return mixed The return value of the callback function if the transaction succeeds.
     */
    public function transaction(callable $callback, string $connection = '_default_'): mixed;

    /**
     * Gets the last inserted ID for an INSERT operation.
     *
     * @param string $connection The name of the connection to use.
     * @return string|false The last inserted ID.
     */
    public function lastInsertId(string $connection = '_default_'): string|false;

    /**
     * Checks if the specified database connection is active.
     *
     * @param string $connection The name of the database connection.
     * @return bool True if the connection is active, false otherwise.
     */
    public function ping(string $connection = '_default_'): bool;

    /**
     * Logs a database query execution.
     *
     * @param string $action The action performed (e.g., "EXECUTE", "PREPARE").
     * @param string $query The SQL query being executed.
     * @param array $parameters The parameters bound to the query.
     * @param DatabaseDriverInterface $connection The database connection used.
     * @param float $duration The time it took for the query to complete
     * @param array|false $errors The errors that occurred, false if none
     * @return void
     */
    public function profileQuery(string $action, string $query, array $parameters, DatabaseDriverInterface $connection, float $duration, array|false $errors): void;

    /**
     * Enables query profiling
     *
     * @return void
     */
    public function enableProfiling(): void;

    /**
     * Disables query profiling
     *
     * @return void
     */
    public function disableProfiling(): void;

    /**
     * Checks if profiling is enabled
     *
     * @return bool
     */
    public function isProfiling(): bool;

}