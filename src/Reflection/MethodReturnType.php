<?php

namespace BstCo\LaravelOpenApiHelper\Reflection;


use BstCo\LaravelOpenApiHelper\Parameter;
use BstCo\LaravelOpenApiHelper\Reflection;

class MethodReturnType extends Reflection\MethodObject
{

    /**
     * @return Parameter[]
     */
    public function make(): array
    {
        /** @var string $doc */
        $docs = ReflectionHelper::getDocComment($this->parent ?? $this->callback);
        $doc = $docs->get('@return');

        if (is_array($doc)) {
            $doc = head($doc);
        }

        $type = $this->callback->getReturnType();

        return $this->parseStringType($doc ?? "", $type ?? "");
    }

}
