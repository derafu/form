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

use Derafu\Form\Contract\FormFieldInterface;

/**
 * Interface for widget factories.
 */
interface WidgetFactoryInterface
{
    /**
     * Creates a widget instance for the given form field.
     *
     * @param FormFieldInterface $field The form field to create the widget for.
     * @return WidgetInterface The created widget.
     */
    public function create(FormFieldInterface $field): WidgetInterface;
}
