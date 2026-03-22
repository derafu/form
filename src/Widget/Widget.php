<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Widget;

use Derafu\Form\Contract\Widget\WidgetInterface;

/**
 * Implementation of the WidgetInterface.
 */
final class Widget implements WidgetInterface
{
    public function __construct(private readonly string $type)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Checks if the widget supports floating labels.
     *
     * @return bool True if the widget supports floating labels, false otherwise.
     */
    public function supportsFloatingLabel(): bool
    {
        return !in_array($this->type, ['collection', 'checkboxes', 'radio', 'checkbox']);
    }
}
