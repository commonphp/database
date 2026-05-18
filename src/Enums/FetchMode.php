<?php

declare(strict_types=1);

namespace CommonPHP\Database\Enums;

enum FetchMode: int
{
    case FETCH_LAZY = 1;
    case FETCH_ASSOC = 2;
    case FETCH_NUM = 3;
    case FETCH_BOTH = 4;
    case FETCH_OBJ = 5;
    case FETCH_BOUND = 6;
    case FETCH_COLUMN = 7;
    case FETCH_CLASS = 8;
    case FETCH_INTO = 9;
    case FETCH_FUNC = 10;
    case FETCH_NAMED = 11;
    case FETCH_KEY_PAIR = 12;

    public static function default(): self
    {
        return self::FETCH_ASSOC;
    }

    public function isAssociative(): bool
    {
        return $this === self::FETCH_ASSOC || $this === self::FETCH_NAMED;
    }

    public function isNumeric(): bool
    {
        return $this === self::FETCH_NUM;
    }

    public function isObject(): bool
    {
        return $this === self::FETCH_OBJ || $this === self::FETCH_CLASS || $this === self::FETCH_INTO;
    }
}
