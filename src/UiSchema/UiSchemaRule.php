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
use Derafu\Form\Contract\UiSchema\UiSchemaRuleInterface;
use Derafu\Support\JsonSerializer;
use InvalidArgumentException;

/**
 * Represents a rule in a UI Schema element.
 */
final class UiSchemaRule implements UiSchemaRuleInterface
{
    /**
     * @param UiSchemaRuleEffect                    $effect    The effect to apply.
     * @param UiSchemaCompositeConditionInterface   $condition The condition (always composite).
     */
    public function __construct(
        private readonly UiSchemaRuleEffect $effect,
        private readonly UiSchemaCompositeConditionInterface $condition,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getEffect(): UiSchemaRuleEffect
    {
        return $this->effect;
    }

    /**
     * {@inheritDoc}
     */
    public function getCondition(): UiSchemaCompositeConditionInterface
    {
        return $this->condition;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'effect' => $this->effect->value,
            'condition' => $this->condition->toArray(),
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
        if (!isset($definition['effect'])) {
            throw new InvalidArgumentException(
                'A UiSchemaRule definition requires an "effect" key.'
            );
        }

        if (!isset($definition['condition'])) {
            throw new InvalidArgumentException(
                'A UiSchemaRule definition requires a "condition" key.'
            );
        }

        $effect = UiSchemaRuleEffect::from($definition['effect']);
        $condDef = $definition['condition'];

        // Composite condition: has a "type" key with a valid AND/OR value.
        if (
            isset($condDef['type'])
            && UiSchemaCompositeConditionType::tryFrom($condDef['type']) !== null
        ) {
            $condition = UiSchemaCompositeCondition::fromArray($condDef);
        } else {
            // Simple condition: normalise to a single-item AND composite.
            $simple = UiSchemaCondition::fromArray($condDef);
            $condition = new UiSchemaCompositeCondition(
                UiSchemaCompositeConditionType::AND,
                [$simple]
            );
        }

        return new static($effect, $condition);
    }
}
