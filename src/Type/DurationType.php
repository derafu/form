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
 * Represents a duration type in the form.
 *
 * A duration type allows users to enter a duration value.
 */
final class DurationType extends AbstractType
{
    /**
     * The pattern for the duration type.
     *
     * @var string
     */
    public const PATTERN = '^P(?:(\d+)Y)?(?:(\d+)M)?(?:(\d+)D)?T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'duration';
    }

    /**
     * {@inheritDoc}
     */
    protected function getDefaultOptions(): array
    {
        return [
            'pattern' => self::PATTERN,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validateValue(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return preg_match($this->getRegex(), $value) === 1;
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonSchema(): array
    {
        return [
            'type' => 'string',
            'format' => 'duration',
        ];
    }
}
