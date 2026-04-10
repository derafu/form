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
 * Renderer for select input widgets.
 *
 * This class handles the rendering of dropdown select fields, supporting both
 * single and multiple selections.
 */
final class SelectWidgetRenderer implements WidgetRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(FormFieldInterface $field, array $options = []): string
    {
        // Get the main form renderer from options.
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRendererInterface) {
            throw new InvalidArgumentException(
                'The "renderer" option in SelectWidgetRenderer must be an instance of FormRendererInterface.'
            );
        }

        $property = $field->getProperty();
        $control = $field->getControl();

        // Determine if there are errors.
        $hasErrors = !$field->isValid();

        // Check if multiple selection is enabled.
        $isMultiple = $property->getType() === 'array';

        // Prepare CSS classes.
        $widgetClass = 'form-select';
        if ($hasErrors) {
            $widgetClass .= ' is-invalid';
        }
        if (isset($options['widget_class'])) {
            $widgetClass .= ' ' . $options['widget_class'];
        }

        // Get field name.
        $name = $field->getName();
        if ($isMultiple) {
            $name .= '[]';
        }

        // Get field value (can be a single value or an array for multiple select).
        $value = $options['value'] ?? $field->getData() ?? null;

        // Get options for the select.
        $choices = [];

        // If the property has an enum, use it for choices.
        if (method_exists($property, 'getEnum') && $property->getEnum() !== null) {
            $enum = $property->getEnum();
            $isSequentialEnum = array_is_list($enum);
            foreach ($enum as $enumKey => $enumValue) {
                $choices[$isSequentialEnum ? $enumValue : $enumKey] = $enumValue;
            }
        }

        // If the property has oneOf, use it for choices.
        // if (method_exists($property, 'getOneOf') && $property->getOneOf() !== null) {
        //     $oneOf = $property->getOneOf();
        //     foreach ($oneOf as $option) {
        //         if (method_exists($option, 'getConst') && $option->getConst() !== null &&
        //             method_exists($option, 'getTitle') && $option->getTitle() !== null) {
        //             $choices[$option->getConst()] = $option->getTitle();
        //         }
        //     }
        // }

        // Allow overriding choices from options.
        if (isset($options['choices']) && is_array($options['choices'])) {
            $choices = $options['choices'];
        }

        // Build HTML attributes for the select.
        $attrs = [
            'id' => $name . '_field',
            'name' => $name,
            'class' => $widgetClass,
        ];

        // Add multiple attribute if necessary.
        if ($isMultiple) {
            $attrs['multiple'] = 'multiple';
        }

        // Add validation attributes from property
        if ($field->isRequired()) {
            $attrs['required'] = 'required';
        }

        // Add size attribute if specified.
        if (isset($options['size']) && is_numeric($options['size'])) {
            $attrs['size'] = (string)$options['size'];
        }

        // Add placeholder from control options if available.
        $controlOptions = $control->getOptions();
        if (isset($controlOptions['placeholder'])) {
            $attrs['data-placeholder'] = $controlOptions['placeholder'];
        }

        // Apply custom attributes from options.
        if (isset($options['attr']) && is_array($options['attr'])) {
            $attrs = array_merge($attrs, $options['attr']);
        }

        // Prepare context for template.
        $context = [
            'field' => $field,
            'options' => $options,
            'attrs' => $attrs,
            'has_errors' => $hasErrors,
            'choices' => $choices,
            'value' => $value,
            'is_multiple' => $isMultiple,
        ];

        // Render the template.
        return $formRenderer->getRenderer()->render('form/widget/select', $context);
    }
}
