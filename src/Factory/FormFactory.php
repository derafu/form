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

use Derafu\Array\ArrayReader;
use Derafu\Form\Contract\Factory\FormFactoryInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Type\TypeResolverInterface;
use Derafu\Form\Form;
use Derafu\Form\FormDefinition;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use Derafu\Value\ValueType;

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
     * @param ChoicesShorthand $choicesShorthand Promotes the choices of the
     * controls to the schema.
     */
    public function __construct(
        private readonly TypeResolverInterface $typeResolver,
        private readonly ChoicesShorthand $choicesShorthand = new ChoicesShorthand()
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $definition
     * @throws InvalidArgumentException If a key of the first level of the
     * definition has a wrong type, or there is neither `schema` nor `data`.
     */
    public function create(array $definition): FormInterface
    {
        $normalized = $this->normalizeDefinition(FormDefinition::fromArray($definition));

        return Form::fromArray($normalized->toArray());
    }

    /**
     * Normalizes the form definition, ensuring all required components are
     * present.
     *
     * If `schema` or `uischema` are missing, they will be automatically
     * generated based on the provided data. The `choices` shorthands of the
     * controls are promoted to the schema.
     *
     * @param FormDefinition $definition The form definition to normalize.
     * @return FormDefinition The normalized definition with schema and uischema.
     * @throws InvalidArgumentException If neither `schema` nor `data` are provided.
     */
    private function normalizeDefinition(FormDefinition $definition): FormDefinition
    {
        $data = $definition->data === [] ? null : $definition->data;
        $schema = $definition->schema;

        if ($schema === []) {
            if ($data === null) {
                throw new InvalidArgumentException(
                    'The form schema definition must be assigned if form data is not provided.'
                );
            }

            $schema = $this->createSchemaDefinitionFromData($data);
        }

        $properties = ArrayReader::fromArray($schema)->getMapOf('properties', ValueType::map()) ?? [];

        $uischema = $definition->uischema;
        if ($uischema === []) {
            $uischema = $this->createUiSchemaDefinitionFromProperties($properties);
        }

        if ($data !== null) {
            foreach ($data as $name => $value) {
                if ($value === null && (($properties[$name]['type'] ?? 'string') === 'string')) {
                    $data[$name] = '';
                }
            }
        }

        // Promote control.options.choices shorthands to schema oneOf.
        [$schema, $uischema] = $this->choicesShorthand->apply($schema, $uischema);

        return $definition->withSchema($schema)->withUiSchema($uischema)->withData($data);
    }

    /**
     * Creates a schema definition based on the provided data.
     *
     * Automatically detects types, formats, and validation rules based on the
     * values in the data.
     *
     * @param array<string, mixed> $data The data to create a schema from
     * @return array<string, mixed> The generated schema definition
     */
    private function createSchemaDefinitionFromData(array $data): array
    {
        $properties = [];

        foreach ($data as $name => $value) {
            $properties[$name] = $this->typeResolver->guess($value)->getJsonSchema();
        }

        return [
            'type' => 'object',
            'properties' => $properties,
            'required' => [],
        ];
    }

    /**
     * Creates a UI schema definition based on the provided properties.
     *
     * A schema without properties has no controls: the UI schema that is
     * created has no elements.
     *
     * @param array<string,array<string,mixed>> $properties The properties to
     * create a UI schema from.
     * @return array<string, mixed> The generated UI schema definition.
     */
    private function createUiSchemaDefinitionFromProperties(array $properties): array
    {
        $elements = [];

        foreach ($properties as $name => $property) {
            $elements[] = [
                'type' => 'Control',
                'label' => ArrayReader::fromArray($property)->getString('title')
                    ?? implode(' ', array_map('ucfirst', explode('_', (string) $name))),
                'scope' => '#/properties/' . $name,
            ];
        }

        return [
            'type' => 'VerticalLayout',
            'elements' => $elements,
        ];
    }
}
