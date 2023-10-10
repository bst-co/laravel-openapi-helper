<?php

namespace BstCo\LaravelOpenApiHelper\Reference\Model;

use BstCo\LaravelOpenApiHelper\Parameter;
use BstCo\LaravelOpenApiHelper\ParamType;
use BstCo\LaravelOpenApiHelper\Reference\ModelRef;
use BstCo\LaravelOpenApiHelper\Reflection\ReflectionHelper;
use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionFunctionAbstract;

abstract class Props
{
    /** @var Schema[] */
    protected array $schemas = [];

    public static function get(ModelRef $context): array
    {
        return (new static($context))->make();
    }

    public function __construct(
        readonly protected ModelRef $context
    )
    {
    }

    /**
     * @return Schema[]
     */
    abstract public function make(): array;

    /**
     * @param string $object_id
     * @return Schema|null
     */
    protected function getSchema(string $object_id): ?SchemaContract
    {
        return $this->schemas[$object_id] ?? null;
    }

    /**
     * @param string $object_id
     * @param ReflectionFunctionAbstract $method
     * @param Parameter[] $type
     * @param bool $setter
     * @param bool $getter
     * @return void
     */
    protected function setSchema(string $object_id, ReflectionFunctionAbstract $method, array $type = [], bool $setter = false, bool $getter = false): void
    {
        $object_id = $this->getPropertyName($object_id);
        $schema    = $this->getSchema($object_id);

        if ($schema === null) {
            $doc         = ReflectionHelper::getDocComment($method);
            $title       = $doc->get('title');
            $description = $doc->get('description');

            $schema = Schema::create($object_id)
                ->type(Schema::TYPE_STRING)
                ->title(empty($title) ? $object_id : $title)
                ->description(empty($description) ? null : $description);

            $schema = $this->binding($schema, $method, $type);
        }

        if ($setter) {
            $schema = $schema->writeOnly();
        }

        if ($getter) {
            $schema = $schema->readOnly();
        }

        if ($schema->readOnly && $schema->readOnly === $schema->writeOnly) {
            $schema = $schema->readOnly(null)->writeOnly(null);
        }

        $this->schemas[$object_id] = $schema;
    }

    protected function getPropertyName(string $name): string
    {
        return Str::snake($name);
    }

    protected function getReflectionClass(): ReflectionClass
    {
        return new ReflectionClass($this->context->model);
    }

    protected function castSchemaType(Schema $schema, string $cast): SchemaContract
    {
//        if (($r = CastEnum::make($cast)->cast($schema)) !== null) {
//            return $r;
//        }

        [$type, $format] = match ($cast) {
            'double',
            'real' => [Schema::TYPE_NUMBER, Schema::FORMAT_DOUBLE],
            'timestamp',
            'float' => [Schema::TYPE_NUMBER, Schema::FORMAT_FLOAT],
            'bool',
            'boolean' => [Schema::TYPE_BOOLEAN, null],
            'immutable_datetime',
            'datetime' => [Schema::TYPE_STRING, Schema::FORMAT_DATE_TIME],
            'immutable_date',
            'date' => [Schema::TYPE_STRING, Schema::FORMAT_DATE],
            'int',
            'integer' => [Schema::TYPE_INTEGER, null],
            'array',
            'json',
            'encrypted:array' => [Schema::TYPE_ARRAY, null],
            'object',
            'encrypted:collection',
            'encrypted:object' => [Schema::TYPE_OBJECT, null],
            default => [Schema::TYPE_STRING, null],
        };

        $schema = $schema->type($type)->format($format);

        if ($type === Schema::TYPE_ARRAY) {
            $schema = $schema->items(Schema::string());
        }

        return $schema;
    }

    /**
     * @param Schema $schema
     * @param ReflectionFunctionAbstract $method
     * @param Parameter[] $types
     * @return Schema
     */
    protected function binding(Schema $schema, ReflectionFunctionAbstract $method, array $types = []): SchemaContract
    {
        if (count($types) === 0) {
            return $schema;
        }

        $type = head($types);

        if ($type->isArrayNumber()) {

            $schema = $schema->type(Schema::TYPE_ARRAY);

            if ($type->value) {
                $value = self::binding(Schema::string(), $method, [$type->value]);

                $schema = $schema->items($value);
            }

        } else if ($type->isArrayObject()) {
            $schema = $schema->type(Schema::TYPE_OBJECT);

            if ($type->value) {
                $value  = self::binding(Schema::string(), $method, [$type->value]);
                $schema = $schema->additionalProperties($value);
            }
        } else {
            $schema = match ($type->type) {
                ParamType::STRING => $schema->type(Schema::TYPE_STRING),
                ParamType::BOOL => $schema->type(Schema::TYPE_BOOLEAN),
                ParamType::INT => $schema->type(Schema::TYPE_INTEGER),
                ParamType::FLOAT => $schema->type(Schema::TYPE_NUMBER)->format(Schema::FORMAT_FLOAT),
                ParamType::OBJECT => $schema->type(Schema::TYPE_OBJECT),
                default => $schema
            };
        }


        return $schema;
    }

}
