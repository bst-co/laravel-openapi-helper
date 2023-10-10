<?php

namespace BstCo\LaravelOpenApiHelper\Builder;

use BstCo\LaravelOpenApiHelper\Reference\FormRequestRef;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use Illuminate\Foundation\Http\FormRequest;
use ReflectionNamedType;
use Vyuldashev\LaravelOpenApi\Builders\Paths\Operation\ParametersBuilder;
use Vyuldashev\LaravelOpenApi\RouteInformation;

class ParametersBuildHelper extends ParametersBuilder
{
    /**
     * @inheritDoc
     */
    public function build(RouteInformation $route): array
    {
        /** @var Parameter[] $parameters */
        $parameters = parent::build($route);

        $paths = collect($parameters)
            ->filter(fn(Parameter $parameter) => $parameter->in === Parameter::IN_PATH);

        if (in_array($route->method, ['get', 'head'], true) && count($parameters) === $paths->count()) {
            foreach ($route->actionParameters as $prop) {
                if (!$prop->hasType() && ! ($prop->getType() instanceof ReflectionNamedType)) {
                    continue;
                }

                $type = $prop->getType();

                if ($type instanceof ReflectionNamedType && is_a($type->getName(), FormRequest::class, true)) {
                    $parameters = [...$parameters, ... FormRequestRef::parameters(new ($type->getName()))];
                }

            }
        }

        return $parameters;
    }

}
