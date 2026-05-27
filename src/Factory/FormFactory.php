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

use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Type\TypeResolverInterface;
use Derafu\Form\Form;
use InvalidArgumentException;
use LogicException;

/**
 * Factory class for creating form instances with automatic schema generation.
 *
 * This factory provides methods to create forms from an array definition.
 *
 * It can automatically detecting types, formats, and validation rules based on
 * the provided values in the `data` index of the definition.
 */
final class FormFactory implements FormFactoryInterface
{
    /**
     * Constructor.
     *
     * @param TypeResolverInterface $typeResolver The type resolver.
     */
    public function __construct(private readonly TypeResolverInterface $typeResolver)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $definition): FormInterface
    {
        $definition = $this->normalizeDefinition($definition);

        return Form::fromArray($definition);
    }

    /**
     * Normalizes the form definition, ensuring all required components are
     * present.
     *
     * If `schema` or `uischema` are missing, they will be automatically
     * generated based on the provided data.
     *
     * @param array $definition The form definition to normalize.
     * @return array The normalized definition with schema and uischema.
     * @throws InvalidArgumentException If neither `schema` nor `data` are provided.
     */
    private function normalizeDefinition(array $definition): array
    {
        if (empty($definition['data'])) {
            $definition['data'] = null;
        }

        if (empty($definition['schema'])) {
            if ($definition['data'] === null) {
                throw new InvalidArgumentException(
                    'The form schema definition must be assigned if form data is not provided.'
                );
            }

            $definition['schema'] = $this->createSchemaDefinitionFromData(
                $definition['data']
            );
        }

        if (empty($definition['uischema'])) {
            $definition['uischema'] = $this->createUiSchemaDefinitionFromProperties(
                $definition['schema']['properties']
            );
        }

        if (is_array($definition['data'])) {
            foreach ($definition['data'] as $name => &$value) {
                if ($value !== null) {
                    continue;
                }
                if (($definition['schema']['properties'][$name]['type'] ?? 'string') === 'string') {
                    $value = '';
                }
            }
        }

        // Promote control.options.choices shorthands to schema oneOf.
        $this->applyChoicesShorthand($definition);

        return $definition;
    }

    /**
     * Creates a schema definition based on the provided data.
     *
     * Automatically detects types, formats, and validation rules based on the
     * values in the data.
     *
     * @param array $data The data to create a schema from
     * @return array The generated schema definition
     */
    private function createSchemaDefinitionFromData(array $data): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => [],
        ];

        foreach ($data as $name => $value) {
            $type = $this->typeResolver->guess($value);

            $property = $type->getJsonSchema();

            $schema['properties'][$name] = $property;
        }

        return $schema;
    }

    /**
     * Creates a UI schema definition based on the provided properties.
     *
     * @param array<string,array<string,mixed>> $properties The properties to
     * create a UI schema from.
     * @return array The generated UI schema definition.
     */
    private function createUiSchemaDefinitionFromProperties(
        array $properties
    ): array {
        $uischema = [
            'type' => 'VerticalLayout',
            'elements' => [],
        ];

        foreach ($properties as $name => $property) {
            $uischema['elements'][] = [
                'type' => 'Control',
                'label' => $property['title']
                    ?? implode(' ', array_map('ucfirst', explode('_', $name)))
                ,
                'scope' => '#/properties/' . $name,
            ];
        }

        return $uischema;
    }

    /**
     * Promotes every `control.options.choices` dict found in the raw definition
     * to a proper `oneOf` (const+title pairs) on the corresponding schema
     * property, then removes the `choices` key so it never reaches any object.
     *
     * This is a write-time convenience: authors may write a compact dict
     * instead of the verbose oneOf/const/title structure. The dict is promoted
     * here, during normalization, and is never persisted to any object.
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
     * ## Error conditions (LogicException)
     *
     *   - Nested scope (`#/properties/a/properties/b`) — not supported in v1.
     *   - Property referenced by scope not found in schema.
     *   - Property type is `integer` or `number`.
     *   - Property already has a `oneOf` defined (ambiguity).
     *   - Array property's `items` already has a `oneOf` defined (ambiguity).
     *
     * @param array $definition The raw form definition array (modified in place).
     * @throws LogicException On any of the error conditions listed above.
     */
    private function applyChoicesShorthand(array &$definition): void
    {
        if (empty($definition['uischema']['elements'])
            || empty($definition['schema']['properties'])
        ) {
            return;
        }

        $this->applyChoicesFromElements(
            $definition['uischema']['elements'],
            $definition['schema']['properties']
        );
    }

    /**
     * Recursively walks the uischema elements array and applies the choices
     * shorthand for every Control that carries `options.choices`.
     *
     * @param array $elements Uischema elements (modified in place).
     * @param array $properties Schema properties (modified in place).
     */
    private function applyChoicesFromElements(
        array &$elements,
        array &$properties
    ): void {
        foreach ($elements as &$element) {
            // Recurse into layout elements (VerticalLayout, HorizontalLayout,
            // Group, Category, Categorization…) before processing controls so
            // nested controls are also handled.
            if (isset($element['elements']) && is_array($element['elements'])) {
                $this->applyChoicesFromElements($element['elements'], $properties);
            }

            // Only process Control elements that carry options.choices.
            if (($element['type'] ?? null) !== 'Control') {
                continue;
            }

            if (!isset($element['options']['choices'])) {
                continue;
            }

            $choices = $element['options']['choices'];
            $scope   = $element['scope'] ?? '';

            // Reject nested scopes — not supported in v1.
            if (!preg_match('/^#\/properties\/([^\/]+)$/', $scope, $m)) {
                throw new LogicException(sprintf(
                    'choices shorthand does not support nested scopes: "%s". '
                    . 'Use oneOf directly in the schema for nested properties.',
                    $scope
                ));
            }

            $name = $m[1];

            // The property must exist in the schema.
            if (!array_key_exists($name, $properties)) {
                throw new LogicException(sprintf(
                    'choices shorthand: property "%s" not found in schema '
                    . '(referenced by scope "%s").',
                    $name,
                    $scope
                ));
            }

            $prop = &$properties[$name];
            $type = $prop['type'] ?? 'string';

            // Integer and number selects are not supported via shorthand.
            // The const values would silently become strings (JSON object keys
            // are always strings), producing wrong validation. Use oneOf
            // directly in the schema instead.
            if ($type === 'integer' || $type === 'number') {
                throw new LogicException(sprintf(
                    'choices shorthand is not supported for type "%s" (property "%s"). '
                    . 'Use oneOf directly in the schema to preserve numeric const values.',
                    $type,
                    $name
                ));
            }

            // Build the oneOf array from the choices dict.
            $oneOf = [];
            foreach ($choices as $const => $title) {
                $oneOf[] = ['const' => $const, 'title' => (string) $title];
            }

            if ($type === 'array') {
                // For array types, choices describe each item's allowed values.
                if (!isset($prop['items'])) {
                    $prop['items'] = [];
                }

                if (isset($prop['items']['oneOf'])) {
                    throw new LogicException(sprintf(
                        'choices shorthand: property "%s" items already has oneOf defined. '
                        . 'Remove either the choices shorthand or the items.oneOf.',
                        $name
                    ));
                }

                $prop['items']['oneOf'] = $oneOf;
            } else {
                // String (and any other scalar type).
                if (isset($prop['oneOf'])) {
                    throw new LogicException(sprintf(
                        'choices shorthand: property "%s" already has oneOf defined. '
                        . 'Remove either the choices shorthand or the schema oneOf.',
                        $name
                    ));
                }

                $prop['oneOf'] = $oneOf;
            }

            // Remove choices from the control options — it must not reach
            // any Control object.
            unset($element['options']['choices']);

            // Drop the options key entirely when it becomes empty.
            if (isset($element['options']) && $element['options'] === []) {
                unset($element['options']);
            }
        }
    }
}
