<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Fixture;

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\Renderer\WidgetRendererInterface;

final class DomainWidgetRenderer implements WidgetRendererInterface
{
    public function render(FormFieldInterface $field, array $options = []): string
    {
        return '<input data-domain-widget name="' . htmlspecialchars($field->getProperty()->getName()) . '">';
    }
}
