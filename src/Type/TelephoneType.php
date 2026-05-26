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
 * Represents a telephone type in the form.
 *
 * A telephone type allows users to enter a telephone number.
 */
final class TelephoneType extends AbstractType
{
    /**
     * The pattern for the tel type.
     * Accepts international phone numbers in different formats,
     * including spaces, parentheses, dashes, plus sign, etc.
     *
     * @var string
     */
    public const PATTERN = '^(\+?\d{1,4})?[-.\s]?\(?\d{1,4}\)?[-.\s]?\d{1,5}[-.\s]?\d{1,9}$';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'telephone';
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
    protected function getDefaultOptions(): array
    {
        return [
            'pattern' => self::PATTERN,
        ];
    }
}
