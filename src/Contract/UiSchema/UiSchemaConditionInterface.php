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
 * ## Supported schema keywords
 *
 * The following JSON Schema keywords are currently evaluated server-side:
 *
 *   - `const`  — the value must equal the constant (strict comparison).
 *   - `enum`   — the value must be one of the listed values.
 *
 * ## Pending schema keywords (add support as needed)
 *
 * The following keywords are valid in JSON Forms conditions but are **not yet
 * evaluated** server-side. They are stored and serialised for the client but
 * will cause the condition to evaluate to `false` on the server until support
 * is added to `FormDataProcessor::evaluateSchema()`:
 *
 *   - `contains`          — array field must contain the given item (multiselect).
 *   - `not`               — negation of a schema fragment.
 *   - `minimum`/`maximum` — numeric range checks.
 *
 * Add a case to `evaluateSchema()` and a corresponding test before using any
 * of the above in a form that requires server-side rule processing.
 *
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
     * Returns the JSON Schema fragment used to test the field value.
     *
     * Example: `["const" => "plazo_fijo"]`.
     */
    public function getSchema(): array;

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
