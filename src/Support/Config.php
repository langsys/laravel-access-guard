<?php

namespace Langsys\AccessGuard\Support;

use BackedEnum;

class Config
{
    /**
     * Resolve a configured table name (falling back to the key itself).
     */
    public static function table(string $name): string
    {
        return config("access-guard.tables.$name", $name);
    }

    /**
     * Normalize a permission/role name — accept a backed enum or a string.
     */
    public static function value(BackedEnum|string $value): string
    {
        return $value instanceof BackedEnum ? (string) $value->value : $value;
    }

    /**
     * @param string|BackedEnum|array<int, string|BackedEnum> $values
     * @return array<int, string>
     */
    public static function values(string|BackedEnum|array $values): array
    {
        $list = is_array($values) ? $values : [$values];

        return array_values(array_filter(array_map(self::value(...), $list)));
    }
}
