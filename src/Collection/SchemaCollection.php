<?php

namespace BstCo\LaravelOpenApiHelper\Collection;

use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;

/**
 * Schema のオンメモリキャッシュ用コレクション
 */
class SchemaCollection
{
    /** @var Schema[] */
    protected static array $factories = [];

    public static function get(string $name): ?Schema
    {
        return static::$factories[$name] ?? null;
    }

    public static function set(string $name, Schema $factory): void
    {
        static::$factories[$name] = $factory;
    }

    public static function has(string $name): bool{
        return isset(static::$factories[$name]);
    }

    /**
     * @return string[]
     */
    public static function names(): array
    {
        return array_keys(static::$factories);
    }
}
