<?php

namespace BstCo\LaravelOpenApiHelper\Reference;

use BackedEnum;
use BstCo\LaravelOpenApiHelper\Exception\ReferenceTypeException;
use BstCo\LaravelOpenApiHelper\Exception\SchemaFactoryGenerateException;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use ReflectionClass;
use ReflectionException;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

/**
 */
class EnumValueRef extends Referencable
{
    public ReflectionClass $classRef;

    /**
     * @param string|BackedEnum $model
     * @throws ReferenceTypeException
     * @throws ReflectionException
     */
    protected function __construct(
        public string|BackedEnum $model,
    )
    {
        if (!is_a($model, BackedEnum::class, true)) {
            throw new ReferenceTypeException($this->model . ' is not BackedEnum class.');
        }

        $this->classRef = new ReflectionClass($this->model);
    }

    /**
     * @param string|BackedEnum $model
     * @return SchemaFactory
     * @throws ReferenceTypeException
     * @throws ReflectionException
     * @throws SchemaFactoryGenerateException
     */
    public static function make(string|BackedEnum $model): SchemaFactory
    {
        return (new static($model))->factory();
    }

    /**
     * @inheritDoc
     */
    protected function objectId(): string
    {
        return $this->classRef->getShortName() . "EnumValueSchema";

    }

    /**
     * @inheritDoc
     */
    protected function schema(): Schema
    {
        $values = collect($this->model::cases())
            ->map(fn(BackedEnum $value) => $value->value)
            ->flatten()
            ->toArray();

        $schema = null;

        if (count($values) > 0) {
            $value = current($values);

            if (is_string($value)) {
                $schema = Schema::string()->enum(...$values);
            } else

            if (is_int($value)) {
                $schema = Schema::number()->enum(... $values);
            }
        }

        $schema = $schema ?? Schema::string();

        return $schema->title($this->objectTitle());
    }
}
