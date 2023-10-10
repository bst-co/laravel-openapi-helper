<?php

namespace BstCo\LaravelOpenApiHelper\Reflection;

use BstCo\LaravelOpenApiHelper\Parameter;
use BstCo\LaravelOpenApiHelper\ParamType;
use InvalidArgumentException;
use PhpToken;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

abstract class MethodObject
{
    readonly protected ReflectionFunctionAbstract $callback;
    readonly protected ReflectionFunctionAbstract|null $parent;

    /**
     * @var PhpToken[]
     */
    readonly protected array $uses;


    /**
     * @return Parameter[]
     * @throws ReflectionException
     */
    public static function get(ReflectionFunctionAbstract|callable|string $callback, ReflectionFunctionAbstract|callable|string|null $parent = null): array
    {
        return (new static($callback, $parent))->make();
    }

    /**
     * @throws ReflectionException
     */
    public function __construct(ReflectionFunctionAbstract|callable|string|array $callback, ReflectionFunctionAbstract|callable|string|null $parent = null)
    {
        $_callback = static::reflection($callback);

        if (!$_callback) {
            throw new InvalidArgumentException('Argument[1] is not a callable function.');
        }

        if ($parent) {
            $_parent = static::reflection($parent);

            if (!$_parent) {
                throw new InvalidArgumentException('Argument[2] is not a callable function.');
            }
        }

        $this->callback = $_callback;
        $this->parent = $_parent ?? null;
        $this->uses = TokenizeHelper::text($_callback->getFileName(), T_NAME_QUALIFIED);
    }

    /**
     * @throws ReflectionException
     */
    protected static function reflection(ReflectionFunctionAbstract|callable|string|array $callback): ReflectionFunctionAbstract|null
    {
        if (is_string($callback)) {
            if (class_exists($callback)) {
                $callback = [$callback, '__invoke'];
            } elseif (str_contains($callback, '::')) {
                $callback = array_pad(explode('::', $callback, 2), 2, '');
            } elseif (function_exists($callback)) {
                $callback = new ReflectionFunction($callback);
            }
        }

        if (is_array($callback) && count($callback) >= 2) {
            $callback = array_values($callback);

            if (method_exists($callback[0], $callback[1])) {
                $callback = new ReflectionMethod($callback[0], $callback[1]);
            }
        }

        if (is_callable($callback)) {
            $callback = new ReflectionFunction($callback);
        }

        if ($callback instanceof ReflectionFunctionAbstract) {
            return $callback;
        }

        return null;
    }

    /**
     * 名前空間が省略されたクラス名を補完して返却する
     * @param string $class_name
     * @return string|null
     */
    public function getQualifyClassName(string $class_name): string|null
    {
        if (class_exists($class_name)) {
            return $class_name;
        }

        foreach ($this->uses as $namespace) {
            $namespaces = explode('\\', $namespace);
            $class_scope = explode('\\', $class_name);

            if (last($namespaces) === head($class_scope)) {
                $qualify_class = "";
                if (count($class_scope) === 1) {
                    $qualify_class = $namespace;
                } elseif (count($class_scope) > 1) {
                    $qualify_class = $namespace . '\\' . implode('\\', array_slice($class_scope, 1));
                }

                if ($qualify_class !== "" && class_exists($qualify_class)) {
                    return $qualify_class;
                }
            }
        }

        return null;
    }

    public function parseStringType(string|ReflectionType ...$types): array
    {
        $items = [];

        foreach ($types as $type) {
            if ($type instanceof ReflectionUnionType) {
                foreach ($type->getTypes() as $value) {
                    $items = [... $items, $this->parseStringType($value->getName())];
                }
            }

            if ($type instanceof ReflectionNamedType) {
                $item = $this->parseType($type->getName(), $type->allowsNull());

                if ($item) {
                    $items[] = $item;
                }
            }

            if (is_string($type) && preg_match_all("/(?<type>[^()&|]+(&[^()&|]+)?)+/", $type, $matches)) {
                foreach ($matches['type'] as $value) {
                    $item = $this->parseType($value);

                    if ($item) {
                        $items[] = $item;
                    }
                }
            }
        }

        return $items;
    }

    public function parseType(string $type, bool $nullable = false): Parameter|null
    {
        $type = trim($type);

        if (str_starts_with($type, '?')) {
            $nullable = true;
            $type     = ltrim($type, '?');
        }

        if (empty($type) || $type === 'void') {
            return null;
        }

        if (preg_match('/^array<(?P<value>[^>]+)>$/i', $type, $matches)) {
            $values = explode(',', $matches['value']);
            $values = array_map('trim', $values);
            $values = array_values($values);

            if (count($values) >= 2) {
                $key = $this->parseType($values[0]);
                $value = $this->parseType($values[1]);

                if ($key && $value && $key->isArrayKeyType()) {
                    return new Parameter(ParamType::ARRAY, nullable: $nullable, key: $key, value: $value);
                }
            }
        }

        if (preg_match('/(?P<value>.+)\[]$/', $type, $matches)) {
            $value = $this->parseType($matches['value']);

            if ($value) {
                return new Parameter(ParamType::ARRAY, nullable: $nullable, value: $value);
            }
        }

        $typed = match (strtolower($type)) {
            'int' => new Parameter(ParamType::INT, nullable: $nullable),
            'float' => new Parameter(ParamType::FLOAT, nullable: $nullable),
            'string' => new Parameter(ParamType::STRING, nullable: $nullable),
            'bool',
            'boolean',
            'true',
            'false' => new Parameter(ParamType::BOOL, nullable: $nullable),
            'mixed' => new Parameter(ParamType::MIXED, nullable: $nullable),
            'object' => new Parameter(ParamType::OBJECT, nullable: $nullable),
            'self',
            'static',
            'parent' => new Parameter(ParamType::CLASS_NAME, $this->callback->getDeclaringClass()->getName(), nullable: $nullable),
            'array',
            'iterable' => new Parameter(ParamType::ARRAY, nullable: $nullable),
            default => null,
        };

        if (!$typed && ($class_name = $this->getQualifyClassName($type)) !== null) {
            $typed = new Parameter(ParamType::CLASS_NAME, $class_name, nullable: $nullable);
        }

        return $typed;
    }


    /**
     * @return Parameter[]
     */
    abstract public function make(): array;
}
