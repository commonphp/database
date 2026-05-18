# Package Boundaries

CommonPHP Database owns engine-neutral database connection and query behavior.

## Belongs Here

- Named connection definitions and registries.
- Lazy driver creation.
- Database manager query helpers.
- Query and result helper objects.
- Transaction helper behavior.
- Fetch mode and parameter type enums.
- Database events and profiling hooks.
- Database exception types.
- Driver contracts and shared abstract driver helpers.

## Does Not Belong Here

- MySQL, SQL Server, SQLite, PostgreSQL, or other engine clients.
- SQL dialect compilation for a specific engine.
- Migrations or schema builders.
- ORM identity maps, entity hydration, or repositories.
- HTTP request parsing, controllers, middleware, or responses.
- Authentication, authorization, sessions, or CSRF handling.
- Validation rules that require application-specific data.
- Runtime bootstrapping, service providers, modules, or container wiring.
- UI rendering.

Those concerns should live in driver packages or higher-level application packages.

## Integration Shape

Driver packages implement `DatabaseDriverInterface` and can extend `AbstractDatabaseDriver`.

Application packages depend on `DatabaseInterface` or `DatabaseManager` when they need to run queries. They should avoid depending on concrete engine driver classes unless they are configuring the connection itself.

## Stability Goal

The core package should stay simple to understand, debug, use, and update. Prefer clear contracts, small immutable helper objects, and explicit exception messages over magic behavior.
