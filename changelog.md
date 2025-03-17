# Changelog

All notable changes to **comphp/database** will be documented in this file.

## [0.1.0] - 2025-03-16

### Added
- **Initial Release** of the `comphp/database` library.
- **DatabaseManager** providing centralized connection management.
- **MySQL Driver** (`MysqlDatabaseDriver`) with essential operations (fetch, insert, update, etc.).
- **Alias Database Driver** enabling alias-based connection references.
- **Transaction Support** allowing atomic multi-query operations.
- **PSR-3 Logging** integration for query actions and exceptions.
- **PSR-14 Events** (`ConnectedEvent`, `QueryExecutedEvent`) for event-driven usage.
- **Extensibility** through `comphp/extensible` (register new drivers, manage them via attributes).
- **Query Profiling** to log query execution duration and errors.

