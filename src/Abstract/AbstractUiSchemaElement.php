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

use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaRuleInterface;
use Derafu\Form\UiSchema\UiSchemaRule;
use Derafu\Support\JsonSerializer;

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
     * Creates a new Control UI Element from its definition.
     *
     * @param array $definition
     */
    public function __construct(protected readonly array $definition)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        if (isset($this->definition['options']['id'])) {
            return $this->definition['options']['id'];
        }

        return $this->generatedId
            ??= uniqid(strtolower($this->getType()) . '-');
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
    public function getRule(): ?UiSchemaRuleInterface
    {
        $rule = $this->definition['rule'] ?? null;

        return $rule !== null ? UiSchemaRule::fromArray($rule) : null;
    }

    /**
     * {@inheritDoc}
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
