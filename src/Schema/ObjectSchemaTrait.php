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

use Derafu\Form\Contract\Schema\ObjectSchemaInterface;
use Derafu\Form\Contract\Schema\PropertySchemaInterface;

/**
 * Trait for ObjectSchema.
 *
 * This trait provides common functionality for object schemas.
 */
trait ObjectSchemaTrait
{
    /**
     * The properties of the object.
     *
     * @var array<string,PropertySchemaInterface>
     */
    protected array $properties = [];

    /**
     * The properties that depend on others.
     *
     * @var array<string,string[]>|null
     */
    protected ?array $dependentRequired = null;

    /**
     * The maximum number of properties.
     *
     * @var int|null
     */
    protected ?int $maxProperties = null;

    /**
     * The minimum number of properties.
     *
     * @var int|null
     */
    protected ?int $minProperties = null;

    /**
     * The required properties.
     *
     * @var array<string>
     */
    protected array $required = [];

    /**
     * Whether additional properties are allowed, as the schema says. A form is
     * closed unless it says otherwise: without the keyword (`null`) it is
     * `false`.
     *
     * @var bool|array<string, mixed>|null
     */
    protected bool|array|null $additionalProperties = null;

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'object';
    }

    /**
     * {@inheritDoc}
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * {@inheritDoc}
     */
    public function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty(string $name): ?PropertySchemaInterface
    {
        if (preg_match('~^#/properties/(.+)$~', $name, $matches)) {
            $segments = explode('/properties/', $matches[1]);
        } else {
            $segments = explode('.', $name);
        }

        // The first segment is the root property name.
        $rootPropertyName = $segments[0];
        $property = $this->properties[$rootPropertyName] ?? null;

        if (!$property) {
            return null;
        }

        // Navigate through the remaining segments.
        for ($i = 1; $i < count($segments); $i++) {
            if (!$property instanceof ObjectSchemaInterface) {
                return null; // Cannot navigate deeper.
            }

            $property = $property->getProperty($segments[$i]);

            if (!$property) {
                return null;
            }
        }

        return $property;
    }

    /**
     * {@inheritDoc}
     */
    public function addProperty(PropertySchemaInterface $property): static
    {
        $this->properties[$property->getName()] = $property;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDependentRequired(): ?array
    {
        return $this->dependentRequired;
    }

    /**
     * {@inheritDoc}
     */
    public function setDependentRequired(array $dependentRequired): static
    {
        $this->dependentRequired = $dependentRequired;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxProperties(): ?int
    {
        return $this->maxProperties;
    }

    /**
     * {@inheritDoc}
     */
    public function setMaxProperties(int $maxProperties): static
    {
        $this->maxProperties = $maxProperties;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMinProperties(): ?int
    {
        return $this->minProperties;
    }

    /**
     * {@inheritDoc}
     */
    public function setMinProperties(int $minProperties): static
    {
        $this->minProperties = $minProperties;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequired(): array
    {
        return $this->required;
    }

    /**
     * {@inheritDoc}
     */
    public function setRequired(array $properties): static
    {
        $this->required = $properties;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return bool|array<string, mixed> mixed>
     */
    public function getAdditionalProperties(): bool|array
    {
        return $this->additionalProperties ?? false;
    }

    /**
     * {@inheritDoc}
     *
     * @param bool|array<string, mixed> $additionalProperties
     */
    public function setAdditionalProperties(bool|array $additionalProperties = true): static
    {
        $this->additionalProperties = $additionalProperties;

        return $this;
    }
}
