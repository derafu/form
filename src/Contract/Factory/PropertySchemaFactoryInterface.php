<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Factory;

use Derafu\Form\Contract\Schema\PropertySchemaInterface;

/**
 * Creates the appropriate PropertySchemaInterface implementation for a given
 * JSON Schema property definition array.
 *
 * Implementations dispatch on the `type` key and return the concrete schema
 * class that matches it (StringSchema, IntegerSchema, ArraySchema, …).
 */
interface PropertySchemaFactoryInterface
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
    public static function create(array $definition): PropertySchemaInterface;
}
