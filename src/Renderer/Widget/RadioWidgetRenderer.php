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
use Derafu\Form\Contract\Schema\StringSchemaInterface;
use InvalidArgumentException;

/**
 * Renderer for radio widgets.
 */
final class RadioWidgetRenderer implements WidgetRendererInterface
{
    /**
     * Constructor.
     *
     * @param string $type The type of input to render.
     */
    public function __construct(private readonly string $type = 'radio')
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
                'The "renderer" option in RadioWidgetRenderer must be an '
                . 'instance of FormRendererInterface.'
            );
        }

        $property = $field->getProperty();
        $control = $field->getControl();

        // Determine if there are errors.
        $hasErrors = $options['has_errors'] ?? false;

        // Determine the CSS class of the widget.
        $widgetClass = 'form-check-input';
        if ($hasErrors) {
            $widgetClass .= ' is-invalid';
        }
        if (isset($options['widget_class'])) {
            $widgetClass .= ' ' . $options['widget_class'];
        }

        $name = $field->getName();
        $value = $options['value'] ?? null;

        // Build HTML attributes for the radio.
        $attrs = [
            'type' => $this->type,
            'id' => $name . '_field',
            'name' => $name,
            'class' => $widgetClass,
        ];

        // Add value if present.
        if ($value !== null) {
            $attrs['value'] = is_array($value)
                || is_object($value)
                ? json_encode($value)
                : $value
            ;
        }

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
            if ($property->getPattern() !== null) {
                $attrs['pattern'] = $property->getPattern();
            }
        }

        $controlOptions = $control->getOptions();
        if (!empty($controlOptions['placeholder'])) {
            $attrs['placeholder'] = $controlOptions['placeholder'];
        }

        if (isset($options['attr']) && is_array($options['attr'])) {
            $attrs = array_merge($attrs, $options['attr']);
        }

        // Special handling for radio buttons.
        $choices = [];
        if (
            method_exists($property, 'getEnum')
            && $property->getEnum() !== null
        ) {
            foreach ($property->getEnum() as $enumValue) {
                $choices[$enumValue] = $enumValue;
            }
        }
        if (isset($options['choices']) && is_array($options['choices'])) {
            $choices = $options['choices'];
        }

        $context = [
            'field' => $field,
            'options' => $options,
            'attrs' => $attrs,
            'has_errors' => $hasErrors,
            'choices' => $choices,
            'value' => $value,
            'name' => $name,
        ];

        return $formRenderer->getRenderer()->render(
            'form/widget/radio',
            $context
        );
    }
}
