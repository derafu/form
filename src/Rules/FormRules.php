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
use Derafu\DataProcessor\Exception\ProcessorException;
use Derafu\DataProcessor\RuleSet;
use Derafu\Form\Contract\Rules\FormRulesInterface;
use Derafu\Support\JsonSerializer;
use Derafu\Translation\Exception\Logic\TranslatableBadMethodCallException;

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
     * The rules of each field.
     *
     * @var array<string, RuleSet>
     */
    private array $rules;

    /**
     * @param array<string, array<array-key, mixed>> $rules Rules keyed by field
     * name, each one in the array format of the Data Processor (see
     * `RuleSet::fromArray()`).
     * @throws ProcessorException If the rules of a field are not valid.
     */
    public function __construct(array $rules = [])
    {
        $this->rules = $this->toSets($rules);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, array<array-key, mixed>> $resolvedRules
     * @throws ProcessorException If the rules of a field are not valid.
     */
    public function fill(array $resolvedRules): void
    {
        $this->rules = $this->toSets($resolvedRules);
    }

    /**
     * {@inheritDoc}
     */
    public function getCastRule(string $fieldName): ?string
    {
        return ($this->rules[$fieldName] ?? null)?->cast;
    }

    /**
     * {@inheritDoc}
     */
    public function getSanitizeRules(string $fieldName): array
    {
        return isset($this->rules[$fieldName]) ? $this->rules[$fieldName]->sanitize : [];
    }

    /**
     * {@inheritDoc}
     */
    public function getTransformRules(string $fieldName): array
    {
        return isset($this->rules[$fieldName]) ? $this->rules[$fieldName]->transform : [];
    }

    /**
     * {@inheritDoc}
     */
    public function getValidateRules(string $fieldName): array
    {
        return isset($this->rules[$fieldName]) ? $this->rules[$fieldName]->validate : [];
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
     *
     * @return array{transform?: list<string>, sanitize?: list<string>, cast?: string, validate?: list<string>}
     */
    public function offsetGet(mixed $offset): mixed
    {
        return ($this->rules[$offset] ?? null)?->toArray() ?? [];
    }

    /**
     * Not supported — use fill() for bulk population.
     *
     * @throws BadMethodCallException Always.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new TranslatableBadMethodCallException('FormRules does not support offsetSet(). Use fill() to populate resolved rules.');
    }

    /**
     * Not supported.
     *
     * @throws BadMethodCallException Always.
     */
    public function offsetUnset(mixed $offset): void
    {
        throw new TranslatableBadMethodCallException('FormRules does not support offsetUnset().');
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, array{transform?: list<string>, sanitize?: list<string>, cast?: string, validate?: list<string>}>
     */
    public function toArray(): array
    {
        return array_map(static fn (RuleSet $rules): array => $rules->toArray(), $this->rules);
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, array{transform?: list<string>, sanitize?: list<string>, cast?: string, validate?: list<string>}>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
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
     *
     * @param array<string, array<array-key, mixed>> $rules
     * @throws ProcessorException If the rules of a field are not valid.
     */
    public static function fromArray(array $rules): static
    {
        return new static($rules);
    }

    /**
     * Turns the rules of each field into a set of rules, validating them.
     *
     * @param array<string, array<array-key, mixed>> $rules
     * @return array<string, RuleSet>
     * @throws ProcessorException If the rules of a field are not valid.
     */
    private function toSets(array $rules): array
    {
        return array_map(static fn (array $fieldRules): RuleSet => RuleSet::fromArray($fieldRules), $rules);
    }
}
