<?php

namespace BstCo\LaravelOpenApiHelper\Reflection;

use PhpToken;

class TokenizeHelper
{
    /**
     * @param string $file
     * @return array|PhpToken[]
     */
    public static function tokens(string $file): array
    {
        if (file_exists($file) && is_readable($file)) {
            return PhpToken::tokenize(file_get_contents($file));
        }

        return [];
    }

    /**
     * @param string $file
     * @param string|int|array $kind
     * @return PhpToken[]
     */
    public static function token(string $file, string|int|array $kind): array
    {
        return array_values(array_filter(static::tokens($file), static fn(PhpToken $token): bool => $token->is($kind)));
    }

    /**
     * @param string $file
     * @param string|int|array $kind
     * @return array
     */
    public static function text(string $file, string|int|array $kind): array
    {
        return array_values(array_map(static fn(PhpToken $token) => $token->text, static::token($file, $kind)));
    }

}
