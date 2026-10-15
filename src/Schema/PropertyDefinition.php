<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Schema;

use Derafu\Support\ArrayReader;
use Derafu\Support\ArrayType;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Immutable definition of a property, as read from a JSON Schema fragment.
 *
 * It is the validated form of the array received by the `fromArray()` of the
 * property schemas: each key is checked and typed when the object is created,
 * so the rest of the code can trust its getters instead of the array shape.
 *
 * Nested schemas (`items`, `properties`, `$defs`, `definitions`) are kept as
 * raw arrays because they are resolved by the schemas that own them.
 */
final readonly class PropertyDefinition
{
    /**
     * @param string $name
     * @param string|list<string>|null $type The type, or the list of types of a
     * schema that accepts more than one (`["string", "null"]`).
     * @param string|null $title
     * @param string|null $description
     * @param mixed $default
     * @param bool $hasDefault Whether the "default" key was present.
     * @param bool|null $deprecated
     * @param list<mixed>|null $examples
     * @param bool|null $readOnly
     * @param bool|null $writeOnly
     * @param list<bool|float|int|string>|null $enum
     * @param mixed $const
     * @param bool $hasConst Whether the "const" key was present.
     * @param list<array<string, mixed>>|null $allOf
     * @param list<array<string, mixed>>|null $anyOf
     * @param list<array<string, mixed>>|null $oneOf
     * @param string|null $format
     * @param int|null $maxLength
     * @param int|null $minLength
     * @param string|null $pattern
     * @param string|null $contentMediaType
     * @param float|int|null $minimum
     * @param float|int|null $maximum
     * @param float|int|null $exclusiveMinimum
     * @param float|int|null $exclusiveMaximum
     * @param float|int|null $multipleOf
     * @param array<string, mixed>|bool|null $items
     * @param int|null $maxItems
     * @param int|null $minItems
     * @param array<string, mixed>|null $contains
     * @param int|null $maxContains
     * @param int|null $minContains
     * @param bool|null $uniqueItems
     * @param list<string>|null $required
     * @param array<string, list<string>>|null $dependentRequired
     * @param int|null $maxProperties
     * @param int|null $minProperties
     * @param bool|array<string, mixed>|null $additionalProperties
     * @param array<string, array<string, mixed>>|null $properties
     * @param array<string, array<string, mixed>>|null $defs The "$defs" key.
     * @param array<string, array<string, mixed>>|null $definitions
     */
    public function __construct(
        public string $name = '',
        public string|array|null $type = null,
        public ?string $title = null,
        public ?string $description = null,
        public mixed $default = null,
        public bool $hasDefault = false,
        public ?bool $deprecated = null,
        public ?array $examples = null,
        public ?bool $readOnly = null,
        public ?bool $writeOnly = null,
        public ?array $enum = null,
        public mixed $const = null,
        public bool $hasConst = false,
        public ?array $allOf = null,
        public ?array $anyOf = null,
        public ?array $oneOf = null,
        public ?string $format = null,
        public ?int $maxLength = null,
        public ?int $minLength = null,
        public ?string $pattern = null,
        public ?string $contentMediaType = null,
        public int|float|null $minimum = null,
        public int|float|null $maximum = null,
        public int|float|null $exclusiveMinimum = null,
        public int|float|null $exclusiveMaximum = null,
        public int|float|null $multipleOf = null,
        public array|bool|null $items = null,
        public ?int $maxItems = null,
        public ?int $minItems = null,
        public ?array $contains = null,
        public ?int $maxContains = null,
        public ?int $minContains = null,
        public ?bool $uniqueItems = null,
        public ?array $required = null,
        public ?array $dependentRequired = null,
        public ?int $maxProperties = null,
        public ?int $minProperties = null,
        public bool|array|null $additionalProperties = null,
        public ?array $properties = null,
        public ?array $defs = null,
        public ?array $definitions = null,
    ) {
    }

    /**
     * Creates a definition from a raw array, validating the type of each key.
     *
     * Unknown keys are ignored. Keys whose value is `null` are treated as
     * absent, except `default` and `const`, where `null` is a value (see
     * `$hasDefault` and `$hasConst`). Nothing is coerced: `"3"` is not an
     * integer.
     *
     * @param array<string, mixed> $definition
     * @throws InvalidArgumentException If a known key has a wrong type.
     */
    public static function fromArray(array $definition): self
    {
        $reader = ArrayReader::from($definition);

        return new self(
            name: $reader->string('name') ?? '',
            type: $reader->get('type', ArrayType::oneOf(ArrayType::string(), ArrayType::listOf(ArrayType::string()))),
            title: $reader->string('title'),
            description: $reader->string('description'),
            default: $definition['default'] ?? null,
            hasDefault: array_key_exists('default', $definition),
            deprecated: $reader->bool('deprecated'),
            examples: $reader->list('examples'),
            readOnly: $reader->bool('readOnly'),
            writeOnly: $reader->bool('writeOnly'),
            enum: $reader->scalarList('enum'),
            const: $definition['const'] ?? null,
            hasConst: array_key_exists('const', $definition),
            allOf: $reader->listOf('allOf', ArrayType::map()),
            anyOf: $reader->listOf('anyOf', ArrayType::map()),
            oneOf: $reader->listOf('oneOf', ArrayType::map()),
            format: $reader->string('format'),
            maxLength: $reader->int('maxLength'),
            minLength: $reader->int('minLength'),
            pattern: $reader->string('pattern'),
            contentMediaType: $reader->string('contentMediaType'),
            minimum: $reader->number('minimum'),
            maximum: $reader->number('maximum'),
            exclusiveMinimum: $reader->number('exclusiveMinimum'),
            exclusiveMaximum: $reader->number('exclusiveMaximum'),
            multipleOf: $reader->number('multipleOf'),
            items: $reader->get('items', ArrayType::oneOf(ArrayType::map(), ArrayType::bool())),
            maxItems: $reader->int('maxItems'),
            minItems: $reader->int('minItems'),
            contains: $reader->map('contains'),
            maxContains: $reader->int('maxContains'),
            minContains: $reader->int('minContains'),
            uniqueItems: $reader->bool('uniqueItems'),
            required: $reader->stringList('required'),
            dependentRequired: $reader->mapOf('dependentRequired', ArrayType::listOf(ArrayType::string())),
            maxProperties: $reader->int('maxProperties'),
            minProperties: $reader->int('minProperties'),
            additionalProperties: $reader->get(
                'additionalProperties',
                ArrayType::oneOf(ArrayType::bool(), ArrayType::map())
            ),
            properties: $reader->mapOf('properties', ArrayType::map()),
            defs: $reader->mapOf('$defs', ArrayType::map()),
            definitions: $reader->mapOf('definitions', ArrayType::map()),
        );
    }

    /**
     * Returns the definition as a JSON Schema array.
     *
     * Only the keys that were defined are included.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'deprecated' => $this->deprecated,
            'examples' => $this->examples,
            'readOnly' => $this->readOnly,
            'writeOnly' => $this->writeOnly,
            'enum' => $this->enum,
            'allOf' => $this->allOf,
            'anyOf' => $this->anyOf,
            'oneOf' => $this->oneOf,
            'format' => $this->format,
            'maxLength' => $this->maxLength,
            'minLength' => $this->minLength,
            'pattern' => $this->pattern,
            'contentMediaType' => $this->contentMediaType,
            'minimum' => $this->minimum,
            'maximum' => $this->maximum,
            'exclusiveMinimum' => $this->exclusiveMinimum,
            'exclusiveMaximum' => $this->exclusiveMaximum,
            'multipleOf' => $this->multipleOf,
            'items' => $this->items,
            'maxItems' => $this->maxItems,
            'minItems' => $this->minItems,
            'contains' => $this->contains,
            'maxContains' => $this->maxContains,
            'minContains' => $this->minContains,
            'uniqueItems' => $this->uniqueItems,
            'required' => $this->required,
            'dependentRequired' => $this->dependentRequired,
            'maxProperties' => $this->maxProperties,
            'minProperties' => $this->minProperties,
            'additionalProperties' => $this->additionalProperties,
            'properties' => $this->properties,
            '$defs' => $this->defs,
            'definitions' => $this->definitions,
        ];

        $array = array_filter($array, static fn (mixed $v): bool => $v !== null);

        if ($this->hasDefault) {
            $array['default'] = $this->default;
        }

        if ($this->hasConst) {
            $array['const'] = $this->const;
        }

        return $array;
    }
}
