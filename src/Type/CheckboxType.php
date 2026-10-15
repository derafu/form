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
 * Represents a checkbox type in the form.
 *
 * A checkbox type allows users to select active or inactive. The main
 * difference with the boolean type is that the boolean type is rendered as a
 * toggle button, while the checkbox type is rendered as a checkbox. Also, the
 * checkbox type is more flexible than the boolean type, because it can be
 * rendered as a single checkbox or as a group of checkboxes.
 */
final class CheckboxType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'checkbox';
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        return [
            'type' => 'boolean',
        ];
    }
}
