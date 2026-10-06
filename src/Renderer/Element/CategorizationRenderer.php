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
use Derafu\Form\Contract\UiSchema\CategorizationInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Renderer\FormRenderer;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Renderer for categorization elements (tabs).
 */
final class CategorizationRenderer implements ElementRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(
        UiSchemaElementInterface $element,
        FormInterface $form,
        array $options = []
    ): string {
        // Check if the element is a CategorizationInterface.
        if (!$element instanceof CategorizationInterface) {
            throw new InvalidArgumentException([
                'Element must be an instance of {expected} in CategorizationRenderer, {given} given.',
                'expected' => CategorizationInterface::class,
                'given' => get_class($element),
            ]);
        }

        // Get the main form renderer from options.
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRenderer) {
            throw new InvalidArgumentException(
                'The "renderer" option in CategorizationRenderer must be an instance of FormRenderer.'
            );
        }

        // Get categories of the categorization.
        $categories = $element->getCategories();

        // Render each category.
        $categoriesHtml = [];

        foreach ($categories as $category) {
            // Create category options by merging parent options with category-specific options.
            $categoryOptions = array_merge($options, $category->getOptions());

            // Get elements of the category.
            $categoryElements = $category->getElements();

            // Render the elements of this category.
            $elementsHtml = [];
            foreach ($categoryElements as $categoryElement) {
                $elementsHtml[] = $formRenderer->renderElement(
                    $categoryElement,
                    $form,
                    $categoryOptions
                );
            }

            // Create context for category template.
            $categoryContext = [
                'element' => $category,
                'form' => $form,
                'options' => $categoryOptions,
                'elements_html' => $elementsHtml,
            ];

            // Render the category template.
            $categoriesHtml[] = $formRenderer->getRenderer()->render(
                'form/element/category',
                $categoryContext
            );
        }

        // Get categorization options.
        $categorizationOptions = $element->getOptions();

        // Merge options.
        $finalOptions = array_merge($options, $categorizationOptions);

        // Prepare context for template.
        $context = [
            'element' => $element,
            'form' => $form,
            'options' => $finalOptions,
            'categories_html' => $categoriesHtml,
        ];

        // Render the template.
        return $formRenderer->getRenderer()->render(
            'form/element/categorization',
            $context
        );
    }
}
