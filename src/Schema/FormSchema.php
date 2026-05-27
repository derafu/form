<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Schema;

use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Contract\Schema\FormSchemaInterface;
use Derafu\Form\Contract\Schema\PropertySchemaInterface;
use Derafu\Form\Factory\PropertySchemaFactory;

/**
 * Implementation of SchemaInterface.
 *
 * This class represents the root schema of a form and adds support for
 * definitions section, which can be used for schema references.
 */
final class FormSchema extends AbstractPropertySchema implements FormSchemaInterface
{
    use ObjectSchemaTrait;

    /**
     * The definitions section used for schema references.
     *
     * @var array<string,PropertySchemaInterface>
     */
    protected array $definitions = [];

    /**
     * {@inheritDoc}
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefinitions(array $definitions): static
    {
        $this->definitions = $definitions;

        return $this;
    }

    /**
     * Adds a definition to the schema.
     *
     * @param string $name The name of the definition.
     * @param PropertySchemaInterface $schema The schema to add as a definition.
     * @return self The current instance.
     */
    public function addDefinition(string $name, PropertySchemaInterface $schema): static
    {
        $this->definitions[$name] = $schema;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        // Convert properties to array.
        $propertiesArray = [];
        foreach ($this->properties as $name => $property) {
            $propertiesArray[$name] = $property->toArray();
        }

        if (!empty($propertiesArray)) {
            $array['properties'] = $propertiesArray;
        }

        if (!empty($this->required)) {
            $array['required'] = $this->required;
        }

        if ($this->dependentRequired !== null) {
            $array['dependentRequired'] = $this->dependentRequired;
        }

        if ($this->maxProperties !== null) {
            $array['maxProperties'] = $this->maxProperties;
        }

        if ($this->minProperties !== null) {
            $array['minProperties'] = $this->minProperties;
        }

        // Only include additionalProperties if it's not the default (true).
        if ($this->additionalProperties !== true) {
            $array['additionalProperties'] = $this->additionalProperties;
        }

        // Convert definitions to array.
        if (!empty($this->definitions)) {
            $definitionsArray = [];
            foreach ($this->definitions as $name => $definition) {
                $definitionsArray[$name] = $definition->toArray();
            }
            // Using $defs as per JSON Schema 2020-12.
            $array['$defs'] = $definitionsArray;
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $definition): static
    {
        $schema = new static($definition['name'] ?? '');

        // Set common properties.
        if (isset($definition['title'])) {
            $schema->setTitle($definition['title']);
        }

        if (isset($definition['description'])) {
            $schema->setDescription($definition['description']);
        }

        if (isset($definition['default'])) {
            $schema->setDefault($definition['default']);
        }

        if (isset($definition['deprecated'])) {
            $schema->setDeprecated($definition['deprecated']);
        }

        if (isset($definition['examples'])) {
            $schema->setExamples($definition['examples']);
        }

        if (isset($definition['readOnly'])) {
            $schema->setReadOnly($definition['readOnly']);
        }

        if (isset($definition['writeOnly'])) {
            $schema->setWriteOnly($definition['writeOnly']);
        }

        if (isset($definition['enum'])) {
            $schema->setEnum($definition['enum']);
        }

        if (isset($definition['const'])) {
            $schema->setConst($definition['const']);
        }

        if (isset($definition['allOf'])) {
            $schema->setAllOf($definition['allOf']);
        }

        if (isset($definition['anyOf'])) {
            $schema->setAnyOf($definition['anyOf']);
        }

        if (isset($definition['oneOf'])) {
            $schema->setOneOf($definition['oneOf']);
        }

        // Set object-specific options.

        if (isset($definition['required'])) {
            $schema->setRequired($definition['required']);
        }

        if (isset($definition['dependentRequired'])) {
            $schema->setDependentRequired($definition['dependentRequired']);
        }

        if (isset($definition['maxProperties'])) {
            $schema->setMaxProperties($definition['maxProperties']);
        }

        if (isset($definition['minProperties'])) {
            $schema->setMinProperties($definition['minProperties']);
        }

        if (isset($definition['additionalProperties'])) {
            $schema->setAdditionalProperties($definition['additionalProperties']);
        }

        // Set object-specific properties.
        if (isset($definition['properties']) && is_array($definition['properties'])) {
            foreach ($definition['properties'] as $propName => $propDefinition) {
                $propSchema = PropertySchemaFactory::create(
                    array_merge(['name' => $propName], $propDefinition)
                );
                $schema->addProperty($propSchema);
            }
        }

        // Set definitions.
        if (isset($definition['$defs']) && is_array($definition['$defs'])) {
            $definitions = [];
            foreach ($definition['$defs'] as $defName => $defDefinition) {
                $definitions[$defName] = PropertySchemaFactory::create(
                    array_merge(['name' => $defName], $defDefinition)
                );
            }
            $schema->setDefinitions($definitions);
        }

        // Handle legacy "definitions" field.
        if (isset($definition['definitions']) && is_array($definition['definitions'])) {
            $definitions = $schema->getDefinitions();
            foreach ($definition['definitions'] as $defName => $defDefinition) {
                $definitions[$defName] = PropertySchemaFactory::create(
                    array_merge(['name' => $defName], $defDefinition)
                );
            }
            $schema->setDefinitions($definitions);
        }

        return $schema;
    }
}
