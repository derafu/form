<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Factory;

use Derafu\Support\ArrayReader;
use Derafu\Support\ArrayType;
use Derafu\Translation\Exception\Core\TranslatableLogicException as LogicException;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Promotes the `options.choices` of the controls to a `oneOf` of the schema.
 *
 * Every `control.options.choices` dict found in the definition is promoted to a
 * proper `oneOf` (const+title pairs) on the corresponding schema property, and
 * the `choices` key is removed so it never reaches any object.
 *
 * This is a write-time convenience: authors may write a compact dict instead of
 * the verbose oneOf/const/title structure. The dict is promoted when the form is
 * created, and is never persisted to any object.
 *
 * ## Behaviour by property type
 *
 *   - `string`          → oneOf injected directly on the property.
 *   - `array`           → oneOf injected on `property.items` (each item
 *                         must be one of the listed values — checkboxes /
 *                         multi-select pattern).
 *   - `integer`/`number`→ NOT supported. Use oneOf directly in the schema.
 *                         Throws LogicException if attempted.
 *
 * ## Error conditions
 *
 *   - LogicException:
 *       - Nested scope (`#/properties/a/properties/b`) — not supported in v1.
 *       - Property referenced by scope not found in schema.
 *       - Property type is `integer` or `number`.
 *       - Property already has a `oneOf` defined (ambiguity).
 *       - Array property's `items` already has a `oneOf` defined (ambiguity).
 *   - InvalidArgumentException:
 *       - `choices` is not an array, or one of its values is not a string, a
 *         number or a boolean.
 *
 * The schema and the UI schema are not modified: the new ones are returned.
 */
final class ChoicesShorthand
{
    /**
     * The scope of a property that is directly in the schema.
     */
    private const SCOPE = '/^#\/properties\/([^\/]+)$/';

    /**
     * Promotes the choices of the controls to the properties of the schema.
     *
     * @param array<string, mixed> $schema The schema of the form.
     * @param array<string, mixed> $uischema The UI schema of the form.
     * @return array{array<string, mixed>, array<string, mixed>} The schema and
     * the UI schema, in this order.
     * @throws LogicException On any of the conditions of the class.
     * @throws InvalidArgumentException If `choices` is not what it must be.
     */
    public function apply(array $schema, array $uischema): array
    {
        $elements = ArrayReader::from($uischema)->listOf('elements', ArrayType::map());
        $properties = ArrayReader::from($schema)->mapOf('properties', ArrayType::map());

        if (empty($elements) || empty($properties)) {
            return [$schema, $uischema];
        }

        [$elements, $properties] = $this->walk($elements, $properties);

        $schema['properties'] = $properties;
        $uischema['elements'] = $elements;

        return [$schema, $uischema];
    }

    /**
     * Walks the elements, promoting the choices of every control.
     *
     * @param list<array<string, mixed>> $elements
     * @param array<string, array<string, mixed>> $properties
     * @return array{list<array<string, mixed>>, array<string, array<string, mixed>>}
     */
    private function walk(array $elements, array $properties): array
    {
        foreach ($elements as $index => $element) {
            [$element, $properties] = $this->walkElement($element, $properties);
            $elements[$index] = $element;
        }

        return [$elements, $properties];
    }

    /**
     * @param array<string, mixed> $element
     * @param array<string, array<string, mixed>> $properties
     * @return array{array<string, mixed>, array<string, array<string, mixed>>}
     */
    private function walkElement(array $element, array $properties): array
    {
        $reader = ArrayReader::from($element);

        // Recurse into layout elements (VerticalLayout, HorizontalLayout,
        // Group, Category, Categorization…) before processing controls so
        // nested controls are also handled.
        $children = $reader->listOf('elements', ArrayType::map());
        if ($children !== null) {
            [$children, $properties] = $this->walk($children, $properties);
            $element['elements'] = $children;
        }

        // Only Control elements carry options.choices.
        if (($element['type'] ?? null) !== 'Control') {
            return [$element, $properties];
        }

        $scope = $reader->string('scope') ?? '';
        $options = $reader->map('options') ?? [];

        // Recurse into the detail elements of array Controls, using the array
        // property's items.properties as the nested property scope.
        [$options, $properties] = $this->walkDetail($options, $scope, $properties);

        if ($options !== []) {
            $element['options'] = $options;
        }

        if (!isset($options['choices'])) {
            return [$element, $properties];
        }

        $choices = ArrayReader::from($options)->get('choices', self::choicesType());

        // Reject nested scopes — not supported in v1.
        if (!preg_match(self::SCOPE, $scope, $m)) {
            throw new LogicException([
                'choices shorthand does not support nested scopes: "{scope}". Use oneOf directly in the schema for nested properties.',
                'scope' => $scope,
            ]);
        }

        $name = $m[1];

        // The property must exist in the schema.
        if (!array_key_exists($name, $properties)) {
            throw new LogicException([
                'choices shorthand: property "{name}" not found in schema (referenced by scope "{scope}").',
                'name' => $name,
                'scope' => $scope,
            ]);
        }

        $property = $properties[$name];
        $type = $property['type'] ?? 'string';

        // Integer and number selects are not supported via shorthand. The const
        // values would silently become strings (JSON object keys are always
        // strings), producing wrong validation. Use oneOf directly in the schema
        // instead.
        if ($type === 'integer' || $type === 'number') {
            throw new LogicException([
                'choices shorthand is not supported for type "{type}" (property "{name}"). Use oneOf directly in the schema to preserve numeric const values.',
                'type' => $type,
                'name' => $name,
            ]);
        }

        // Build the oneOf array from the choices dict.
        $oneOf = [];
        foreach ($choices ?? [] as $const => $title) {
            $oneOf[] = ['const' => $const, 'title' => (string) $title];
        }

        if ($type === 'array') {
            // For array types, choices describe each item's allowed values.
            $items = ArrayReader::from($property)->map('items') ?? [];

            if (isset($items['oneOf'])) {
                throw new LogicException([
                    'choices shorthand: property "{name}" items already has oneOf defined. Remove either the choices shorthand or the items.oneOf.',
                    'name' => $name,
                ]);
            }

            $items['oneOf'] = $oneOf;
            $property['items'] = $items;
        } else {
            // String (and any other scalar type).
            if (isset($property['oneOf'])) {
                throw new LogicException([
                    'choices shorthand: property "{name}" already has oneOf defined. Remove either the choices shorthand or the schema oneOf.',
                    'name' => $name,
                ]);
            }

            $property['oneOf'] = $oneOf;
        }

        $properties[$name] = $property;

        // Remove choices from the control options — it must not reach any
        // Control object — and drop the options key entirely when it becomes
        // empty.
        unset($options['choices']);
        if ($options === []) {
            unset($element['options']);
        } else {
            $element['options'] = $options;
        }

        return [$element, $properties];
    }

    /**
     * Walks the elements of the detail of a control, with the properties of the
     * items of its array property.
     *
     * @param array<string, mixed> $options The options of the control.
     * @param string $scope The scope of the control.
     * @param array<string, array<string, mixed>> $properties
     * @return array{array<string, mixed>, array<string, array<string, mixed>>}
     *     The options and the properties.
     */
    private function walkDetail(array $options, string $scope, array $properties): array
    {
        $detail = ArrayReader::from($options)->map('detail');
        $elements = $detail === null ? null : ArrayReader::from($detail)->listOf('elements', ArrayType::map());

        if ($detail === null || $elements === null || !preg_match(self::SCOPE, $scope, $m)) {
            return [$options, $properties];
        }

        $name = $m[1];
        $items = ArrayReader::from($properties[$name] ?? [])->map('items');
        $itemProperties = $items === null
            ? null
            : ArrayReader::from($items)->mapOf('properties', ArrayType::map());

        if ($items === null || $itemProperties === null) {
            return [$options, $properties];
        }

        [$elements, $itemProperties] = $this->walk($elements, $itemProperties);

        $detail['elements'] = $elements;
        $options['detail'] = $detail;
        $items['properties'] = $itemProperties;
        $properties[$name]['items'] = $items;

        return [$options, $properties];
    }

    /**
     * The type of `choices`: an array whose values are text, numbers or
     * booleans (what is written as the title of each choice).
     *
     * @return ArrayType<array<array-key, bool|float|int|string>>
     */
    private static function choicesType(): ArrayType
    {
        return new ArrayType(
            'array<bool|float|int|string>',
            static function (mixed $value, string $path): array {
                if (!is_array($value)) {
                    throw ArrayType::mismatch($path, 'array<bool|float|int|string>', $value);
                }

                foreach ($value as $key => $title) {
                    if (!is_scalar($title)) {
                        throw ArrayType::mismatch(ArrayType::path($path, $key), 'bool|float|int|string', $title);
                    }
                }

                return $value;
            }
        );
    }
}
