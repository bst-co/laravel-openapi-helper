<?php

namespace BstCo\LaravelOpenApiHelper\Collection;

use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

/**
 * SchemaFactory のオンメモリキャッシュ用コレクション
 */
class FactoryCollection
{
    /** @var Array<string, SchemaFactory> */
    protected static array $factories = [];

    public static function get(string $name): ?SchemaFactory
    {
        return static::$factories[$name] ?? null;
    }

    public static function set(string $name, SchemaFactory $factory): void
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
