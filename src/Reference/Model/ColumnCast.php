<?php

namespace BstCo\LaravelOpenApiHelper\Reference\Model;

use BackedEnum;
use BstCo\LaravelOpenApiHelper\Reference\EnumValueRef;
use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Throwable;

class ColumnCast
{
    /**
     * @param string $cast
     * @return Schema|Schema[]|null
     */
    public static function cast(string $cast): SchemaContract|array|null
    {
        [$cast, $option] = array_pad(explode(':', $cast, 2), 2, null);

        $schema = match ($cast) {
            'double',
            'real' => Schema::number()->format(Schema::FORMAT_DOUBLE),
            'timestamp',
            'float' => Schema::number()->format(Schema::FORMAT_FLOAT),
            'bool',
            'boolean' => Schema::boolean(),
            'immutable_datetime',
            'datetime' => Schema::string()->format(Schema::FORMAT_DATE_TIME),
            'immutable_date',
            'date' => Schema::string()->format(Schema::FORMAT_DATE),
            'int',
            'integer' => Schema::integer(),
            'encrypted' => match ($option) {
                'array' => Schema::array()->items(Schema::string()),
                'collection',
                'object' => Schema::object()->additionalProperties(Schema::string()),
                default => Schema::string(),
            },
            'array' => Schema::array()->items(Schema::string()),
            'json',
            'collection',
            'object', => Schema::object(),
            default => null,
        };

        if ($schema !== null) {
            return $schema;
        }

        if (is_a($cast, BackedEnum::class, true)) {
            return static::asEnumCast($cast);
        }

        if ($cast === AsEnumArrayObject::class) {
//            return null;
            return Schema::array()->items(static::asEnumCast($option));
        }

        if ($cast === AsEnumCollection::class) {
//            return null;
            return Schema::object()->additionalProperties(static::asEnumCast($option));
        }


        return null;
    }

    /**
     * @param Schema|null $child
     * @return Schema[]|null
     */
    public static function asArrayObject(Schema $child = null): ?array
    {
        return $child
            ? [
                Schema::array()->items($child),
                Schema::object()->additionalProperties($child),
            ]
            : null;
    }

    /**
     * ENUMオブジェクトをSchemaにキャストする
     * @param string|BackedEnum $enum
     * @return Schema|null
     */
    public static function asEnumCast(string|BackedEnum $enum): ?Schema
    {
        try {
            return EnumValueRef::make($enum)::ref();
        } catch (Throwable $e) {

        }

        return null;
    }

}
