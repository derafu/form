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
use Derafu\Form\Contract\Schema\ObjectSchemaInterface;
use Derafu\Form\Factory\PropertySchemaFactory;

/**
 * Implementation of ObjectSchemaInterface.
 *
 * This class represents an object schema and provides object-specific
 * validations along with property management.
 */
final class ObjectSchema extends AbstractPropertySchema implements ObjectSchemaInterface
{
    use ObjectSchemaTrait;

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
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

        // Only include additionalProperties if the schema has it.
        if ($this->additionalProperties !== null) {
            $array['additionalProperties'] = $this->additionalProperties;
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $definition
     */
    public static function fromArray(array $definition): static
    {
        $def = PropertyDefinition::fromArray($definition);
        $schema = new static($def->name);

        // Set common properties.
        if ($def->title !== null) {
            $schema->setTitle($def->title);
        }

        if ($def->description !== null) {
            $schema->setDescription($def->description);
        }

        if ($def->default !== null) {
            $schema->setDefault($def->default);
        }

        if ($def->deprecated !== null) {
            $schema->setDeprecated($def->deprecated);
        }

        if ($def->examples !== null) {
            $schema->setExamples($def->examples);
        }

        if ($def->readOnly !== null) {
            $schema->setReadOnly($def->readOnly);
        }

        if ($def->writeOnly !== null) {
            $schema->setWriteOnly($def->writeOnly);
        }

        if ($def->enum !== null) {
            $schema->setEnum($def->enum);
        }

        if ($def->const !== null) {
            $schema->setConst($def->const);
        }

        if ($def->allOf !== null) {
            $schema->setAllOf($def->allOf);
        }

        if ($def->anyOf !== null) {
            $schema->setAnyOf($def->anyOf);
        }

        if ($def->oneOf !== null) {
            $schema->setOneOf($def->oneOf);
        }

        // Set object-specific options.

        if ($def->required !== null) {
            $schema->setRequired($def->required);
        }

        if ($def->dependentRequired !== null) {
            $schema->setDependentRequired($def->dependentRequired);
        }

        if ($def->maxProperties !== null) {
            $schema->setMaxProperties($def->maxProperties);
        }

        if ($def->minProperties !== null) {
            $schema->setMinProperties($def->minProperties);
        }

        if ($def->additionalProperties !== null) {
            $schema->setAdditionalProperties($def->additionalProperties);
        }

        // Set object-specific properties.
        if ($def->properties !== null) {
            foreach ($def->properties as $propName => $propDefinition) {
                $propSchema = PropertySchemaFactory::create(
                    array_merge(['name' => $propName], $propDefinition)
                );
                $schema->addProperty($propSchema);
            }
        }

        return $schema;
    }
}
