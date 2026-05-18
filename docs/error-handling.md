# Error Handling

Database has one base exception type: `DatabaseException`.

All package-specific exceptions extend it.

## Connection Errors

Connection definition, registry, and creation failures throw connection exceptions:

- `InvalidConnectionNameException`
- `ConnectionExistsException`
- `ConnectionNotFoundException`
- `ConnectionException`
- `DatabaseDriverException`

Examples:

```php
$database->with('missing'); // ConnectionNotFoundException
$database->connect('', AppDatabaseDriver::class); // InvalidConnectionNameException
```

## Query Errors

Manager query methods wrap unexpected driver runtime failures in `QueryException`.

```php
try {
    $database->execute('bad sql');
} catch (QueryException $exception) {
    $previous = $exception->getPrevious();
}
```

If a driver already throws a `DatabaseException`, the manager does not wrap it again.

## Driver Operation Errors

Non-query driver operations such as `ping()` and `lastInsertId()` wrap unexpected runtime failures in `DatabaseDriverException`.

## Transaction Errors

Transaction failures throw `TransactionException`.

Runtime failures from transaction callbacks are wrapped after rollback. Existing database exceptions are rolled back and rethrown.

```php
try {
    $database->transaction($callback);
} catch (TransactionException $exception) {
    // Transaction begin, commit, rollback, or callback failed.
}
```

## Recommended Integration

Application layers should catch database exceptions at boundaries that can add context:

- API packages can translate them into problem details;
- console packages can print a concise error and return a failing exit code;
- logging packages can record the connection name, action, and query safely;
- UI packages should avoid exposing raw SQL errors to users.

Keep database exceptions technical. User-facing wording belongs at the boundary.
