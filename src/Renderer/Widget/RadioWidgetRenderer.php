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
use Derafu\Form\Renderer\Support\RequiredField;
use Derafu\Support\ArrayReader;
use Derafu\Support\ArrayType;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

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
     *
     * @param array<string, mixed> $options
     */
    public function render(
        FormFieldInterface $field,
        array $options = []
    ): string {
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRendererInterface) {
            throw new InvalidArgumentException(
                'The "renderer" option in RadioWidgetRenderer must be an instance of FormRendererInterface.'
            );
        }

        $reader = ArrayReader::from($options);
        $customAttrs = $reader->mapOf('attr', ArrayType::scalar()) ?? [];

        $property = $field->getProperty();
        $control = $field->getControl();

        // Determine if there are errors.
        $hasErrors = $options['has_errors'] ?? false;

        // Determine the CSS class of the widget.
        $widgetClass = 'form-check-input';
        if ($hasErrors) {
            $widgetClass .= ' is-invalid';
        }
        $extraClass = $reader->string('widget_class');
        if ($extraClass !== null) {
            $widgetClass .= ' ' . $extraClass;
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

        $controlOptions = $control->getOptions();
        if (!empty($controlOptions['placeholder'])) {
            $attrs['placeholder'] = $controlOptions['placeholder'];
        }

        $attrs = array_merge($attrs, $customAttrs);

        // Build choices from the property schema (oneOf or enum).
        $choices = $property->getChoices() ?? [];

        $context = [
            'field' => $field,
            'options' => $options,
            'attrs' => $attrs,
            'has_errors' => $hasErrors,
            'choices' => $choices,
            'value' => $value,
            'name' => $name,
            'required' => RequiredField::isAnnounced($field),
            'native_required' => RequiredField::isNative($field, $options),
        ];

        return $formRenderer->getRenderer()->render(
            'form/widget/radio',
            $context
        );
    }
}
