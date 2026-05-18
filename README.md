# CommonPHP Database

CommonPHP Database provides driver-based database connection and query support for CommonPHP applications. It defines database managers, connection handling, result behavior, and the contracts needed for database-specific drivers such as MySQL or Microsoft SQL Server.

The package is intended to support named connections and lazy driver creation so applications can define multiple database connections without opening them until needed.

## Requirements

- PHP `^8.5`
- `comphp/runtime:^0.3`

## Installation

Once this package is available through your Composer repositories, install it with:

```bash
composer require comphp/database
```

## Usage

```php
<?php

// TODO: Write usage
```

## Package Notes

This package should provide database managers, named connections, lazy connection drivers, transactions, and result abstractions. Concrete database engines such as MySQL, SQL Server, SQLite, and PostgreSQL should live in driver packages.

## Error Handling

Connection failures, query failures, transaction failures, invalid connection names, and driver errors should throw CommonPHP database exceptions.

## Documentation

- [Documentation index](docs/index.md)
- [Usage](docs/usage.md)
- [Testing](TESTING.md)
- [Contributing](CONTRIBUTING.md)
- [Security](SECURITY.md)

## License

MIT. See [LICENSE.md](LICENSE.md).
