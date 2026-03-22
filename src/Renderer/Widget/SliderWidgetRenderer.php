<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.org>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer\Widget;

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Contract\Renderer\WidgetRendererInterface;
use Derafu\Form\Contract\Schema\IntegerSchemaInterface;
use Derafu\Form\Contract\Schema\NumberSchemaInterface;
use InvalidArgumentException;

/**
 * Renderer for range slider input widgets.
 */
final class SliderWidgetRenderer implements WidgetRendererInterface
{
    /**
     * Constructor.
     *
     * @param string $type The type of input to render.
     */
    public function __construct(private readonly string $type = 'range')
    {
    }

    /**
     * {@inheritDoc}
     */
    public function render(
        FormFieldInterface $field,
        array $options = []
    ): string {
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRendererInterface) {
            throw new InvalidArgumentException(
                'The "renderer" option in SliderWidgetRenderer must be an '
                . 'instance of FormRendererInterface.'
            );
        }

        $property = $field->getProperty();
        $control = $field->getControl();

        // Determine if there are errors.
        $hasErrors = $options['has_errors'] ?? false;

        // Determine the CSS class of the widget.
        $widgetClass = 'form-slider';
        if ($hasErrors) {
            $widgetClass .= ' is-invalid';
        }
        if (isset($options['widget_class'])) {
            $widgetClass .= ' ' . $options['widget_class'];
        }

        $name = $field->getName();
        $value = $options['value'] ?? null;

        // Build HTML attributes for the input.
        $attrs = [
            'type' => $this->type,
            'id' => $name . '_field',
            'name' => $name,
            'class' => $widgetClass,
        ];

        // Set default min/max values
        $attrs['min'] = '0';
        $attrs['max'] = '100';
        $attrs['step'] = '1';

        // Handle numeric schema constraints
        if (
            $property instanceof NumberSchemaInterface
            || $property instanceof IntegerSchemaInterface
        ) {
            if ($property->getMinimum() !== null) {
                $attrs['min'] = (string)$property->getMinimum();
            }
            if ($property->getMaximum() !== null) {
                $attrs['max'] = (string)$property->getMaximum();
            }
            if (
                method_exists($property, 'getMultipleOf')
                && $property->getMultipleOf() !== null
            ) {
                $attrs['step'] = (string)$property->getMultipleOf();
            }
        }

        // Add value if present, otherwise set to middle of range
        if ($value !== null) {
            $attrs['value'] = is_array($value) || is_object($value)
                ? json_encode($value)
                : (string)$value;
        } else {
            // Default to middle value if no value set
            $min = (float)$attrs['min'];
            $max = (float)$attrs['max'];
            $defaultValue = round(($min + $max) / 2);
            $attrs['value'] = (string)$defaultValue;
        }

        if ($field->isRequired()) {
            $attrs['required'] = 'required';
        }

        // Control options
        $controlOptions = $control->getOptions();

        // Handle step from control options
        if (!empty($controlOptions['step'])) {
            $attrs['step'] = (string)$controlOptions['step'];
        }

        // Handle min/max from control options (override schema if present)
        if (!empty($controlOptions['min'])) {
            $attrs['min'] = (string)$controlOptions['min'];
        }
        if (!empty($controlOptions['max'])) {
            $attrs['max'] = (string)$controlOptions['max'];
        }

        // Merge additional attributes from options
        if (isset($options['attr']) && is_array($options['attr'])) {
            $attrs = array_merge($attrs, $options['attr']);
        }

        // Additional slider-specific options
        $sliderOptions = [
            'show_value' => $options['show_value'] ?? true,
            'unit' => $options['unit'] ?? '',
            'label' => $options['label'] ?? null,
        ];

        $context = [
            'field' => $field,
            'options' => array_merge($options, $sliderOptions),
            'attrs' => $attrs,
            'has_errors' => $hasErrors,
            'value' => $attrs['value'],
            'name' => $name,
            'min' => $attrs['min'],
            'max' => $attrs['max'],
            'step' => $attrs['step'],
        ];

        $template = 'form/widget/slider';

        return $formRenderer->getRenderer()->render($template, $context);
    }
}
