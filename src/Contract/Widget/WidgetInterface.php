<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Widget;

/**
 * Interface for widgets.
 */
interface WidgetInterface
{
    /**
     * Gets the type of the widget.
     *
     * @return string The type of the widget.
     */
    public function getType(): string;

    /**
     * Checks if the widget supports floating labels.
     *
     * @return bool True if the widget supports floating labels, false otherwise.
     */
    public function supportsFloatingLabel(): bool;
}
