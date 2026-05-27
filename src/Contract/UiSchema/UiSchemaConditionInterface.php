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

use JsonSerializable;

/**
 * Represents a simple (leaf) condition in a UI Schema rule.
 *
 * A simple condition targets a single field by its JSON Pointer scope and
 * tests its submitted value against a JSON Schema fragment.
 *
 * Example:
 * ```json
 * { "scope": "#/properties/tipo_contrato", "schema": { "const": "plazo_fijo" } }
 * ```
 *
 * The schema fragment is represented as a ConditionSchemaInterface object.
 * See that interface for the full list of supported keywords.
 *
 * @see ConditionSchemaInterface
 * @see https://jsonforms.io/docs/uischema/rules
 */
interface UiSchemaConditionInterface extends JsonSerializable
{
    /**
     * Returns the JSON Pointer scope of the field to evaluate.
     *
     * Example: `"#/properties/tipo_contrato"`.
     */
    public function getScope(): string;

    /**
     * Returns the condition schema fragment used to test the field value.
     *
     * Example: `new ConditionSchema(['const' => 'plazo_fijo'])`.
     */
    public function getSchema(): ConditionSchemaInterface;

    /**
     * Returns the condition as a plain array suitable for serialisation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Returns the condition serialised as a JSON string.
     */
    public function toJson(): string;

    /**
     * Creates a condition from a plain array definition.
     *
     * Expected keys: `scope` (string) and `schema` (array).
     *
     * @param array<string, mixed> $definition
     * @throws \InvalidArgumentException If required keys are missing.
     */
    public static function fromArray(array $definition): static;
}
