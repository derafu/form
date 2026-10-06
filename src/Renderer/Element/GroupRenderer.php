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
use Derafu\Form\Contract\UiSchema\GroupInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Renderer\FormRenderer;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Renderer for group elements.
 */
final class GroupRenderer implements ElementRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(
        UiSchemaElementInterface $element,
        FormInterface $form,
        array $options = []
    ): string {
        // Check if the element is a GroupInterface.
        if (!$element instanceof GroupInterface) {
            throw new InvalidArgumentException([
                'Element must be an instance of {expected} in GroupRenderer, {given} given.',
                'expected' => GroupInterface::class,
                'given' => get_class($element),
            ]);
        }

        // Get the main form renderer from options.
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRenderer) {
            throw new InvalidArgumentException(
                'The "renderer" in GroupRenderer must be an instance of FormRenderer.'
            );
        }

        // Get the child elements.
        $childElements = $element->getElements();
        $elementsHTML = [];

        // Render each child element
        foreach ($childElements as $childElement) {
            // Check if there are element-specific options.
            $elementOptions = $options;

            // Get options for this element if available (using element type and index).
            $elementIndex = array_search($childElement, $childElements, true);
            if (isset($options['elements_options'][$elementIndex])) {
                $elementOptions = array_merge(
                    $options,
                    $options['elements_options'][$elementIndex]
                );
            }

            $elementsHTML[] = $formRenderer->renderElement(
                $childElement,
                $form,
                $elementOptions
            );
        }

        // Prepare context for template.
        $context = [
            'element' => $element,
            'form' => $form,
            'options' => $options,
            'elements_html' => $elementsHTML,
        ];

        // Render the template.
        return $formRenderer->getRenderer()->render(
            'form/element/group',
            $context
        );
    }
}
