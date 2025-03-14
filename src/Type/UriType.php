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
 * Represents a URI type in the form.
 *
 * A URI type allows users to enter a URI.
 */
final class UriType extends AbstractType
{
    /**
     * The pattern for the date type.
     *
     * @var string
     */
    public const PATTERN = '/^(?:[a-zA-Z][a-zA-Z0-9+.-]*):(?:\/\/)?(?:[\w.-]+(?:\:[\w.-]*)?@)?(?:[\w.-]+)(?:\:\d+)?(?:\/[\w\/._-]*)?(?:\?\S*)?(?:#\S*)?$/';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'uri';
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
            'format' => 'uri',

            // RFC 3986.
            'maxLength' => 2048,
            'minLength' => 2,
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
