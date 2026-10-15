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
 * It holds two sets of rules:
 *
 *   - The rules **declared** in the form's 'rules' section. They are set when
 *     the object is created and never change: they are what toArray() and
 *     jsonSerialize() give, because they are the definition of the form.
 *   - The rules **resolved** by the resolver with fill(): the complete merged
 *     set (schema-derived + UI-derived + explicit). Once there is one, every
 *     read (ArrayAccess and the getters of each category) gives it; before
 *     that, the declared rules.
 *
 * The resolver always starts from the declared rules, so resolving a form again
 * gives the same result.
 *
 * ArrayAccess writes are not supported; use fill() for bulk population.
 */
final class FormRules implements FormRulesInterface
{
    /**
     * The rules that the definition of the form declares, of each field.
     *
     * @var array<string, RuleSet>
     */
    private array $declared;

    /**
     * The rules that the resolver gave, of each field (`null` until it does).
     *
     * @var array<string, RuleSet>|null
     */
    private ?array $resolved = null;

    /**
     * @param array<string, array<array-key, mixed>> $rules Rules keyed by field
     * name, each one in the array format of the Data Processor (see
     * `RuleSet::fromArray()`).
     * @throws ProcessorException If the rules of a field are not valid.
     */
    public function __construct(array $rules = [])
    {
        $this->declared = $this->toSets($rules);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, array<array-key, mixed>> $resolvedRules
     * @throws ProcessorException If the rules of a field are not valid.
     */
    public function fill(array $resolvedRules): void
    {
        $this->resolved = $this->toSets($resolvedRules);
    }

    /**
     * The rules that are read: the resolved ones, or the declared ones if there
     * are none yet.
     *
     * @return array<string, RuleSet>
     */
    private function effective(): array
    {
        return $this->resolved ?? $this->declared;
    }

    /**
     * {@inheritDoc}
     */
    public function getCastRule(string $fieldName): ?string
    {
        return ($this->effective()[$fieldName] ?? null)?->cast;
    }

    /**
     * {@inheritDoc}
     */
    public function getSanitizeRules(string $fieldName): array
    {
        $rules = $this->effective();

        return isset($rules[$fieldName]) ? $rules[$fieldName]->sanitize : [];
    }

    /**
     * {@inheritDoc}
     */
    public function getTransformRules(string $fieldName): array
    {
        $rules = $this->effective();

        return isset($rules[$fieldName]) ? $rules[$fieldName]->transform : [];
    }

    /**
     * {@inheritDoc}
     */
    public function getValidateRules(string $fieldName): array
    {
        $rules = $this->effective();

        return isset($rules[$fieldName]) ? $rules[$fieldName]->validate : [];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->effective()[$offset]);
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
        return ($this->effective()[$offset] ?? null)?->toArray() ?? [];
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
        return array_map(static fn (RuleSet $rules): array => $rules->toArray(), $this->declared);
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
