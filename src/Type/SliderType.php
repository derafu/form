<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Type;

use Derafu\Form\Abstract\AbstractType;

/**
 * Represents a slider type in the form.
 *
 * A slider type allows users to enter a value between a minimum and maximum.
 */
final class SliderType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'slider';
    }

    /**
     * {@inheritDoc}
     */
    public function validateValue(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * {@inheritDoc}
     */
    public function castValue(mixed $value): mixed
    {
        if ((int)$value == $value) {
            return (int)$value;
        }

        return (float)$value;
    }
}
