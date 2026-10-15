<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer\Support;

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\Schema\BooleanSchemaInterface;
use Derafu\Form\Contract\Schema\PropertySchemaInterface;

/**
 * What the renderers say about a field that is required.
 *
 * As in JSON Schema, `required` only says that the key must be in the data:
 * for a boolean it is not a reason to ask for a value. A boolean that must be
 * checked (to accept the terms, for example) says it with `const: true` or
 * `enum: [true]` in its property.
 */
final class RequiredField
{
    /**
     * Whether the property is a boolean that must be true.
     */
    public static function mustBeChecked(PropertySchemaInterface $property): bool
    {
        return $property instanceof BooleanSchemaInterface
            && ($property->getConst() === true || $property->getEnum() === [true]);
    }

    /**
     * Whether the control of the field is announced as required (`aria-required`).
     *
     * A boolean is not: it is announced by the native `required` when it must
     * be checked.
     */
    public static function isAnnounced(FormFieldInterface $field): bool
    {
        return $field->isRequired() && !$field->getProperty() instanceof BooleanSchemaInterface;
    }

    /**
     * Whether the control of the field asks for a value in the browser
     * (`required`), which the form decides with the option `native_required`.
     *
     * @param array<string, mixed> $options The options of the render.
     */
    public static function isNative(FormFieldInterface $field, array $options): bool
    {
        return self::isAnnounced($field)
            && ($options['native_required'] ?? $field->isNativeRequired()) === true;
    }

    /**
     * The attributes of a control that says that its field is required: the
     * native `required` when the form asks for it, `aria-required` if not. A
     * boolean that must be checked always has the native one.
     *
     * @param array<string, mixed> $options The options of the render.
     * @return array<string, string>
     */
    public static function attributes(FormFieldInterface $field, array $options): array
    {
        if (self::mustBeChecked($field->getProperty()) || self::isNative($field, $options)) {
            return ['required' => 'required'];
        }

        return self::isAnnounced($field) ? ['aria-required' => 'true'] : [];
    }

    /**
     * Whether the label of the field shows the marker of a required field.
     */
    public static function isMarked(FormFieldInterface $field): bool
    {
        return self::isAnnounced($field) || self::mustBeChecked($field->getProperty());
    }
}
