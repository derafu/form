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
use Derafu\Form\Schema\Pattern;

/**
 * Represents a regex type in the form.
 *
 * A regex type allows users to enter a regex pattern.
 */
final class RegexType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'regex';
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
            'format' => 'regex',
        ];
    }

    /**
     * {@inheritDoc}
     *
     * The value is a pattern, written like the ones of JSON Schema (without
     * delimiters): it is valid if it can be compiled.
     */
    public function validateValue(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        // PHP raises a warning for a pattern that can not be compiled; the
        // result is what says it.
        set_error_handler(static fn (): bool => true);

        try {
            return preg_match(Pattern::toRegex($value), '') !== false;
        } finally {
            restore_error_handler();
        }
    }
}
