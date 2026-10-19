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
use Derafu\Form\Renderer\Support\RequiredField;
use Derafu\Support\ArrayReader;
use Derafu\Support\ArrayType;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Renderer for textarea input widgets.
 *
 * This class handles the rendering of multiline text areas for longer text content.
 */
final class TextareaWidgetRenderer implements WidgetRendererInterface
{
    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $options
     */
    public function render(FormFieldInterface $field, array $options = []): string
    {
        // Get the main form renderer from options.
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRendererInterface) {
            throw new InvalidArgumentException(
                'The "renderer" option in TextareaWidgetRenderer must be an instance of FormRendererInterface.'
            );
        }

        $reader = ArrayReader::from($options);
        $customAttrs = $reader->mapOf('attr', ArrayType::scalar()) ?? [];

        $property = $field->getProperty();
        $control = $field->getControl();

        // Determine if there are errors.
        $hasErrors = !$field->isValid();

        // Prepare CSS classes.
        $widgetClass = 'form-control';
        if ($hasErrors) {
            $widgetClass .= ' is-invalid';
        }
        $extraClass = $reader->string('widget_class');
        if ($extraClass !== null) {
            $widgetClass .= ' ' . $extraClass;
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
        $rows = $reader->int('rows') ?? 5;
        $cols = $reader->int('cols') ?? 40;

        $attrs['rows'] = (string)$rows;
        $attrs['cols'] = (string)$cols;

        // Bootstrap fixes the height of a control with a floating label to one
        // line (`height` and `min-height`) and ignores `rows`, so the height
        // is set from the rows: each row has the line height of the control
        // (1.25rem), plus its padding (1.625rem above and 0.625rem below) and
        // its border. A `style` in the `attr` option replaces it.
        if (!empty($options['floating_labels'])) {
            $attrs['style'] = sprintf(
                'height: calc(%d * 1.25rem + 2.25rem + 2px)',
                max(1, $rows)
            );
        }

        // Add validation attributes from property.
        $attrs = array_merge($attrs, RequiredField::attributes($field, $options));

        if ($property instanceof StringSchemaInterface) {
            if ($property->getMinLength() !== null) {
                $attrs['minlength'] = (string)$property->getMinLength();
            }

            if ($property->getMaxLength() !== null) {
                $attrs['maxlength'] = (string)$property->getMaxLength();
            }

            if ($property->getPattern() !== null) {
                $attrs['pattern'] = $property->getPattern();
            }
        }

        // Add placeholder from control options if available.
        $controlOptions = $control->getOptions();
        if (isset($controlOptions['placeholder'])) {
            $attrs['placeholder'] = $controlOptions['placeholder'];
        }

        // Apply custom attributes from options.
        $attrs = array_merge($attrs, $customAttrs);

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
