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
 * Represents a week type in the form.
 *
 * A week type allows users to select a week from a calendar.
 */
final class WeekType extends AbstractType
{
    /**
     * The pattern for the week type.
     *
     * @var string
     */
    public const PATTERN = '/^[0-9]{4}-W[0-9]{2}$/';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'week';
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
    public function getJsonSchema(): array
    {
        return [
            'type' => 'string',
            'format' => 'week',
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
