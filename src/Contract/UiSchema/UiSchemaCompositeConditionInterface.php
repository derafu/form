<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\UiSchema;

use Derafu\Form\UiSchema\UiSchemaCompositeConditionType;
use JsonSerializable;

/**
 * Represents a composite (branch) condition in a UI Schema rule.
 *
 * A composite condition combines two or more child conditions using a logical
 * operator (AND / OR). Each child may itself be a simple condition
 * ({@see UiSchemaConditionInterface}) or another composite condition,
 * allowing arbitrarily deep nesting.
 *
 * Example — OR composite:
 * ```json
 * {
 *   "type": "OR",
 *   "conditions": [
 *     { "scope": "#/properties/foo", "schema": { "const": "a" } },
 *     { "scope": "#/properties/foo", "schema": { "const": "b" } }
 *   ]
 * }
 * ```
 *
 * ## Normalisation
 *
 * `UiSchemaRule::fromArray()` always normalises simple JSON Forms conditions
 * to a single-item `AND` composite so that the rest of the system only needs
 * to handle `UiSchemaCompositeConditionInterface`. Serialisation
 * (`toArray()` / `toJson()`) reverses this normalisation, emitting the original
 * simple format when the composite holds exactly one simple child under `AND`.
 *
 * @see https://jsonforms.io/docs/uischema/rules
 */
interface UiSchemaCompositeConditionInterface extends JsonSerializable
{
    /**
     * Returns the logical operator that combines the child conditions.
     */
    public function getType(): UiSchemaCompositeConditionType;

    /**
     * Returns the child conditions.
     *
     * Each element is either a {@see UiSchemaConditionInterface} (simple) or
     * another {@see UiSchemaCompositeConditionInterface} (nested composite).
     *
     * @return array<int, UiSchemaConditionInterface|UiSchemaCompositeConditionInterface>
     */
    public function getConditions(): array;

    /**
     * Returns the composite condition as a plain array suitable for
     * serialisation.
     *
     * When the composite was created by normalising a simple condition (one
     * child, type AND), the output matches the original simple format so that
     * round-trips are transparent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Returns the composite condition serialised as a JSON string.
     */
    public function toJson(): string;

    /**
     * Creates a composite condition from a plain array definition.
     *
     * Expected keys: `type` (string, AND|OR) and `conditions` (non-empty array).
     *
     * @param array<string, mixed> $definition
     * @throws \InvalidArgumentException If required keys are missing or the
     *                                   type value is not a valid operator.
     */
    public static function fromArray(array $definition): static;
}
