<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Schema;

use Derafu\Form\Contract\Schema\PropertySchemaInterface;

/**
 * Creates the appropriate PropertySchemaInterface implementation for a given
 * JSON Schema definition array.
 *
 * This factory centralises the type-dispatch logic that was previously
 * duplicated across FormSchema::fromArray() and ObjectSchema::fromArray().
 */
final class PropertySchemaFactory
{
    /**
     * Creates a PropertySchemaInterface instance from a definition array.
     *
     * Dispatches to the concrete schema class that matches the `type` key.
     * Defaults to StringSchema when the type is absent or unrecognised.
     *
     * @param array $definition The JSON Schema property definition, including
     *   a `name` key that identifies the property within its parent schema.
     * @return PropertySchemaInterface
     */
    public static function create(array $definition): PropertySchemaInterface
    {
        $type = $definition['type'] ?? 'string';

        return match ($type) {
            'string'  => StringSchema::fromArray($definition),
            'number'  => NumberSchema::fromArray($definition),
            'integer' => IntegerSchema::fromArray($definition),
            'array'   => ArraySchema::fromArray($definition),
            'object'  => ObjectSchema::fromArray($definition),
            'boolean' => BooleanSchema::fromArray($definition),
            'null'    => NullSchema::fromArray($definition),
            default   => StringSchema::fromArray($definition),
        };
    }
}
