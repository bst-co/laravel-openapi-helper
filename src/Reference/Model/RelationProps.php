<?php

namespace BstCo\LaravelOpenApiHelper\Reference\Model;

use BstCo\LaravelOpenApiHelper\Reference\ModelRef;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionException;
use Throwable;

/**
 * ModelオブジェクトのRelationalオブジェクト取得用クラス
 */
class RelationProps extends Props
{

    /**
     * @inheritDoc
     * @throws ReflectionException
     */
    public function make(): array
    {
        $class = $this->getReflectionClass();

        $schemas = [];

        foreach ($class->getMethods() as $method) {
            if ($method->isAbstract()) {
                continue;
            }

            if (!str_starts_with($method->getDeclaringClass()->getNamespaceName(), 'App\\')) {
                continue;
            }

            if ($method->getNumberOfParameters() !== 0) {
                continue;
            }

            $relation = $method->invoke($this->context->model);

            if ($relation instanceof Relation) {
                $name = $this->getPropertyName($method->getName());

                $target = $relation->getRelated();
                $schema = null;

                try {
                    $ref = ModelRef::make($target);
                } catch (Throwable) {
                } finally {
                    $ref = $ref ?? null;
                }

                if ($ref) {
                    if ($relation instanceof HasOne || $relation instanceof HasOneThrough || $relation instanceof BelongsTo) {
                        $schema = $ref::ref($name);
                    } elseif ($relation instanceof HasMany || $relation instanceof BelongsToMany) {
                        $schema = Schema::array($name)->items($ref::ref());
                    }
                }

                if ($this->context->isWith($name)) {
                    $schema = $schema->nullable();
                }

                if ($schema) {
                    $schemas[] = $schema;
                }
            }
        }

        return $schemas;
    }
}
