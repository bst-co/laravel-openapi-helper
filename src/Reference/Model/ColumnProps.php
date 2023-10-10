<?php

namespace BstCo\LaravelOpenApiHelper\Reference\Model;

use DB;
use Doctrine\DBAL;
use GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException;
use GoldSpecDigital\ObjectOrientedOAS\Objects\OneOf;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Illuminate\Support\Str;

class ColumnProps extends Props
{
    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     */
    public function make(): array
    {
        $table_name = $this->context->model->getTable();

        $column_names = DB::getSchemaBuilder()->getColumnListing($table_name);

        $properties = [];

        foreach ($column_names as $column_name) {
            if (!$this->context->isVisible($column_name)) {
                continue;
            }

            $column = DB::connection()->getDoctrineColumn($table_name, $column_name);

            $cast = $this->context->getCast($column_name);

            $props = null;

            if ($cast) {
                $props = ColumnCast::cast($cast);
            }

            if (!$props) {
                $props = match ($column->getType()::class) {
                    DBAL\Types\SmallIntType::class => Schema::number()->format(Schema::FORMAT_INT32),
                    DBAL\Types\IntegerType::class => Schema::number(),
                    DBAL\Types\BigIntType::class => Schema::number()->format(Schema::FORMAT_INT64),
                    DBAL\Types\DateType::class => Schema::string()->format(Schema::FORMAT_DATE),
                    DBAL\Types\DateTimeType::class => Schema::string()->format(Schema::FORMAT_DATE_TIME),
                    DBAL\Types\FloatType::class => Schema::integer()->format(Schema::FORMAT_FLOAT),
                    DBAL\Types\DecimalType::class => Schema::integer()->format(Schema::FORMAT_DOUBLE),
                    DBAL\Types\BooleanType::class => Schema::boolean(),
                    DBAL\Types\BlobType::class,
                    DBAL\Types\BinaryType::class => Schema::string()->format(Schema::FORMAT_BINARY),
                    DBAL\Types\GuidType::class => Schema::string()->format(Schema::FORMAT_UUID),
                    default => Schema::string(),
                };
            }

            if (!is_array($props)) {
                $props = [$props];
            }

            foreach ($props as $index => $prop) {
                $scalar = !in_array($prop->type, [Schema::TYPE_OBJECT, Schema::TYPE_ARRAY], true);

                $prop = $prop
                    ->default($scalar ? $column->getDefault() : null)
                    ->title(Str::headline($column_name))
                    ->description($column->getComment());

                if ($column->getAutoincrement()) {
                    $prop = $prop->readOnly();
                }

                if ($scalar && $column->getLength()) {
                    $prop = $prop->maximum($column->getLength());
                }

                if (!$column->getNotnull()) {
                    $prop = $prop->nullable();
                }

                if (!$this->context->model->isFillable($column_name)) {
                    $prop = $prop->readOnly();
                }

                $props[$index] = $prop;
            }

            if (count($props) > 1) {
                $property = OneOf::create($column_name)->schemas(... $props);
            } else if (count($props) === 1) {
                $property = current($props)->objectId($column_name);
            } else {
                $property = null;
            }

            if ($property) {
                $properties[] = $property;
            }
        }

        return $properties;
    }
}
