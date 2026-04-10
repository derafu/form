<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer\Widget;

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Contract\Renderer\WidgetRendererInterface;
use InvalidArgumentException;

/**
 * Renderer for multiple checkboxes.
 *
 * This class handles the rendering of multiple checkbox groups from array
 * fields.
 */
final class CheckboxWidgetRenderer implements WidgetRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(
        FormFieldInterface $field,
        array $options = []
    ): string {
        // Get the main form renderer from options.
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRendererInterface) {
            throw new InvalidArgumentException(
                'The "renderer" option in CheckboxWidgetRenderer must be an '
                . 'instance of FormRendererInterface.'
            );
        }

        $property = $field->getProperty();
        $control = $field->getControl();

        // Determine if there are errors.
        $hasErrors = !$field->isValid();

        // Prepare CSS classes.
        $widgetClass = 'form-check-input';
        if ($hasErrors) {
            $widgetClass .= ' is-invalid';
        }
        if (isset($options['widget_class'])) {
            $widgetClass .= ' ' . $options['widget_class'];
        }

        // Get field name.
        $name = $field->getName();

        // Get field value.
        $value = $options['value'] ?? $field->getData();

        // For multiple checkboxes, ensure value is an array.
        if (!is_array($value)) {
            // Handle case where single value is passed.
            $value = $value !== null && $value !== '' ? [$value] : [];
        }

        $choices = [];

        // First try array items enum.
        $schema = $property->toArray();
        if (
            isset($schema['type'])
            && $schema['type'] === 'array'
            && isset($schema['items']['enum'])
        ) {
            $isSequentialEnum = array_is_list($schema['items']['enum']);
            foreach ($schema['items']['enum'] as $enumKey => $enumValue) {
                $label = ucfirst(str_replace(['_', '-'], ' ', $enumValue));
                $choices[$isSequentialEnum ? $enumValue : $enumKey] = $label;
            }
        }

        // Try direct enum.
        if (empty($choices) && $property->getEnum() !== null) {
            $directEnum = $property->getEnum();
            $isSequentialEnum = array_is_list($directEnum);
            foreach ($directEnum as $enumKey => $enumValue) {
                $choices[$isSequentialEnum ? $enumValue : $enumKey] = $enumValue;
            }
        }

        // Override with explicit choices from options.
        if (isset($options['choices']) && is_array($options['choices'])) {
            $choices = $options['choices'];
        }

        if (empty($choices)) {
            throw new InvalidArgumentException(
                'CheckboxWidgetRenderer: No choices found for multiple '
                . 'checkbox field "' . $name . '"'
            );
        }

        // Prepare context for template.
        $context = [
            'field' => $field,
            'options' => $options,
            'choices' => $choices,
            'value' => $value,
            'name' => $name,
            'widget_class' => $widgetClass,
            'has_errors' => $hasErrors,
        ];

        // Render the template.
        return $formRenderer->getRenderer()->render(
            'form/widget/checkbox',
            $context
        );
    }
}
