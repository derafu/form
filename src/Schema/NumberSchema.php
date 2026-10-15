<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Schema;

use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Contract\Schema\NumberSchemaInterface;

/**
 * Implementation of NumberSchemaInterface.
 *
 * This class represents a number schema and provides number-specific validations.
 */
final class NumberSchema extends AbstractPropertySchema implements NumberSchemaInterface
{
    /**
     * The exclusive maximum value.
     *
     * @var int|float|null
     */
    protected int|float|null $exclusiveMaximum = null;

    /**
     * The exclusive minimum value.
     *
     * @var int|float|null
     */
    protected int|float|null $exclusiveMinimum = null;

    /**
     * The maximum value.
     *
     * @var int|float|null
     */
    protected int|float|null $maximum = null;

    /**
     * The minimum value.
     *
     * @var int|float|null
     */
    protected int|float|null $minimum = null;

    /**
     * The divisor for the value.
     *
     * @var int|float|null
     */
    protected int|float|null $multipleOf = null;

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'number';
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
    public function setExclusiveMaximum(int|float $exclusiveMaximum): static
    {
        $this->exclusiveMaximum = $exclusiveMaximum;
        return $this;
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
    public function setExclusiveMinimum(int|float $exclusiveMinimum): static
    {
        $this->exclusiveMinimum = $exclusiveMinimum;
        return $this;
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
    public function setMaximum(int|float $maximum): static
    {
        $this->maximum = $maximum;
        return $this;
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
    public function setMinimum(int|float $minimum): static
    {
        $this->minimum = $minimum;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMultipleOf(): int|float|null
    {
        return $this->multipleOf;
    }

    /**
     * {@inheritDoc}
     */
    public function setMultipleOf(int|float $multipleOf): static
    {
        $this->multipleOf = $multipleOf;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        if ($this->exclusiveMaximum !== null) {
            $array['exclusiveMaximum'] = $this->exclusiveMaximum;
        }

        if ($this->exclusiveMinimum !== null) {
            $array['exclusiveMinimum'] = $this->exclusiveMinimum;
        }

        if ($this->maximum !== null) {
            $array['maximum'] = $this->maximum;
        }

        if ($this->minimum !== null) {
            $array['minimum'] = $this->minimum;
        }

        if ($this->multipleOf !== null) {
            $array['multipleOf'] = $this->multipleOf;
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $definition
     */
    public static function fromArray(array $definition): static
    {
        $def = PropertyDefinition::fromArray($definition);
        $schema = new static($def->name);

        // Set common properties.
        if ($def->title !== null) {
            $schema->setTitle($def->title);
        }

        if ($def->description !== null) {
            $schema->setDescription($def->description);
        }

        if ($def->default !== null) {
            $schema->setDefault($def->default);
        }

        if ($def->deprecated !== null) {
            $schema->setDeprecated($def->deprecated);
        }

        if ($def->examples !== null) {
            $schema->setExamples($def->examples);
        }

        if ($def->readOnly !== null) {
            $schema->setReadOnly($def->readOnly);
        }

        if ($def->writeOnly !== null) {
            $schema->setWriteOnly($def->writeOnly);
        }

        if ($def->enum !== null) {
            $schema->setEnum($def->enum);
        }

        if ($def->const !== null) {
            $schema->setConst($def->const);
        }

        if ($def->allOf !== null) {
            $schema->setAllOf($def->allOf);
        }

        if ($def->anyOf !== null) {
            $schema->setAnyOf($def->anyOf);
        }

        if ($def->oneOf !== null) {
            $schema->setOneOf($def->oneOf);
        }

        // Set number-specific properties.
        if ($def->exclusiveMaximum !== null) {
            $schema->setExclusiveMaximum($def->exclusiveMaximum);
        }

        if ($def->exclusiveMinimum !== null) {
            $schema->setExclusiveMinimum($def->exclusiveMinimum);
        }

        if ($def->maximum !== null) {
            $schema->setMaximum($def->maximum);
        }

        if ($def->minimum !== null) {
            $schema->setMinimum($def->minimum);
        }

        if ($def->multipleOf !== null) {
            $schema->setMultipleOf($def->multipleOf);
        }

        return $schema;
    }
}
