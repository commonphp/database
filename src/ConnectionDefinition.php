<?php

declare(strict_types=1);

namespace CommonPHP\Database;

use CommonPHP\Database\Contracts\DatabaseDriverInterface;
use CommonPHP\Database\Exceptions\ConnectionException;
use CommonPHP\Database\Exceptions\DatabaseDriverException;
use CommonPHP\Database\Exceptions\InvalidConnectionNameException;
use Stringable;

final readonly class ConnectionDefinition implements Stringable
{
    public const DEFAULT_NAME = '_default_';

    private string $name;

    /**
     * @var class-string<DatabaseDriverInterface>
     */
    private string $driverClass;

    private ?DatabaseDriverInterface $driver;

    /**
     * @param class-string<DatabaseDriverInterface>|DatabaseDriverInterface $driver
     * @param array<string|int, mixed> $options
     */
    public function __construct(
        string $name,
        string|DatabaseDriverInterface $driver,
        private array $options = [],
        private bool $default = false,
    ) {
        $this->name = self::normalizeName($name);

        if ($driver instanceof DatabaseDriverInterface) {
            if ($options !== []) {
                throw ConnectionException::forInvalidConfiguration(
                    'Connection "' . $this->name . '" cannot define constructor options for an existing driver instance.',
                );
            }

            $this->driverClass = $driver::class;
            $this->driver = $driver;

            return;
        }

        if (!is_a($driver, DatabaseDriverInterface::class, true)) {
            throw DatabaseDriverException::forClass($driver);
        }

        $this->driverClass = $driver;
        $this->driver = null;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config, ?string $name = null): self
    {
        $connectionName = $name ?? ($config['name'] ?? self::DEFAULT_NAME);

        if (!is_string($connectionName)) {
            throw InvalidConnectionNameException::forName((string) $connectionName);
        }

        $driver = $config['driver'] ?? null;

        if (!is_string($driver) && !$driver instanceof DatabaseDriverInterface) {
            throw ConnectionException::forInvalidConfiguration(
                'Connection "' . $connectionName . '" must define a database driver class or instance.',
            );
        }

        $inlineOptions = array_diff_key($config, [
            'name' => true,
            'driver' => true,
            'default' => true,
            'options' => true,
        ]);

        $explicitOptions = $config['options'] ?? [];

        if (!is_array($explicitOptions)) {
            throw ConnectionException::forInvalidConfiguration(
                'Connection "' . $connectionName . '" options must be an array.',
            );
        }

        return new self(
            $connectionName,
            $driver,
            array_replace($inlineOptions, $explicitOptions),
            (bool) ($config['default'] ?? false),
        );
    }

    public static function normalizeName(string $name): string
    {
        if (str_contains($name, "\0")) {
            throw InvalidConnectionNameException::forName($name);
        }

        $name = strtolower(trim($name));

        if ($name === '') {
            throw InvalidConnectionNameException::forName($name);
        }

        return $name;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return class-string<DatabaseDriverInterface>
     */
    public function driverClass(): string
    {
        return $this->driverClass;
    }

    /**
     * @return array<string|int, mixed>
     */
    public function options(): array
    {
        return $this->options;
    }

    public function hasDriverInstance(): bool
    {
        return $this->driver !== null;
    }

    public function driverInstance(): ?DatabaseDriverInterface
    {
        return $this->driver;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
