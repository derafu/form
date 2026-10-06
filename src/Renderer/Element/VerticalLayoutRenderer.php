<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer\Element;

use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Renderer\ElementRendererInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Contract\UiSchema\VerticalLayoutInterface;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Renderer for vertical layout elements.
 */
final class VerticalLayoutRenderer implements ElementRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(
        UiSchemaElementInterface $element,
        FormInterface $form,
        array $options = []
    ): string {
        // Check if the element is a VerticalLayoutInterface.
        if (!$element instanceof VerticalLayoutInterface) {
            throw new InvalidArgumentException([
                'Element must be an instance of {expected} in VerticalLayoutRenderer, {given} given.',
                'expected' => VerticalLayoutInterface::class,
                'given' => get_class($element),
            ]);
        }

        // Get the main form renderer from options.
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRendererInterface) {
            throw new InvalidArgumentException(
                'The "renderer" option in VerticalLayoutRenderer must be an instance of FormRendererInterface.'
            );
        }

        // Prepare context for template.
        $context = [
            'element' => $element,
            'form' => $form,
            'options' => $options,
            // Render child elements.
            'elements_html' => $formRenderer->renderElements(
                $element->getElements(),
                $form,
                $options
            ),
        ];

        // Render the template.
        return $formRenderer->getRenderer()->render(
            'form/element/vertical_layout',
            $context
        );
    }
}
