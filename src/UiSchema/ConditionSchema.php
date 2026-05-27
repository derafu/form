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

use Derafu\Form\Contract\UiSchema\ConditionSchemaInterface;

/**
 * Implementation of ConditionSchemaInterface.
 *
 * Parses and stores the JSON Schema fragment keywords used to test field values
 * in UI Schema rule conditions.
 */
final class ConditionSchema implements ConditionSchemaInterface
{
    /**
     * Whether the `const` keyword is present (distinct from its value being null).
     */
    private bool $hasConst = false;

    /**
     * The `const` value (null when keyword is absent or when const is null).
     */
    private mixed $const = null;

    /**
     * The `enum` array, or null when absent.
     *
     * @var array|null
     */
    private ?array $enum = null;

    /**
     * The `contains` sub-condition, or null when absent.
     */
    private ?ConditionSchemaInterface $contains = null;

    /**
     * The `not` sub-condition, or null when absent.
     */
    private ?ConditionSchemaInterface $not = null;

    /**
     * The `minimum` value (inclusive), or null when absent.
     */
    private int|float|null $minimum = null;

    /**
     * The `maximum` value (inclusive), or null when absent.
     */
    private int|float|null $maximum = null;

    /**
     * The `exclusiveMinimum` value (strict), or null when absent.
     */
    private int|float|null $exclusiveMinimum = null;

    /**
     * The `exclusiveMaximum` value (strict), or null when absent.
     */
    private int|float|null $exclusiveMaximum = null;

    /**
     * The `pattern` ECMA regex string (no delimiters), or null when absent.
     */
    private ?string $pattern = null;

    /**
     * {@inheritDoc}
     */
    public function hasConst(): bool
    {
        return $this->hasConst;
    }

    /**
     * {@inheritDoc}
     */
    public function getConst(): mixed
    {
        return $this->const;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnum(): ?array
    {
        return $this->enum;
    }

    /**
     * {@inheritDoc}
     */
    public function getContains(): ?ConditionSchemaInterface
    {
        return $this->contains;
    }

    /**
     * {@inheritDoc}
     */
    public function getNot(): ?ConditionSchemaInterface
    {
        return $this->not;
    }

    /**
     * {@inheritDoc}
     */
    public function getMinimum(): int|float|null
    {
        return $this->minimum;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaximum(): int|float|null
    {
        return $this->maximum;
    }

    /**
     * {@inheritDoc}
     */
    public function getExclusiveMinimum(): int|float|null
    {
        return $this->exclusiveMinimum;
    }

    /**
     * {@inheritDoc}
     */
    public function getExclusiveMaximum(): int|float|null
    {
        return $this->exclusiveMaximum;
    }

    /**
     * {@inheritDoc}
     */
    public function getPattern(): ?string
    {
        return $this->pattern;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $array = [];

        if ($this->hasConst) {
            $array['const'] = $this->const;
        }

        if ($this->enum !== null) {
            $array['enum'] = $this->enum;
        }

        if ($this->contains !== null) {
            $array['contains'] = $this->contains->toArray();
        }

        if ($this->not !== null) {
            $array['not'] = $this->not->toArray();
        }

        if ($this->minimum !== null) {
            $array['minimum'] = $this->minimum;
        }

        if ($this->maximum !== null) {
            $array['maximum'] = $this->maximum;
        }

        if ($this->exclusiveMinimum !== null) {
            $array['exclusiveMinimum'] = $this->exclusiveMinimum;
        }

        if ($this->exclusiveMaximum !== null) {
            $array['exclusiveMaximum'] = $this->exclusiveMaximum;
        }

        if ($this->pattern !== null) {
            $array['pattern'] = $this->pattern;
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $definition): static
    {
        $schema = new static();

        if (array_key_exists('const', $definition)) {
            $schema->hasConst = true;
            $schema->const = $definition['const'];
        }

        if (array_key_exists('enum', $definition)) {
            $schema->enum = (array) $definition['enum'];
        }

        if (array_key_exists('contains', $definition) && is_array($definition['contains'])) {
            $schema->contains = static::fromArray($definition['contains']);
        }

        if (array_key_exists('not', $definition) && is_array($definition['not'])) {
            $schema->not = static::fromArray($definition['not']);
        }

        if (array_key_exists('minimum', $definition)) {
            $schema->minimum = $definition['minimum'];
        }

        if (array_key_exists('maximum', $definition)) {
            $schema->maximum = $definition['maximum'];
        }

        if (array_key_exists('exclusiveMinimum', $definition)) {
            $schema->exclusiveMinimum = $definition['exclusiveMinimum'];
        }

        if (array_key_exists('exclusiveMaximum', $definition)) {
            $schema->exclusiveMaximum = $definition['exclusiveMaximum'];
        }

        if (array_key_exists('pattern', $definition)) {
            $schema->pattern = (string) $definition['pattern'];
        }

        return $schema;
    }
}
