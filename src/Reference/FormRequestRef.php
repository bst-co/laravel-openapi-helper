<?php

namespace BstCo\LaravelOpenApiHelper\Reference;

use BackedEnum;
use BstCo\LaravelOpenApiHelper\Exception\ReferenceTypeException;
use BstCo\LaravelOpenApiHelper\Exception\SchemaFactoryGenerateException;
use BstCo\LaravelOpenApiHelper\Reflection\ReflectionHelper;
use BstCo\LaravelOpenApiHelper\UseDefaults;
use GoldSpecDigital\ObjectOrientedOAS\Exceptions\InvalidArgumentException;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Parameter;
use GoldSpecDigital\ObjectOrientedOAS\Objects\Schema;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationRuleParser;
use ReflectionClass;
use ReflectionException;
use Str;
use Vyuldashev\LaravelOpenApi\Factories\SchemaFactory;

class FormRequestRef extends Referencable
{

    /** @var Array<string, Schema> */
    private array $schemas = [];

    /** @var string[] */
    private array $requires = [];

    private ReflectionClass $classRef;

    /**
     * @param FormRequest $request
     * @throws InvalidArgumentException
     */
    protected function __construct(
        protected readonly FormRequest $request
    )
    {
        $this->classRef = new ReflectionClass($this->request);

        $rules = method_exists($this->request, 'rules') ? $this->request->rules() : null;

        if (is_array($rules)) {
            $validator = Validator::make([], $rules, $this->request->messages(), $this->request->attributes());

            foreach ($validator->getRulesWithoutPlaceholders() as $key => $rules) {
                $this->parse($key, $this->parseRules($rules));
            }
        }
    }

    protected function objectId(): string
    {
        return $this->classRef->getShortName() . "RequestSchema";
    }

    /**
     * @param FormRequest $request
     * @return SchemaFactory
     * @throws InvalidArgumentException
     * @throws SchemaFactoryGenerateException
     */
    public static function make(FormRequest $request): SchemaFactory
    {
        return (new static($request))->factory();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function schema(): Schema
    {
        $schemas = [];

        foreach ($this->schemas as $key => $schema) {
            $schemas[] = $schema->objectId($key)->title(Str::title($this->getAttribute($key)));
        }

        return Schema::object()
            ->title($this->objectTitle())
            ->properties(... $schemas)
            ->required(... $this->requires);
    }

    /**
     * @param FormRequest $request
     * @param string $type
     * @return Parameter[]
     * @throws InvalidArgumentException
     */
    public static function parameters(FormRequest $request, string $type = Parameter::IN_QUERY): array
    {
        $object = (new static($request));

        $root = [];

        foreach ($object->schemas as $key => $schema) {
            $root[] = Parameter::create($key)
                ->schema($schema)
                ->name($key)
                ->description($object->getAttribute($key))
                ->in($type)
                ->required(in_array($key, $object->requires, true));
        }

        return $root;
    }

    /**
     * @param FormRequest $request
     * @return Parameter[]
     * @throws InvalidArgumentException
     */
    public static function query(FormRequest $request): array
    {
        return static::parameters($request, Parameter::IN_QUERY);
    }

    /**
     * @param FormRequest $request
     * @return Parameter[]
     * @throws InvalidArgumentException
     */
    public static function path(FormRequest $request): array
    {
        return static::parameters($request, Parameter::IN_PATH);
    }

    /**
     * @param FormRequest $request
     * @return Parameter[]
     * @throws InvalidArgumentException
     */
    public static function cookie(FormRequest $request): array
    {
        return static::parameters($request, Parameter::IN_COOKIE);
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getAttribute(string $key): string
    {
        return Arr::get($this->request->attributes(), $key, $key);
    }

    /**
     * パラメータのデフォルト値を取得
     * @param string $key
     * @return mixed
     */
    protected function getDefault(string $key): mixed
    {
        if (in_array(UseDefaults::class, trait_uses_recursive($this->request), true)) {
            return Arr::get($this->request->defaults(), $key);
        }

        return null;
    }

    /**
     * @param array|string $rules
     * @return array
     */
    protected function parseRules(array|string $rules): array
    {

        $_rules = [];

        foreach ($rules as $rule) {
            [$rule, $parameters] = ValidationRuleParser::parse($rule);

            if (is_string($rule)) {
                $_rules[$rule] = $parameters;
            } elseif ($rule instanceof Rule) {
                $_rules[$rule::class] = $rule;
            }
        }

        return $_rules;
    }

    /**
     * @param string $key
     * @param array $rules
     * @throws InvalidArgumentException
     * @throws SchemaFactoryGenerateException
     * @throws ReferenceTypeException
     * @throws ReflectionException
     */
    protected function parse(string $key, array $rules): void
    {
        $required = isset($rules['Required']) ? [$key] : [];


        $schema = Schema::string();

        foreach ($rules as $rule => $parameters) {
            $schema = match ($rule) {
                'Numeric' => Schema::number(),
                'Integer' => Schema::integer(),
                'Decimal' => Schema::number()->format(Schema::FORMAT_DOUBLE),
                'File',
                'Image',
                'Mimes',
                'Mimetypes' => Schema::string()->format(Schema::FORMAT_BINARY),
                'Array' => Schema::array()->items(Schema::string()),
                'Password' => Schema::string()->format(Schema::FORMAT_PASSWORD),
                'Uuid' => Schema::string()->format(Schema::FORMAT_UUID),
                'Accepted', 'Boolean' => Schema::boolean(),
                default => $schema,
            };
        }

        foreach ($rules as $rule => $parameters) {
            $schema = match ($rule) {
                'Nullable' => $schema->nullable(),
                'Min' => $schema->minimum(isset($parameters[0]) ? (int)$parameters[0] : null),
                'Max' => $schema->maximum(isset($parameters[0]) ? (int)$parameters[0] : null),
                'Between' => $schema->minimum(isset($parameters[0]) ? (int)$parameters[0] : null)->maximum(isset($parameters[1]) ? (int)$parameters[1] : null),
                default => $schema,
            };

            if ($rule === Enum::class) {
                $type = ReflectionHelper::getObjectProperty($parameters, 'type');

                if (is_a($type, BackedEnum::class, true)) {
                    $schema = EnumValueRef::make($type)::ref();
                }
            }
        }

        $default = $this->getDefault($key);

        if ($default instanceof BackedEnum) {
            $default = $default->value;
        }

        $schema = $schema->default($default);

        $this->schemas[$key] = $schema;

        if ($required) {
            $this->requires[] = $key;
        }

        if (isset($rules['Confirmed'])) {
            $extra_key = $key . '_confirmation';

            $this->schemas[$extra_key] = $schema->objectId($extra_key);

            if ($required) {
                $this->requires[] = $extra_key;
            }
        }
    }
}
