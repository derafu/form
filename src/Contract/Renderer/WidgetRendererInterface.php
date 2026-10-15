<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Renderer;

use Derafu\Form\Contract\FormFieldInterface;

/**
 * Interface for widget renderers.
 *
 * Widget renderers are responsible for rendering form widgets like text inputs,
 * selects, checkboxes, etc.
 */
interface WidgetRendererInterface
{
    /**
     * Renders a form widget.
     *
     * @param FormFieldInterface $field The form field to render the widget for.
     * @param array<string, mixed> $options Additional rendering options.
     * @return string The rendered HTML.
     */
    public function render(FormFieldInterface $field, array $options = []): string;
}
