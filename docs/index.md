# CommonPHP Database Documentation

CommonPHP Database is the driver-based database layer for CommonPHP applications and plain PHP projects. It owns named connection definitions, lazy driver creation, query execution helpers, transaction helpers, result wrappers, database events, and database-specific exception types.

Database stays engine-neutral. MySQL, Microsoft SQL Server, SQLite, PostgreSQL, and other engines belong in driver packages that implement `DatabaseDriverInterface`.

## Start Here

- [Getting started](getting-started.md)
- [Usage](usage.md)
- [Package boundaries](package-boundaries.md)

## Database Concepts

- [Connections](connections.md)
- [Queries and results](queries-and-results.md)
- [Transactions](transactions.md)
- [Drivers](drivers.md)
- [Events and profiling](events-and-profiling.md)
- [Error handling](error-handling.md)

## Examples

- [Examples index](examples/index.md)
- [Basic connections](examples/basic-connections.md)
- [Transactions](examples/transactions.md)
- [Custom driver](examples/custom-driver.md)
- [Profiling](examples/profiling.md)

## Development

- [Testing and QA](testing.md)

## Public API Map

Entry points:

- `CommonPHP\Database\DatabaseManager`
- `CommonPHP\Database\ConnectionRegistry`
- `CommonPHP\Database\ConnectionDefinition`

Query and result objects:

- `CommonPHP\Database\Query`
- `CommonPHP\Database\QueryResult`
- `CommonPHP\Database\Transaction`

Contracts:

- `CommonPHP\Database\Contracts\DatabaseInterface`
- `CommonPHP\Database\Contracts\DatabaseDriverInterface`
- `CommonPHP\Database\Contracts\AbstractDatabaseDriver`

Enums:

- `CommonPHP\Database\Enums\FetchMode`
- `CommonPHP\Database\Enums\ParameterType`

Events:

- `CommonPHP\Database\Events\ConnectedEvent`
- `CommonPHP\Database\Events\QueryExecutedEvent`

Exceptions:

- `CommonPHP\Database\Exceptions\DatabaseException`
- `CommonPHP\Database\Exceptions\ConnectionException`
- `CommonPHP\Database\Exceptions\ConnectionExistsException`
- `CommonPHP\Database\Exceptions\ConnectionNotFoundException`
- `CommonPHP\Database\Exceptions\DatabaseDriverException`
- `CommonPHP\Database\Exceptions\InvalidConnectionNameException`
- `CommonPHP\Database\Exceptions\QueryException`
- `CommonPHP\Database\Exceptions\TransactionException`
