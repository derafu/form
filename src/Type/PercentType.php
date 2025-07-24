<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Type;

use Derafu\Form\Abstract\AbstractType;

/**
 * Represents a percent type in the form.
 *
 * A percent type allows users to enter a percentage value.
 */
final class PercentType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'percent';
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

    /**
     * {@inheritDoc}
     */
    public function getJsonSchema(): array
    {
        return [
            'type' => 'number',
            'format' => 'percent',
        ];
    }
}
