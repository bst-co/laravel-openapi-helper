<?php

namespace BstCo\LaravelOpenApiHelper\Reference;

use BstCo\LaravelOpenApiHelper\Exception\SchemaFactoryGenerateException;
use BstCo\LaravelOpenApiHelper\Reference\Model\AttributeProps;
use BstCo\LaravelOpenApiHelper\Reference\Model\ColumnProps;
use BstCo\LaravelOpenApiHelper\Reference\Model\RelationProps;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class ModelRef extends Referencable
{
    public ReflectionClass $classRef;

    protected  function __construct(
        public Model $model,
        public array $with = [],
        public array $without = [],
    )
    {
        $this->classRef = new ReflectionClass($this->model);
    }


    /**
     * @inheritDoc
     */
    protected function objectId(): string
    {
        return $this->classRef->getShortName() . "ModelSchema";
    }

    /**
     * @param Model $model
     * @return SchemaFactory
     * @throws SchemaFactoryGenerateException
     */
    public static function make(Model $model): SchemaFactory
    {
        return (new static($model))->factory();
    }

    /**
     * @inheritDoc
     */
    protected function schema(): Schema
    {
        $schema = Schema::object();

        $properties = [
            ... ColumnProps::get($this),
            ... AttributeProps::get($this),
            ... RelationProps::get($this),
        ];

        $required = [];

        foreach ($properties as $property) {
            if ($property instanceof Schema && !$property->nullable) {
                $required[] = $property->objectId;
            }
        }

        return $schema
            ->title($this->objectTitle())
            ->properties(... $properties)
            ->required(... $required);
    }

    public function getVisible(): array
    {
        return [... $this->model->getVisible()];
    }

    public function getHidden(): array
    {
        return [... $this->model->getHidden()];
    }

    public function isVisible(string $object_id): bool
    {
        return !in_array($object_id, $this->getHidden(), true) || in_array($object_id, $this->getVisible(), true);
    }

    public function getWith(): array
    {
        return [... $this->with];
    }

    public function getWithOut(): array
    {
        return [... $this->without];
    }

    public function isWith(string $object_id): bool
    {
        return !in_array($object_id, $this->getWithOut(), true) || in_array($object_id, $this->getWith(), true);
    }

    private array|null $casts;

    public function getCast(string $object_id): string|null
    {
        $casts = $this->casts ?? ($this->casts = $this->model->getCasts());

        return $casts[$object_id] ?? null;
    }
}
