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

use Derafu\Array\ArrayReader;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Renderer\ElementRendererInterface;
use Derafu\Form\Contract\UiSchema\HorizontalLayoutInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Renderer\FormRenderer;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use Derafu\Value\ValueType;

/**
 * Renderer for horizontal layout elements.
 */
final class HorizontalLayoutRenderer implements ElementRendererInterface
{
    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $options
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

        // The options of the layout: its own decide before the ones that are
        // passed, and the children do not get them.
        $layoutOptions = array_merge($options, $element->getOptions());

        // The options of each child, by its index (`elements_options`): they are
        // passed to the child, and the size and the class of its column are
        // taken from them, before what the child asks for.
        $elementsOptions = ArrayReader::fromArray($layoutOptions)->getListOf('elements_options', ValueType::map()) ?? [];

        // Render each element.
        $elementsHtml = [];
        $columns = [];

        foreach ($uiElements as $index => $uiElement) {
            // The size of the column that the element asks for.
            $column = [];
            $colSize = ArrayReader::fromArray($uiElement->getOptions())->get('col_size', ValueType::scalar());
            if ($colSize !== null) {
                $column['col_size'] = $colSize;
            }
            $elementOptions = $elementsOptions[$index] ?? [];
            $columns[] = array_merge($column, $elementOptions);

            // Render the element.
            $elementsHtml[] = $formRenderer->renderElement(
                $uiElement,
                $form,
                array_merge($options, $elementOptions)
            );
        }

        // Prepare context for template.
        $context = [
            'element' => $element,
            'form' => $form,
            'options' => $layoutOptions,
            'elements_html' => $elementsHtml,
            'columns' => $columns,
        ];

        // Render the template.
        return $formRenderer->getRenderer()->render(
            'form/element/horizontal_layout',
            $context
        );
    }
}
