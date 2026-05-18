# Queries And Results

The database package gives callers direct manager methods and an immutable `Query` object for delayed execution.

## Direct Query Methods

`DatabaseInterface` exposes:

- `prepare()`
- `count()`
- `execute()`
- `fetchScalar()`
- `fetchOne()`
- `fetchAll()`
- `transaction()`
- `lastInsertId()`
- `ping()`

```php
$database->execute('update users set active = :active where id = :id', [
    'active' => true,
    'id' => 1,
]);

$row = $database->fetchOne('select * from users where id = :id', [
    'id' => 1,
]);
```

## Query

`Query` stores:

- SQL string;
- parameters;
- optional manager or driver executor;
- optional connection name.

```php
$query = $database
    ->prepare('select * from users where id = :id')
    ->bind('id', 1);
```

Useful accessors:

- `sql()`
- `getQuery()`
- `parameters()`
- `getParameters()`
- `connection()`
- `parameterType()`

Useful mutation-style methods:

- `withParameters(array $parameters)`
- `bind(string|int $key, mixed $value)`
- `on(?string $connection)`
- `using(DatabaseInterface|DatabaseDriverInterface $executor, ?string $connection = null)`

These return new query objects. The original query is unchanged.

## Parameter Types

`ParameterType::detect()` classifies parameters as:

- `Empty`
- `Named`
- `Positional`

Named parameters win when an array mixes numeric and string keys.

## Fetch Modes

`FetchMode` mirrors common PDO fetch mode values:

- `FETCH_ASSOC`
- `FETCH_NUM`
- `FETCH_BOTH`
- `FETCH_OBJ`
- and other common PDO-compatible modes.

Drivers can map `FetchMode` to the underlying engine or client library.

## QueryResult

`QueryResult` is a small wrapper for code that wants iterable, countable result objects.

```php
$result = $database
    ->prepare('select id, name from users')
    ->result();

foreach ($result as $row) {
    echo $row['name'] . PHP_EOL;
}
```

It exposes:

- `all()`
- `first(mixed $default = false)`
- `scalar(mixed $default = null)`
- `isEmpty()`
- `affectedRows()`
- `lastInsertId()`

Use `QueryResult::affected()` for command-style results:

```php
$result = QueryResult::affected(1, lastInsertId: '42');
```
