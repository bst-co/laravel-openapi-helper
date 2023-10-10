<?php

namespace BstCo\LaravelOpenApiHelper\Reflection;

use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionObject;
use ReflectionProperty;
use ReflectionType;

class ReflectionHelper
{
    public static function getObjectProperty(object|string $object, string $property): mixed
    {
        $target = null;

        if (is_string($object) && class_exists($object)) {
            $target = new ReflectionClass($object);
        } else if (is_object($object)) {
            $target = new ReflectionObject($object);
        }

        if ($target instanceof ReflectionClass && $target->hasProperty($property)) {
            foreach ($target->getProperties() as $prop) {
                if ($prop->name === $property) {
                    return is_object($object) ? $prop->getValue($object) : $prop->getDefaultValue();
                }
            }
        }

        return null;
    }

    /**
     * 関数の返却型を取得する
     * @param ReflectionFunctionAbstract|callable $method
     * @return ReflectionType|null
     * @throws ReflectionException
     */
    public static function getReturnType(ReflectionFunctionAbstract|callable $method): ReflectionType|null
    {
        if (is_callable($method)) {
            $method = new ReflectionFunction($method);
        }

        return $method->getReturnType();
    }

    /**
     * PHPDoc を取得/解析する
     *
     * @param ReflectionFunctionAbstract|ReflectionProperty|ReflectionClass|ReflectionClassConstant $method
     * @return Collection|array
     */
    public static function getDocComment(ReflectionFunctionAbstract|ReflectionProperty|ReflectionClass|ReflectionClassConstant $method): Collection|array
    {
        $docs = preg_replace('<((^\s*/?\*+\s)|(\*+/$))>m', '', $method->getDocComment());
        $docs = trim($docs);
        $docs = preg_split('/^\s*@/m', $docs);

        $title       = "";
        $description = "";
        $params      = [];

        foreach ($docs as $index => $doc) {
            if ($index === 0) {
                [$title, $description] = array_pad(preg_split("/(\n|\r|\r\n)+/", $doc, 2), 2, "");
            } else {
                $param = preg_split('/\s+/', $doc, 2);
                $param = array_pad($param, 2, '');

                $key = '@' . strtolower(trim($param[0]));

                if (!isset($param[$key])) {
                    $param[$key] = [];
                }

                $params[$key][] = trim($param[1]);
            }
        }

        return collect([
            'title' => trim($title),
            'description' => trim($description),
            ... $params,
        ]);
    }

    /**
     * ターゲットを継承するクラスのリストを取得する
     * @param string $target
     * @return array
     */
    public static function getImplementClass(string $target): array
    {
        return array_filter(get_declared_classes(), static fn(string $name) => is_subclass_of($name, $target));
    }
}
