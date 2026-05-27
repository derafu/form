<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\UiSchema;

use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use InvalidArgumentException;

/**
 * Represents a control in a UI Schema.
 *
 * Controls are used to render form properties.
 */
final class Control extends AbstractUiSchemaElement implements ControlInterface
{
    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'Control';
    }

    /**
     * {@inheritDoc}
     */
    public function getScope(): string
    {
        return $this->definition['scope'];
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyPath(): string
    {
        // Extract property name from scope (assumed format:
        // "#/properties/propertyName").
        if (preg_match('~^#/properties/(.+)$~', $this->getScope(), $matches)) {
            return $matches[1];
        }

        throw new InvalidArgumentException(sprintf(
            'Invalid scope format: %s. Expected format: #/properties/path/to/property',
            $this->getScope()
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyName(): string
    {
        $name = $this->definition['options']['name'] ?? null;
        if ($name) {
            return $name;
        }

        $name = str_replace('/', '.', $this->getPropertyPath());

        return str_replace('properties.', '', $name);
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): ?string
    {
        return $this->definition['label'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getControlType(): ?string
    {
        return $this->definition['options']['type'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(): array
    {
        return $this->definition['options'] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->definition;
    }

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $definition): static
    {
        return new static($definition);
    }
}
