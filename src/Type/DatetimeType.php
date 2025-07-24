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
 * Represents a datetime type in the form.
 *
 * A datetime type allows users to select a date and time from a calendar.
 */
final class DatetimeType extends AbstractType
{
    /**
     * The pattern for the datetime type.
     *
     * @var string
     */
    public const PATTERN = '/^(19\d{2}|20\d{2}|2100)-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])T([01]\d|2[0-3]):([0-5]\d):([0-5]\d)(?:\.\d+)?(Z|[+-](?:0\d|1[0-4]):[0-5]\d)?$/';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'datetime';
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

        return preg_match(self::PATTERN, $value) === 1;
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
            'format' => 'date-time',

            // ISO 8601.
            'maxLength' => 35,
            'minLength' => 16,
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
