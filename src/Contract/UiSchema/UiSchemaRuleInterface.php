<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\UiSchema;

use Derafu\Form\UiSchema\UiSchemaRuleEffect;
use JsonSerializable;

/**
 * Represents a rule in a UI Schema element.
 *
 * A rule defines a conditional behavior for a UI element: depending on the
 * evaluation of a condition against the current form data, the element is
 * shown, hidden, enabled, or disabled (the effect).
 *
 * Follows the JSON Forms rule specification:
 * @see https://jsonforms.io/docs/uischema/rules
 *
 * ## Simple condition
 *
 * Targets a single field by scope and tests its value against a JSON Schema
 * fragment. Internally normalised to a single-item AND composite.
 *
 * ```json
 * {
 *   "effect": "SHOW",
 *   "condition": {
 *     "scope": "#/properties/tipo_contrato",
 *     "schema": { "const": "plazo_fijo" }
 *   }
 * }
 * ```
 *
 * ## Composite condition
 *
 * Combines multiple conditions with a logical AND or OR operator. Conditions
 * may be nested arbitrarily.
 *
 * ```json
 * {
 *   "effect": "SHOW",
 *   "condition": {
 *     "type": "OR",
 *     "conditions": [
 *       { "scope": "#/properties/foo", "schema": { "const": "a" } },
 *       { "scope": "#/properties/bar", "schema": { "const": "b" } }
 *     ]
 *   }
 * }
 * ```
 */
interface UiSchemaRuleInterface extends JsonSerializable
{
    /**
     * Returns the effect to apply based on the condition result.
     */
    public function getEffect(): UiSchemaRuleEffect;

    /**
     * Returns the condition for this rule.
     *
     * Always returns a {@see UiSchemaCompositeConditionInterface}: simple
     * conditions are normalised to a single-item AND composite on creation.
     */
    public function getCondition(): UiSchemaCompositeConditionInterface;

    /**
     * Returns the rule serialised as a plain array.
     *
     * Simple conditions that were normalised internally are emitted back in
     * their original simple format, making round-trips transparent.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Returns the rule serialised as a JSON string.
     */
    public function toJson(): string;

    /**
     * Creates a rule instance from its plain array definition.
     *
     * @param array<string, mixed> $definition Raw rule array from the form definition.
     * @throws \InvalidArgumentException If required keys are missing.
     */
    public static function fromArray(array $definition): static;
}
