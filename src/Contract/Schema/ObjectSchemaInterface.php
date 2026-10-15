<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Schema;

/**
 * Represents a Schema definition for a form.
 *
 * The Schema defines the data structure, types, and validation rules for form
 * fields.
 */
interface ObjectSchemaInterface extends PropertySchemaInterface
{
    /**
     * Gets all properties defined in the schema.
     *
     * @return array<string,PropertySchemaInterface> An associative array of
     * property definitions.
     */
    public function getProperties(): array;

    /**
     * Checks if a specific property exists in the schema.
     *
     * @param string $name The property name to check.
     * @return bool True if the property exists, false otherwise.
     */
    public function hasProperty(string $name): bool;

    /**
     * Gets the definition for a specific property.
     *
     * @param string $name The property name.
     * @return PropertySchemaInterface|null The property definition or `null` if
     * not found.
     */
    public function getProperty(string $name): ?PropertySchemaInterface;

    /**
     * Adds a property definition to the schema.
     *
     * @param PropertySchemaInterface $property The property definition.
     * @return static The current instance.
     */
    public function addProperty(PropertySchemaInterface $property): static;

    /**
     * Gets the list of properties that depend on others in order to be used.
     *
     * @return array<string,string[]>
     * @link https://www.learnjsonschema.com/2020-12/validation/dependentrequired/
     */
    public function getDependentRequired(): ?array;

    /**
     * Sets the list of properties that depend on others in order to be used.
     *
     * @param array<string,string[]> $dependentRequired
     * @return static The current instance.
     */
    public function setDependentRequired(array $dependentRequired): static;

    /**
     * Gets the maximum quantity of properties.
     *
     * @return integer|null
     * @link https://www.learnjsonschema.com/2020-12/validation/maxproperties/
     */
    public function getMaxProperties(): ?int;

    /**
     * Sets the maximum quantity of properties.
     *
     * @param int $maxProperties
     * @return static The current instance.
     */
    public function setMaxProperties(int $maxProperties): static;

    /**
     * Gets the minimum quantity of properties.
     *
     * @return integer|null
     * @link https://www.learnjsonschema.com/2020-12/validation/minproperties/
     */
    public function getMinProperties(): ?int;

    /**
     * Sets the minimum quantity of properties.
     *
     * @param int $minProperties
     * @return static The current instance.
     */
    public function setMinProperties(int $minProperties): static;

    /**
     * Gets the list of required properties.
     *
     * @return array<string> List of property names that are required.
     * @link https://www.learnjsonschema.com/2020-12/validation/required/
     */
    public function getRequired(): array;

    /**
     * Sets the list of required properties.
     *
     * @param array<string> $properties
     * @return static The current instance.
     */
    public function setRequired(array $properties): static;

    /**
     * Gets the `additionalProperties` value, which determines if properties not
     * defined in the schema are allowed.
     *
     * @return bool|array<string, mixed> A boolean indicating if additional properties are
     * allowed, or an array defining their schema.
     */
    public function getAdditionalProperties(): bool|array;

    /**
     * Sets the `additionalProperties` value, which determines if properties not
     * defined in the schema are allowed.
     *
     * @param bool|array<string, mixed> $additionalProperties
     * @return static The current instance.
     */
    public function setAdditionalProperties(
        bool|array $additionalProperties = true
    ): static;
}
