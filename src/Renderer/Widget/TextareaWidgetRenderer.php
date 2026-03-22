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
use Derafu\Form\Contract\Schema\StringSchemaInterface;
use InvalidArgumentException;

/**
 * Renderer for textarea input widgets.
 *
 * This class handles the rendering of multiline text areas for longer text content.
 */
final class TextareaWidgetRenderer implements WidgetRendererInterface
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
                'The "renderer" option in TextareaWidgetRenderer must be an '
                . 'instance of FormRendererInterface.'
            );
        }

        $property = $field->getProperty();
        $control = $field->getControl();

        // Determine if there are errors.
        $hasErrors = !$field->isValid();

        // Prepare CSS classes.
        $widgetClass = 'form-control';
        if ($hasErrors) {
            $widgetClass .= ' is-invalid';
        }
        if (isset($options['widget_class'])) {
            $widgetClass .= ' ' . $options['widget_class'];
        }

        // Get field name.
        $name = $field->getName();

        // Get field value
        $value = $options['value'] ?? $field->getData() ?? '';

        // Build HTML attributes for the textarea.
        $attrs = [
            'id' => $name . '_field',
            'name' => $name,
            'class' => $widgetClass,
        ];

        // Default rows and cols.
        $rows = $options['rows'] ?? 5;
        $cols = $options['cols'] ?? 40;

        $attrs['rows'] = (string)$rows;
        $attrs['cols'] = (string)$cols;

        // Add validation attributes from property.
        if ($field->isRequired()) {
            $attrs['required'] = 'required';
        }

        if ($property instanceof StringSchemaInterface) {
            if ($property->getMinLength() !== null) {
                $attrs['minlength'] = (string)$property->getMinLength();
            }

            if ($property->getMaxLength() !== null) {
                $attrs['maxlength'] = (string)$property->getMaxLength();
            }
        }

        // Add placeholder from control options if available.
        $controlOptions = $control->getOptions();
        if (isset($controlOptions['placeholder'])) {
            $attrs['placeholder'] = $controlOptions['placeholder'];
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
            'value' => $value,
            'has_errors' => $hasErrors,
        ];

        // Render the template.
        return $formRenderer->getRenderer()->render(
            'form/widget/textarea',
            $context
        );
    }
}
