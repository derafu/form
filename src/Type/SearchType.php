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
 * Represents a search type in the form.
 *
 * A search type allows users to enter a search query.
 */
final class SearchType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'search';
    }

    /**
     * {@inheritDoc}
     */
    public function validateValue(mixed $value): bool
    {
        return is_string($value);
    }
}
