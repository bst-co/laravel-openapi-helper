<?php

namespace BstCo\LaravelOpenApiHelper;

use BstCo\LaravelOpenApiHelper\Exception\ReferenceTypeException;
use BstCo\LaravelOpenApiHelper\Exception\SchemaFactoryGenerateException;
use BstCo\LaravelOpenApiHelper\Reference\FormRequestRef;
use BstCo\LaravelOpenApiHelper\Reference\ModelRef;
use GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use UnitEnum;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class SchemaRef
{
    /**
     * @param string|Model|UnitEnum|FormRequest $ref
     * @param string|null $objectId
     * @return Schema
     * @throws SchemaFactoryGenerateException
     * @throws InvalidArgumentException
     * @throws ReferenceTypeException
     */
    public static function ref(string|Model|UnitEnum|FormRequest $ref, string $objectId = null): Schema
    {
        $schema = null;


        if (is_a($ref, Model::class, true)) {
            $schema = static::model($ref);
        } else if (is_a($ref, UnitEnum::class, true)) {
            $schema = static::enum($ref);
        } else if (is_a($ref, FormRequest::class, true)) {
            $schema = static::formRequest($ref);
        }

        if ($schema instanceof SchemaFactory) {
            return $schema::ref($objectId);
        }

        throw new ReferenceTypeException('Object name ' . ((string)$ref) . ' is not supported.');
    }

    public static function model(string|Model $ref): SchemaFactory
    {
        if (is_string($ref) && is_a($ref, Model::class, true)) {
            $ref = new $ref;
        }

        return ModelRef::make($ref);
    }

    public static function enum(string|UnitEnum $ref): SchemaFactory
    {
        dd($ref);
    }

    /**
     * @param string|FormRequest $ref
     * @return SchemaFactory
     * @throws Exception\SchemaFactoryGenerateException
     * @throws InvalidArgumentException
     */
    public static function formRequest(string|FormRequest $ref): SchemaFactory
    {
        if (is_string($ref)) {
            $ref = new $ref;
        }

        return FormRequestRef::make($ref);
    }
}
