<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Factory;

use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;

/**
 * Factory interface for creating UiSchemaElement instances.
 */
interface UiSchemaElementFactoryInterface
{
    /**
     * Create a UiSchemaElement instance from an array.
     *
     * @param array<string, mixed> $definition The UI schema definition as an array.
     * @return UiSchemaElementInterface The created UiSchemaElement instance.
     */
    public static function create(array $definition): UiSchemaElementInterface;
}
