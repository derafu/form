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
 *
 * The options of an element come from several places, and the one that is
 * closest to the element decides. From the one that decides last to the one that
 * decides first:
 *
 *   1. The defaults of the renderer.
 *   2. The options of the form (its `options` section).
 *   3. The options that are passed when rendering (`$options`), that go down to
 *      all the elements.
 *   4. The options of the element (its `options` in the UI schema).
 *
 * A layout can also give options to each one of its children by their index
 * (`elements_options`, in its own options or in the ones that are passed), that
 * count as options that are passed to that child.
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
