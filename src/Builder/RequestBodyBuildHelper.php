<?php

namespace BstCo\LaravelOpenApiHelper\Builder;

use BstCo\LaravelOpenApiHelper\Reference\FormRequestRef;
use GoldSpecDigital\ObjectOrientedOAS\Objects\MediaType;
use GoldSpecDigital\ObjectOrientedOAS\Objects\RequestBody;
use Illuminate\Foundation\Http\FormRequest;
use ReflectionNamedType;
use Vyuldashev\LaravelOpenApi\Builders\Paths\Operation\RequestBodyBuilder;
use Vyuldashev\LaravelOpenApi\RouteInformation;

class RequestBodyBuildHelper extends RequestBodyBuilder
{
    public function build(RouteInformation $route): ?RequestBody
    {
        $body = parent::build($route);

        if ($body === null && !in_array($route->method, ['get', 'head'])) {
            foreach ($route->actionParameters as $parameter) {
                if (!$parameter->hasType()) {
                    continue;
                }

                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && is_a($type->getName(), FormRequest::class, true)) {
                    $request = new ($type->getName());
                    $body = RequestBody::create()->content(MediaType::json()->schema(FormRequestRef::make($request)::ref()));
                }
            }
        }

        return $body;
    }
}
