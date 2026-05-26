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

use DateTimeImmutable;
use Derafu\Form\Abstract\AbstractType;

/**
 * Represents a date type in the form.
 *
 * A date type allows users to select a date from a calendar.
 */
final class DateType extends AbstractType
{
    /**
     * The pattern for the date type.
     *
     * @var string
     */
    public const PATTERN = '^(19\d{2}|20\d{2}|2100)-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'date';
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
    public function castValue(mixed $value): mixed
    {
        return new DateTimeImmutable($value);
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonSchema(): array
    {
        return [
            'type' => 'string',
            'format' => 'date',

            // ISO 8601.
            'maxLength' => 10,
            'minLength' => 10,
        ];
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
    public function isGuessable(): bool
    {
        return true;
    }
}
