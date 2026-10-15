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
 * Represents a time type in the form.
 *
 * A time type allows users to select a time from a clock.
 */
final class TimeType extends AbstractType
{
    /**
     * The pattern for the time type.
     *
     * @var string
     */
    public const PATTERN = '^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'time';
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
            'format' => 'time',

            // "23:59:59" (8), "00:00:00" (8).
            'maxLength' => 14,
            'minLength' => 8,
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
