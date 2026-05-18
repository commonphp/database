# Connections

Connections are named definitions for database drivers. A connection can store a driver class plus constructor options, or it can store an already-created driver instance.

## Connection Names

Connection names are normalized by `ConnectionDefinition::normalizeName()`:

- leading and trailing whitespace is trimmed;
- names are lowercased;
- empty names are rejected;
- null bytes are rejected.

```php
ConnectionDefinition::normalizeName(' Main '); // "main"
```

The default connection name is `_default_`.

## ConnectionDefinition

```php
use CommonPHP\Database\ConnectionDefinition;

$definition = new ConnectionDefinition('main', AppDatabaseDriver::class, [
    'host' => '127.0.0.1',
], default: true);
```

The definition exposes:

- `name()`
- `driverClass()`
- `options()`
- `hasDriverInstance()`
- `driverInstance()`
- `isDefault()`

## Config Arrays

```php
$definition = ConnectionDefinition::fromConfig([
    'name' => 'main',
    'driver' => AppDatabaseDriver::class,
    'host' => '127.0.0.1',
    'options' => [
        'dbname' => 'app',
    ],
    'default' => true,
]);
```

Inline keys other than `name`, `driver`, `default`, and `options` become driver options. Explicit `options` override inline options with the same key.

## ConnectionRegistry

`ConnectionRegistry` stores definitions and lazily creates drivers.

```php
use CommonPHP\Database\ConnectionRegistry;

$registry = new ConnectionRegistry();
$registry->add('main', AppDatabaseDriver::class, $options, default: true);

$driver = $registry->get('main');
```

Repeated calls to `get()` return the same driver instance.

## Registry Config

Single connection:

```php
$registry = ConnectionRegistry::fromConfig([
    'driver' => AppDatabaseDriver::class,
    'host' => '127.0.0.1',
]);
```

Multiple connections:

```php
$registry = ConnectionRegistry::fromConfig([
    'connections' => [
        'main' => [
            'driver' => AppDatabaseDriver::class,
            'host' => '127.0.0.1',
        ],
        'reporting' => [
            'driver' => AppDatabaseDriver::class,
            'host' => '10.0.0.20',
            'default' => true,
        ],
    ],
]);
```

## Registry Operations

The registry supports:

- `register(ConnectionDefinition $definition)`
- `add(string $name, string|DatabaseDriverInterface $driver, array $options = [])`
- `remove(string $name)`
- `has(string $name)`
- `definition(?string $name = null)`
- `get(?string $name = null)`
- `isResolved(string $name)`
- `setDefault(string $name)`
- `defaultName()`
- `defaultDefinition()`
- `all()`
- `resolved()`
- `clear()`

It is countable and iterable over connection definitions.
