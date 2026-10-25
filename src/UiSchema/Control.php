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

use Derafu\Array\ArrayReader;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use Derafu\Value\ValueType;

/**
 * Represents a control in a UI Schema.
 *
 * Controls are used to render form properties.
 */
final class Control extends AbstractUiSchemaElement implements ControlInterface
{
    /**
     * The scope of the property that the control is for.
     */
    private readonly string $scope;

    /**
     * Creates a control from its definition.
     *
     * @param array<string, mixed> $definition
     * @throws InvalidArgumentException If the scope is missing, or a known key
     * has a wrong type.
     */
    public function __construct(array $definition)
    {
        parent::__construct($definition);

        $this->scope = ArrayReader::fromArray($definition)->require('scope', ValueType::string());
    }

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
        return $this->scope;
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

        throw new InvalidArgumentException([
            'Invalid scope format: {scope}. Expected format: #/properties/path/to/property',
            'scope' => $this->getScope(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getPropertyName(): string
    {
        $name = ArrayReader::fromArray($this->definition->options)->getString('name');
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
        return $this->definition->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getControlType(): ?string
    {
        return ArrayReader::fromArray($this->definition->options)->getString('type');
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->definition->options;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->definition->toArray();
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $definition
     */
    public static function fromArray(array $definition): static
    {
        return new static($definition);
    }
}
