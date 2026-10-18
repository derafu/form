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
use Derafu\Form\Contract\Schema\IntegerSchemaInterface;
use Derafu\Form\Contract\Schema\NumberSchemaInterface;
use Derafu\Form\Contract\Schema\StringSchemaInterface;
use Derafu\Form\Renderer\Support\RequiredField;
use Derafu\Support\ArrayReader;
use Derafu\Support\ArrayType;
use Derafu\Support\Caster;
use Derafu\Support\File;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Renderer for input widgets.
 */
final class InputWidgetRenderer implements WidgetRendererInterface
{
    /**
     * Constructor.
     *
     * @param string $type The type of input to render.
     */
    public function __construct(private readonly string $type = 'text')
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
        // Get the main form renderer from options.
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRendererInterface) {
            throw new InvalidArgumentException(
                'The "renderer" option in InputWidgetRenderer must be an instance of FormRendererInterface.'
            );
        }

        $reader = ArrayReader::from($options);
        $customAttrs = $reader->mapOf('attr', ArrayType::scalar()) ?? [];

        $property = $field->getProperty();
        $control = $field->getControl();

        // Determine if there are errors.
        $hasErrors = !$field->isValid();

        // Determine the type of input to render.
        $type = isset($customAttrs['type'])
            ? (string) $customAttrs['type']
            : ($reader->string('type') ?? $this->type);

        // Prepare CSS classes.
        $widgetClass = 'form-control';

        if ($type === 'checkbox') {
            $widgetClass = 'form-check-input';
        } elseif ($type === 'range') {
            $widgetClass .= ' form-range';
        }
        if ($hasErrors) {
            $widgetClass .= ' is-invalid';
        }
        $extraClass = $reader->string('widget_class');
        if ($extraClass !== null) {
            $widgetClass .= ' ' . $extraClass;
        }

        // Get field name.
        $name = $field->getName();

        // Get field value.
        $value = $options['value'] ?? $field->getData() ?? null;

        if ($type === 'percent' || $type === 'float' || $type === 'money') {
            $type = 'number';
        } elseif ($type === 'image') {
            // 'image' is a UI control type for image-file uploads.
            // HTML's <input type="image"> is a submit button — not what we want.
            $type = 'file';
        }

        // Build HTML attributes for the input.
        $attrs = [
            'type' => $type,
            'id' => $name . '_field',
            'name' => $name,
            'class' => $widgetClass,
        ];

        // Set value if provided.
        if ($value !== null) {
            if ($type === 'checkbox') {
                if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                    $attrs['checked'] = 'checked';
                }
            } elseif ($type === 'datetime-local') {
                $date = new \DateTime(Caster::requireString($value));
                $attrs['value'] = $date->format('Y-m-d\TH:i:s');
            } else {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                $attrs['value'] = $value;
            }
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

        if ($property instanceof IntegerSchemaInterface || $property instanceof NumberSchemaInterface) {
            if ($property->getMinimum() !== null) {
                $attrs['min'] = (string)$property->getMinimum();
            }

            if ($property->getMaximum() !== null) {
                $attrs['max'] = (string)$property->getMaximum();
            }

            if ($property->getMultipleOf() !== null) {
                $attrs['step'] = (string)$property->getMultipleOf();
            }
        }

        // Add placeholder from control options if available.
        $controlOptions = $control->getOptions();
        if (!empty($controlOptions['placeholder'])) {
            $attrs['placeholder'] = $controlOptions['placeholder'];
        }

        // Handle multiple file inputs: append [] to name and add multiple attr.
        if ($type === 'file' && !empty($controlOptions['multiple'])) {
            $attrs['name'] .= '[]';
            $attrs['multiple'] = 'multiple';
        }

        // Image upload controls restrict accepted MIME types to images.
        if ($type === 'file' && $control->getControlType() === 'image') {
            $attrs['accept'] = 'image/*';
        }

        // The files that the browser offers (`accept`): extensions or MIME types,
        // like `.xml` or `.p12,.pfx`. It replaces the one of the image controls.
        if ($type === 'file' && !empty($controlOptions['accept'])) {
            $attrs['accept'] = $controlOptions['accept'];
        }

        // The sizes (in bytes) that the server asks of the files, for a script
        // of the page that wants to check them before the file is sent.
        if ($type === 'file') {
            foreach (['min_size' => 'data-min-size', 'max_size' => 'data-max-size'] as $option => $attribute) {
                $size = $controlOptions[$option] ?? null;
                $bytes = is_int($size) || is_string($size) ? File::parseSize((string) $size) : null;
                if ($bytes !== null) {
                    $attrs[$attribute] = (string) $bytes;
                }
            }
        }

        // Handle readonly from schema property or control options.
        if ($property->isReadOnly() || !empty($controlOptions['readonly'])) {
            $attrs['readonly'] = 'readonly';
        }

        // What says that the field is required.
        if ($type !== 'hidden') {
            $attrs = array_merge($attrs, RequiredField::attributes($field, $options));
        }

        // Apply custom attributes from options.
        $attrs = array_merge($attrs, $customAttrs);

        if (
            $type === 'checkbox'
            && isset($attrs['checked'])
            && $attrs['checked'] === 'checked'
        ) {
            $attrs['checked'] = 'checked';
        }

        // Prepare context for template.
        $context = [
            'field' => $field,
            'options' => $options,
            'attrs' => $attrs,
            'has_errors' => $hasErrors,
        ];

        // Render the template.
        return $formRenderer->getRenderer()->render(
            'form/widget/input',
            $context
        );
    }
}
