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

use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;

/**
 * Interface for element renderers.
 *
 * Element renderers are responsible for rendering UI schema elements like
 * controls, layouts, groups, etc.
 */
interface ElementRendererInterface
{
    /**
     * Renders a UI schema element.
     *
     * @param UiSchemaElementInterface $element The element to render.
     * @param FormInterface $form The form containing the element.
     * @param array<string, mixed> $options Additional rendering options.
     * @return string The rendered HTML.
     */
    public function render(
        UiSchemaElementInterface $element,
        FormInterface $form,
        array $options = []
    ): string;
}
