<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Rules;

use BadMethodCallException;
use Derafu\Form\Contract\Rules\FormRulesInterface;
use Derafu\Support\JsonSerializer;

/**
 * Per-field processing rules for a form.
 *
 * Starts with the rules declared in the form's 'rules' section. After the
 * resolver calls fill(), the collection holds the complete merged set
 * (schema-derived + UI-derived + explicit). All reads always reflect the
 * current contents of the collection.
 *
 * ArrayAccess writes are not supported; use fill() for bulk population.
 */
final class FormRules implements FormRulesInterface
{
    /**
     * @param array<string, array> $rules Rules keyed by field name.
     */
    public function __construct(private array $rules = [])
    {
    }

    /**
     * {@inheritDoc}
     */
    public function fill(array $resolvedRules): void
    {
        $this->rules = $resolvedRules;
    }

    /**
     * {@inheritDoc}
     */
    public function getCastRule(string $fieldName): ?string
    {
        return $this->rules[$fieldName]['cast'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSanitizeRules(string $fieldName): array
    {
        return $this->rules[$fieldName]['sanitize'] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getTransformRules(string $fieldName): array
    {
        return $this->rules[$fieldName]['transform'] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function getValidateRules(string $fieldName): array
    {
        return $this->rules[$fieldName]['validate'] ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->rules[$offset]);
    }

    /**
     * Returns the complete rules array for the given field, or [] if none.
     *
     * {@inheritDoc}
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->rules[$offset] ?? [];
    }

    /**
     * Not supported — use fill() for bulk population.
     *
     * @throws BadMethodCallException Always.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new BadMethodCallException('FormRules does not support offsetSet(). Use fill() to populate resolved rules.');
    }

    /**
     * Not supported.
     *
     * @throws BadMethodCallException Always.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new BadMethodCallException('FormRules does not support offsetUnset().');
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->rules;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->rules;
    }

    /**
     * Converts the rules to a JSON string.
     *
     * @return string
     */
    public function toJson(): string
    {
        return JsonSerializer::serialize($this->jsonSerialize());
    }

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $rules): static
    {
        return new static($rules);
    }
}
