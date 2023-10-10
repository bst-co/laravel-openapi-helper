<?php

namespace BstCo\LaravelOpenApiHelper;

class Parameter
{
    public function __construct(
        public ParamType      $type,
        public string|null    $model = null,
        public bool           $nullable = false,
        public Parameter|null $key = null,
        public Parameter|null $value = null,
    )
    {
    }

    public function isObject(): bool
    {
        return $this->type === ParamType::OBJECT || $this->type === ParamType::CLASS_NAME;
    }

    public function isClass(): bool
    {
        return match ($this->type) {
            ParamType::CLASS_NAME => true,
            default => false,
        };
    }

    public function isClassInstance(string $object): bool
    {
        return $this->isClass() && is_a($this->model, $object, true);
    }

    public function isArray(): bool
    {
        return match ($this->type) {
            ParamType::ARRAY => true,
            default => false,
        };
    }

    public function isStandardArray(): bool
    {
        return $this->type === ParamType::ARRAY && !$this->isArrayCasted();
    }

    public function isArrayCasted(): bool
    {
        return $this->type === ParamTYpe::ARRAY && ($this->key !== null || $this->value !== null);
    }

    public function isArrayNumber(): bool
    {
        return $this->type === ParamType::ARRAY && ($this->key === null || $this->key->key === ParamType::INT);
    }

    public function isArrayObject(): bool
    {
        return $this->type === ParamType::ARRAY && !$this->isArrayNumber();
    }

    public function isScalar(): bool
    {
        return match ($this->type) {
            ParamType::STRING,
            ParamType::BOOL,
            ParamType::FLOAT,
            ParamType::INT => true,
            default => false,
        };
    }

    public function isArrayKeyType(): bool
    {
        return match ($this->type) {
            ParamType::STRING,
            ParamType::INT => true,
            default => false,
        };
    }
}
