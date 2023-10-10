<?php

namespace BstCo\LaravelOpenApiHelper;

use BstCo\LaravelOpenApiHelper\Builder\ParametersBuildHelper;
use BstCo\LaravelOpenApiHelper\Builder\RequestBodyBuildHelper;
use BstCo\LaravelOpenApiHelper\Builder\SchemaBuildHelper;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Vyuldashev\LaravelOpenApi\Builders\Components\SchemasBuilder;
use Vyuldashev\LaravelOpenApi\Builders\Paths\Operation\ParametersBuilder;
use Vyuldashev\LaravelOpenApi\Builders\Paths\Operation\RequestBodyBuilder;

class OpenApiHelperServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /**
         * Vyuldashev\LaravelOpenApi\Builders\Components\SchemasBuilder の拡張
         */
        $this->app->extend(SchemasBuilder::class, function (SchemasBuilder $builder) {
            // SchemasBuilder をラップ
            return SchemaBuildHelper::make($builder);
        });

        $this->app->extend(ParametersBuilder::class,
            fn(ParametersBuilder $builder, Application $app) => $app->make(ParametersBuildHelper::class)
        );

        $this->app->extend(RequestBodyBuilder::class,
            fn(RequestBodyBuilder $builder, Application $app) => $app->make(RequestBodyBuildHelper::class)
        );
    }

    public function boot(): void
    {
    }
}
