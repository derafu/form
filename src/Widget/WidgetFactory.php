<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Widget;

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\Schema\ArraySchemaInterface;
use Derafu\Form\Contract\Schema\StringSchemaInterface;
use Derafu\Form\Contract\Widget\WidgetFactoryInterface;
use Derafu\Form\Contract\Widget\WidgetInterface;

/**
 * Factory class for creating widget instances.
 */
final class WidgetFactory implements WidgetFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(FormFieldInterface $field): WidgetInterface
    {
        $widgetType = $this->determineWidgetType($field);

        return new Widget($widgetType);
    }

    /**
     * Determines the appropriate widget type based on the form field.
     *
     * @param FormFieldInterface $field The form field.
     * @return string The widget type (e.g., 'text', 'email', 'select', etc.).
     */
    private function determineWidgetType(FormFieldInterface $field): string
    {
        $property = $field->getProperty();
        $control = $field->getControl();

        // Check if the control has a specific widget type defined in options.
        $options = $control->getOptions();
        if (isset($options['widget'])) {
            return $options['widget'];
        }

        // Determine widget type based on property type and format.
        $type = $property->getType();
        $format = $property instanceof StringSchemaInterface
            ? $property->getFormat()
            : null
        ;

        // Map property type and format to widget type.
        if ($property instanceof StringSchemaInterface) {
            if ($format === 'email') {
                return 'email';
            } elseif ($format === 'date') {
                return 'date';
            } elseif ($format === 'date-time') {
                return 'datetime';
            } elseif ($format === 'week') {
                return 'week';
            } elseif ($format === 'month') {
                return 'month';
            } elseif ($format === 'password') {
                return 'password';
            } elseif ($format === 'time') {
                return 'time';
            } elseif ($format === 'color') {
                return 'color';
            } elseif (
                isset($options['type'])
                && $options['type'] === 'radio'
            ) {
                return 'radio';
            } elseif ($format === 'uri') {
                return 'url';
            } elseif ($property->getEnum() !== null) {
                return 'select';
            } elseif (
                !empty($options['multi'])
                || (
                    $property->getMaxLength() !== null
                    && $property->getMaxLength() > 255
                )
                || (
                    isset($options['type'])
                    && $options['type'] === 'textarea'
                )
            ) {
                return 'textarea';
            }
            return 'text';
        } elseif (
            $type === 'number'
            || $type === 'integer'
            || $type === 'float'
            || (isset($options['type']) && $options['type'] === 'float')
            || $type === 'percent'
        ) {
            // Check if slider widget is explicitly requested.
            if (isset($options['type']) && $options['type'] === 'slider') {
                return 'slider';
            }
            // Check if range widget is explicitly requested (alias for slider).
            if (isset($options['type']) && $options['type'] === 'range') {
                return 'range';
            }
            return 'number';
        } elseif ($type === 'boolean') {
            return 'checkbox';
        } elseif ($type === 'array') {
            $schema = $property->toArray();
            if (isset($schema['items']['enum'])) {
                return 'checkboxes';
            }
            return 'collection';
        } elseif ($property instanceof ArraySchemaInterface) {
            return 'collection';
        } elseif ($type === 'object') {
            return 'compound';
        }

        // Check if the control has a specific format defined in options.
        if (isset($options['format'])) {
            if ($options['format'] === 'checkbox') {
                // For boolean fields, use single checkbox
                // For array fields with enum, use multiple checkboxes
                if ($property->getType() === 'boolean') {
                    return 'checkbox';
                } else {
                    return 'checkboxes';
                }
            }
        }

        // Default to text.
        return 'text';
    }
}
