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

        return $schema;
    }
}
