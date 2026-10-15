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

use Derafu\Form\Contract\FormInterface;
use InvalidArgumentException;

/**
 * Interface for form factories.
 */
interface FormFactoryInterface
{
    /**
     * Creates a new form instance from the provided definition.
     *
     * If `schema` or `uischema` are not provided, they will be automatically
     * generated based on the data.
     *
     * @param array<string, mixed> $definition The form definition with schema, uischema and data.
     * @return FormInterface A new form instance.
     * @throws InvalidArgumentException If the definition is invalid.
     */
    public function create(array $definition): FormInterface;
}
