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
 * Represents a month type in the form.
 *
 * A month type allows users to select a month from a calendar.
 */
final class MonthType extends AbstractType
{
    /**
     * The pattern for the month type.
     *
     * @var string
     */
    public const PATTERN = '^[1-9]\d{3}-(0[1-9]|1[0-2])$';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'month';
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        return [
            'type' => 'string',
            'format' => 'month',

            // ISO 8601.
            'maxLength' => 7,
            'minLength' => 7,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
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
