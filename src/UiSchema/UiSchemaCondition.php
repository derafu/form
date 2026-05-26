<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\UiSchema;

use Derafu\Form\Contract\UiSchema\UiSchemaConditionInterface;
use Derafu\Support\JsonSerializer;
use InvalidArgumentException;

/**
 * Simple (leaf) condition targeting a single field.
 */
final class UiSchemaCondition implements UiSchemaConditionInterface
{
    /**
     * @param string $scope  JSON Pointer scope, e.g. "#/properties/tipo_contrato".
     * @param array  $schema JSON Schema fragment, e.g. ["const" => "plazo_fijo"].
     */
    public function __construct(
        private readonly string $scope,
        private readonly array $schema,
    ) {
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
    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'scope' => $this->scope,
            'schema' => $this->schema,
        ];
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
        return JsonSerializer::serialize($this->toArray());
    }

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $definition): static
    {
        if (!isset($definition['scope'])) {
            throw new InvalidArgumentException(
                'A UiSchemaCondition definition requires a "scope" key.'
            );
        }

        if (!isset($definition['schema'])) {
            throw new InvalidArgumentException(
                'A UiSchemaCondition definition requires a "schema" key.'
            );
        }

        return new static($definition['scope'], $definition['schema']);
    }
}
