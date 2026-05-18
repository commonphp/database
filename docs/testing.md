# Testing And QA

CommonPHP Database includes a package-local PHPUnit configuration and unit tests.

## Install Dependencies

From the package directory:

```bash
composer install
```

From the monorepo, the root `vendor` directory can also satisfy the test suite because `tests/bootstrap.php` checks both package and workspace autoloaders.

## Run PHPUnit

From the monorepo root:

```bash
vendor/bin/phpunit -c package/database/phpunit.xml.dist
```

From `package/database`:

```bash
../../vendor/bin/phpunit -c phpunit.xml.dist
```

## Current Test Coverage

The unit suite covers:

- `ConnectionDefinition` normalization, config parsing, driver classes, driver instances, default flags, invalid names, invalid drivers, and invalid options;
- `ConnectionRegistry` construction, config loading, single and multiple connection configs, duplicate names, missing connections, default switching, removal, clearing, lazy driver creation, resolved driver caching, iteration, and constructor failure wrapping;
- `DatabaseManager` registration, static construction, config construction, default and named connection routing, lazy connection events, query delegation, prepared queries, profiling toggles, manual profiling, query events, logger behavior, transaction routing, runtime query failure wrapping, database exception pass-through, and driver operation failure wrapping;
- `Query` accessors, immutability, parameter binding, connection switching, manager execution, direct driver execution, result wrapping, parameter type detection, and missing executor failures;
- `QueryResult` iterable rows, count behavior, affected row helpers, insert IDs, first-row defaults, array/object/scalar/null scalar extraction, and empty results;
- `Transaction` begin, commit, rollback, state changes, callback success, callback runtime failures, database exception rethrows, inactive operations, and begin/commit/rollback failure wrapping;
- `AbstractDatabaseDriver` default name, query preparation, count, scalar fetch behavior, protected helper behavior, and transaction delegation;
- `FetchMode`, `ParameterType`, `ConnectedEvent`, `QueryExecutedEvent`, and all database exception factory helpers.

## Manual Review Areas

Manual review should still cover concrete driver packages against real database engines. The core package test suite uses in-memory fixtures so it can verify contracts and behavior without external services.
