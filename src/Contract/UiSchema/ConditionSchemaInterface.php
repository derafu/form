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

/**
 * Represents the JSON Schema fragment used in a UI Schema rule condition.
 *
 * A condition schema is a lightweight, anonymous schema fragment that expresses
 * the matching criteria for a rule trigger. It is deliberately separate from
 * PropertySchemaInterface because:
 *
 *   - It has no `type` (the fragment tests a value, not defines a property).
 *   - It has no `name`, `title`, `description` or other metadata keywords.
 *   - Its `contains` and `not` keywords refer back to itself recursively (not
 *     to a full PropertySchemaInterface hierarchy).
 *   - `const` can legitimately be `null`, so presence and value must be
 *     distinguished via hasConst() + getConst().
 *
 * ## Supported keywords
 *
 *   - `const`              — value must equal the constant (strict, nullable).
 *   - `enum`               — value must be in the listed set.
 *   - `contains`           — value must be an array containing a matching item.
 *   - `not`                — negation of the nested condition schema.
 *   - `minimum`            — value >= minimum (inclusive numeric).
 *   - `maximum`            — value <= maximum (inclusive numeric).
 *   - `exclusiveMinimum`   — value >  exclusiveMinimum (strict numeric).
 *   - `exclusiveMaximum`   — value <  exclusiveMaximum (strict numeric).
 *   - `pattern`            — value matches the ECMA regex pattern.
 *
 * @see https://jsonforms.io/docs/uischema/rules
 */
interface ConditionSchemaInterface
{
    /**
     * Returns true if a `const` keyword is present (even when its value is null).
     */
    public function hasConst(): bool;

    /**
     * Returns the `const` value, or null when the keyword is absent.
     *
     * Use hasConst() to distinguish "absent" from "const: null".
     */
    public function getConst(): mixed;

    /**
     * Returns the `enum` array (plain list), or null when the keyword is absent.
     *
     * Per JSON Schema spec, `enum` is always a plain array of values.
     * Use the `oneOf`/`const`+`title` pattern for labelled choices.
     */
    public function getEnum(): ?array;

    /**
     * Returns the nested condition schema for the `contains` keyword, or null.
     *
     * The field value must be an array with at least one item satisfying this
     * sub-condition.
     */
    public function getContains(): ?ConditionSchemaInterface;

    /**
     * Returns the nested condition schema for the `not` keyword, or null.
     *
     * The field value must NOT satisfy this sub-condition.
     */
    public function getNot(): ?ConditionSchemaInterface;

    /**
     * Returns the `minimum` value (inclusive), or null when absent.
     */
    public function getMinimum(): int|float|null;

    /**
     * Returns the `maximum` value (inclusive), or null when absent.
     */
    public function getMaximum(): int|float|null;

    /**
     * Returns the `exclusiveMinimum` value (strict), or null when absent.
     */
    public function getExclusiveMinimum(): int|float|null;

    /**
     * Returns the `exclusiveMaximum` value (strict), or null when absent.
     */
    public function getExclusiveMaximum(): int|float|null;

    /**
     * Returns the `pattern` ECMA regex string (no delimiters), or null.
     */
    public function getPattern(): ?string;

    /**
     * Serialises the condition schema back to a plain array.
     *
     * Only keywords that are present are included. Round-trips correctly with
     * fromArray().
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Creates a ConditionSchemaInterface from a plain array fragment.
     *
     * Unknown keywords are silently ignored.
     *
     * @param array<string, mixed> $definition
     */
    public static function fromArray(array $definition): static;
}
