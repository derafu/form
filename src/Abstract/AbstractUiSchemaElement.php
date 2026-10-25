<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Abstract;

use Derafu\Array\ArrayReader;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaRuleInterface;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Form\UiSchema\UiSchemaRule;
use Derafu\Support\JsonSerializer;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Abstract base class for UI Schema elements.
 *
 * This class provides a common implementation for UI Schema elements, including
 * type handling and JSON serialization.
 */
abstract class AbstractUiSchemaElement implements UiSchemaElementInterface
{
    /**
     * Cached generated id for instances without an explicit options['id'].
     *
     * @var string|null
     */
    private ?string $generatedId = null;

    /**
     * The definition of the element.
     */
    protected readonly UiSchemaElementDefinition $definition;

    /**
     * Creates a new UI Element from its definition.
     *
     * @param array<string, mixed> $definition
     * @throws InvalidArgumentException If a known key has a wrong type.
     */
    public function __construct(array $definition)
    {
        $this->definition = UiSchemaElementDefinition::fromArray($definition);
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        $id = ArrayReader::fromArray($this->definition->options)->getString('id');
        if ($id !== null) {
            return $id;
        }

        return $this->generatedId
            ??= uniqid(strtolower($this->getType()) . '-');
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
     */
    public function getRule(): ?UiSchemaRuleInterface
    {
        $rule = $this->definition->rule;

        return $rule !== null ? UiSchemaRule::fromArray($rule) : null;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function toJson(): string
    {
        return JsonSerializer::serialize($this->jsonSerialize());
    }
}
