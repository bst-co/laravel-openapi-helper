<?php

namespace BstCo\LaravelOpenApiHelper\Reference\Model;

use BstCo\LaravelOpenApiHelper\Reflection\MethodReturnType;
use BstCo\LaravelOpenApiHelper\Reflection\ReflectionHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;

class AttributeProps extends Props
{

    /**
     * @inheritDoc
     */
    public function make(): array
    {
        $class = $this->getReflectionClass();

        foreach ($class->getMethods() as $method) {
            if ($method->isAbstract()) {
                continue;
            }

            if (!str_starts_with($method->getDeclaringClass()->getNamespaceName(), 'App\\')) {
                continue;
            }


            if (preg_match("/^(?P<action>get|set)(?P<name>.+)Attribute$/i", $method->getName(), $matches)) {
                // set*Attribute/getAttribute 方式のプロパティを取得
                $this->setSchema(
                    $matches['name'],
                    $method,
                    type: MethodReturnType::get($method),
                    setter: $matches['action'] === 'set',
                    getter: $matches['action'] === 'get'
                );
            } else {
                $return_type = ReflectionHelper::getReturnType($method);

                if ($return_type instanceof ReflectionNamedType && $return_type->getName() === Attribute::class) {
                    // Attribute型のプロパティを取得
                    try {
                        $invoke = $method->invoke($this->context->model);

                        if ($invoke instanceof Attribute) {
                            if (($callback = $invoke->get) !== null && is_callable($callback)) {
                                $this->setSchema(
                                    object_id: $method->getName(),
                                    method: $method,
                                    type: MethodReturnType::get($callback),
                                    getter: true
                                );
                            }

                            if (($callback = $invoke->set) !== null && is_callable($callback)) {
                                $function = new ReflectionFunction($callback);
                                $arguments = $function->getParameters();

                                if (isset($arguments[0])) {
                                    $this->setSchema(
                                        object_id: $method->getName(),
                                        method: $method,
                                        type: MethodReturnType::get($callback),
                                        setter: true
                                    );
                                }
                            }
                        }
                    } catch (ReflectionException $e) {
                    }
                }
            }
        }

        return array_values($this->schemas);
    }
}
