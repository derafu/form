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
 * Represents a radio type in the form.
 *
 * A radio type allows users to select one option from a list.
 */
final class RadioType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'radio';
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonSchema(): array
    {
        return [
            'type' => 'string',
            'enum' => [],
        ];
    }
}
