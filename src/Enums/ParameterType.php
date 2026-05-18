<?php

declare(strict_types=1);

namespace CommonPHP\Database\Enums;

enum ParameterType
{
    case Named;
    case Positional;
    case Empty;

    public static function detect(array $parameters): self
    {
        if ($parameters === []) {
            return self::Empty;
        }

        foreach (array_keys($parameters) as $key) {
            if (is_string($key)) {
                return self::Named;
            }
        }

        return self::Positional;
    }

    public function isNamed(): bool
    {
        return $this === self::Named;
    }

    public function isPositional(): bool
    {
        return $this === self::Positional;
    }

    public function isEmpty(): bool
    {
        return $this === self::Empty;
    }
}
