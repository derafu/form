<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Factory;

use Derafu\Form\Contract\Factory\PropertySchemaFactoryInterface;
use Derafu\Form\Contract\Schema\PropertySchemaInterface;
use Derafu\Form\Schema\ArraySchema;
use Derafu\Form\Schema\BooleanSchema;
use Derafu\Form\Schema\IntegerSchema;
use Derafu\Form\Schema\NullSchema;
use Derafu\Form\Schema\NumberSchema;
use Derafu\Form\Schema\ObjectSchema;
use Derafu\Form\Schema\StringSchema;

/**
 * Creates the appropriate PropertySchemaInterface implementation for a given
 * JSON Schema definition array.
 *
 * This factory centralises the type-dispatch logic that was previously
 * duplicated across FormSchema::fromArray() and ObjectSchema::fromArray().
 */
final class PropertySchemaFactory implements PropertySchemaFactoryInterface
{
    /**
     * {@inheritDoc}
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
