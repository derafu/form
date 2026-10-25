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
use Derafu\Value\ValueCaster;

/**
 * Represents a color type in the form.
 *
 * A color type allows users to select a color.
 */
final class ColorType extends AbstractType
{
    /**
     * The pattern for the color type.
     *
     * @var string
     */
    public const PATTERN = '^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'color';
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
    public function castValue(mixed $value): mixed
    {
        return strtolower(ValueCaster::requireString($value));
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
            'format' => 'color',
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
