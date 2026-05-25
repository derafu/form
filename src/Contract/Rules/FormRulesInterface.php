<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Rules;

use ArrayAccess;
use JsonSerializable;

/**
 * Represents the explicit processing rules defined in a form's 'rules' section.
 *
 * This interface models the collection of per-field processing rules that
 * supplement the rules derived automatically from the schema and UI schema.
 * It follows the same naming conventions as FormSchema, FormData, and
 * FormOptions — typed getters parameterised by field name, ArrayAccess for
 * positional access, and fromArray()/toArray() for serialisation round-trips.
 *
 * Rule categories match Derafu Data Processor terminology:
 *   - cast:      single string (e.g. 'integer', 'float').
 *   - sanitize:  ordered list of sanitizers (e.g. ['trim', 'strip_tags']).
 *   - transform: ordered list of transformers (e.g. ['lowercase', 'slug']).
 *   - validate:  ordered list of validators (e.g. ['required', 'min_length:3']).
 *
 * Field names follow the same dot-notation convention as the rest of the
 * package (e.g. "address.city" for nested properties).
 *
 * ArrayAccess contract: $rules[$fieldName] returns the complete rules array
 * for that field (same shape as the Data Processor expects) or [] when no
 * rules exist for that field. offsetSet() and offsetUnset() throw
 * BadMethodCallException; use fill() to replace the contents in bulk.
 */
interface FormRulesInterface extends ArrayAccess, JsonSerializable
{
    /**
     * Returns the explicit cast rule for a field, or null if none is defined.
     *
     * @param string $fieldName Field name (dot notation for nested fields).
     * @return string|null The cast target type (e.g. 'integer', 'float', 'boolean').
     */
    public function getCastRule(string $fieldName): ?string;

    /**
     * Returns the explicit sanitize rules for a field.
     *
     * @param string $fieldName Field name (dot notation for nested fields).
     * @return array<int,string> Ordered list of sanitizer rule strings.
     */
    public function getSanitizeRules(string $fieldName): array;

    /**
     * Returns the explicit transform rules for a field.
     *
     * @param string $fieldName Field name (dot notation for nested fields).
     * @return array<int,string> Ordered list of transformer rule strings.
     */
    public function getTransformRules(string $fieldName): array;

    /**
     * Returns the explicit validate rules for a field.
     *
     * @param string $fieldName Field name (dot notation for nested fields).
     * @return array<int,string> Ordered list of validator rule strings.
     */
    public function getValidateRules(string $fieldName): array;

    /**
     * Replaces the contents of this collection with the fully resolved rules.
     *
     * Called by FormRulesResolverInterface after merging schema-derived,
     * UI-derived, and explicit rules. Operates on the existing instance so
     * that $form->getRules() always reflects the current resolved state
     * without creating a second object.
     *
     * @param array<string, array> $resolvedRules Complete per-field rules keyed by field name.
     */
    public function fill(array $resolvedRules): void;

    /**
     * Returns the full rules definition as a plain array.
     *
     * The returned structure mirrors the 'rules' section of a form definition:
     * field names as keys, each value being an array with optional 'cast',
     * 'sanitize', 'transform', and 'validate' entries.
     *
     * @return array<string, array> Rules keyed by field name.
     */
    public function toArray(): array;

    /**
     * Creates a FormRules instance from a plain array.
     *
     * @param array<string, array> $rules Rules keyed by field name.
     * @return static
     */
    public static function fromArray(array $rules): static;
}
