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

use Derafu\Form\Contract\UiSchema\UiSchemaCompositeConditionInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaConditionInterface;
use Derafu\Support\JsonSerializer;
use InvalidArgumentException;

/**
 * Composite (branch) condition combining child conditions with AND / OR.
 */
final class UiSchemaCompositeCondition implements UiSchemaCompositeConditionInterface
{
    /**
     * @param UiSchemaCompositeConditionType                                        $type       Logical operator.
     * @param array<int, UiSchemaConditionInterface|UiSchemaCompositeConditionInterface> $conditions Child conditions.
     */
    public function __construct(
        private readonly UiSchemaCompositeConditionType $type,
        private readonly array $conditions,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): UiSchemaCompositeConditionType
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    /**
     * {@inheritDoc}
     *
     * Reverses normalisation: a single-item AND wrapping one simple condition
     * is serialised back to the original simple format so that round-trips
     * between array/JSON and object representations are transparent.
     */
    public function toArray(): array
    {
        if (
            $this->type === UiSchemaCompositeConditionType::AND
            && count($this->conditions) === 1
            && $this->conditions[0] instanceof UiSchemaConditionInterface
            && !$this->conditions[0] instanceof UiSchemaCompositeConditionInterface
        ) {
            return $this->conditions[0]->toArray();
        }

        return [
            'type' => $this->type->value,
            'conditions' => array_map(
                static fn ($c) => $c->toArray(),
                $this->conditions
            ),
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
        if (!isset($definition['type'])) {
            throw new InvalidArgumentException(
                'A UiSchemaCompositeCondition definition requires a "type" key.'
            );
        }

        if (empty($definition['conditions'])) {
            throw new InvalidArgumentException(
                'A UiSchemaCompositeCondition definition requires a non-empty "conditions" array.'
            );
        }

        $type = UiSchemaCompositeConditionType::from($definition['type']);

        $conditions = [];
        foreach ($definition['conditions'] as $condDef) {
            // A child with a "type" key that is a valid composite type is itself composite.
            if (
                isset($condDef['type'])
                && UiSchemaCompositeConditionType::tryFrom($condDef['type']) !== null
            ) {
                $conditions[] = static::fromArray($condDef);
            } else {
                $conditions[] = UiSchemaCondition::fromArray($condDef);
            }
        }

        return new static($type, $conditions);
    }
}
