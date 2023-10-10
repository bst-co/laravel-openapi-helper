<?php

namespace BstCo\LaravelOpenApiHelper\Reference;

use BstCo\LaravelOpenApiHelper\Collection\FactoryCollection;
use BstCo\LaravelOpenApiHelper\Collection\SchemaCollection;
use BstCo\LaravelOpenApiHelper\Exception\SchemaFactoryGenerateException;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Str;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

abstract class Referencable
{

    /**
     * @return string
     */
    abstract protected function objectId(): string;

    /**
     * @return string
     */
    public function objectTitle(): string
    {
        return Str::headline($this->objectId());
    }

    /**
     * Schemaオブジェクトを SchemaFactory に変換して保存と返却する
     * @throws SchemaFactoryGenerateException
     */
    final public function factory(): SchemaFactory
    {
        $objectId = $this->objectId();

        $namespace = SchemaAnonymous::class;
        $className = $objectId . 'Factory';
        $classFqdn = implode('\\', [$namespace, $className]);

        if (FactoryCollection::has($classFqdn)) {
            return FactoryCollection::get($classFqdn);
        }

        if (class_exists($classFqdn)) {
            $factory = app($classFqdn);
            FactoryCollection::set($classFqdn, $factory);
            return $factory;
        }

        /*
         * 対象となる SchemaFactory 継承クラスを生成する
         */
        // デフォルト Schemaオブジェクト を設定
        SchemaCollection::set($objectId, Schema::string($objectId));

        // 対象クラスの定義
        $eval = <<<PHP
namespace $namespace;

use BstCo\LaravelOpenApiHelper\Collection\SchemaCollection;
use GoldSpecDigital\ObjectOrientedOAS\Contracts\SchemaContract;

class $className extends \\$namespace {
    /**
     * @inheritDoc
     */
    public function build(): SchemaContract
    {
        \$objectId = "$objectId";
        return SchemaCollection::get(\$objectId);
    }
}
PHP;

        // クラスを作成する
        eval($eval);

        /*
         * クラスが生成された場合のアクション
         */
        if (class_exists($classFqdn) && is_a($classFqdn, SchemaAnonymous::class, true)) {
            // Laravel に bind する
            app()->bind($classFqdn, function () use ($classFqdn) {
                return new $classFqdn;
            });

            // 対象クラスを生成
            /** @var SchemaAnonymous $factory */
            $factory = app($classFqdn);

            // クラスインスタンスを保存する
            FactoryCollection::set($classFqdn, $factory);

            // Schemaオブジェクトを生成してメモリ上に保存する
            SchemaCollection::set($objectId, $this->schema()->objectId($objectId));

            return $factory;
        }

        throw new SchemaFactoryGenerateException("Schema Factory generation error at " . $objectId);
    }

    /**
     * 対象オブジェクトのSchemaオブジェクトを生成する
     * @return Schema
     */
    abstract protected function schema(): Schema;

}
