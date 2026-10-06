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
use Derafu\Form\Contract\UiSchema\HorizontalLayoutInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Renderer\FormRenderer;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Renderer for horizontal layout elements.
 */
final class HorizontalLayoutRenderer implements ElementRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(
        UiSchemaElementInterface $element,
        FormInterface $form,
        array $options = []
    ): string {
        // Check if the element is a HorizontalLayoutInterface.
        if (!$element instanceof HorizontalLayoutInterface) {
            throw new InvalidArgumentException([
                'Element must be an instance of {expected} in HorizontalLayoutRenderer, {given} given.',
                'expected' => HorizontalLayoutInterface::class,
                'given' => get_class($element),
            ]);
        }

        // Get the main form renderer from options.
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRenderer) {
            throw new InvalidArgumentException(
                'The "renderer" option in HorizontalLayoutRenderer must be an instance of FormRenderer.'
            );
        }

        // Get elements of the horizontal layout.
        $uiElements = $element->getElements();

        // Render each element.
        $elementsHtml = [];
        $elementsOptions = [];

        foreach ($uiElements as $uiElement) {
            // Check if this element has specific options for the horizontal layout.
            $elementOptions = $options;

            // If the element has options for column sizing, extract them.
            $uiElementOptions = $uiElement->getOptions();
            if (isset($uiElementOptions['col_size'])) {
                $elementOptions['col_size'] = $uiElementOptions['col_size'];
            }

            // Store the options for this element.
            $elementsOptions[] = $elementOptions;

            // Render the element.
            $elementsHtml[] = $formRenderer->renderElement(
                $uiElement,
                $form,
                $options
            );
        }

        // Prepare context for template.
        $context = [
            'element' => $element,
            'form' => $form,
            'options' => $options,
            'elements_html' => $elementsHtml,
            'elements_options' => $elementsOptions,
        ];

        // Render the template.
        return $formRenderer->getRenderer()->render(
            'form/element/horizontal_layout',
            $context
        );
    }
}
