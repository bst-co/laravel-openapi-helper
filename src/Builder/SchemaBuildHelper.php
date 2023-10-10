<?php

namespace BstCo\LaravelOpenApiHelper\Builder;

use BstCo\LaravelOpenApiHelper\Collection\FactoryCollection;
use Vyuldashev\LaravelOpenApi\Builders\Components\SchemasBuilder;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;
use Vyuldashev\LaravelOpenApi\Generator;

/**
 * SchemasBuilders の上書き処理
 */
class SchemaBuildHelper extends SchemasBuilder
{
    /**
     * @param SchemasBuilder $builder
     * @return static
     */
    public static function make(SchemasBuilder $builder): static {
        return new static($builder->directories);
    }

    /**
     * @param string $collection
     * @return array
     */
    public function build(string $collection = Generator::COLLECTION_DEFAULT): array
    {
        return [
            // オリジナルのSchemaを取得
            ... parent::build($collection),
            // プラグインで生成されたSchemaFactoryを追加
            ... collect(FactoryCollection::names())
                ->map(fn(string $v) => FactoryCollection::get($v))
                ->filter(fn($factory) => $factory instanceof SchemaFactory)
                ->map(fn(SchemaFactory $factory) => $factory->build())
                ->values()
                ->toArray()
        ];
    }

}
