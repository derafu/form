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
use Derafu\Form\Contract\Schema\ArraySchemaInterface;
use Derafu\Form\Contract\Schema\PropertySchemaInterface;
use Derafu\Form\Factory\PropertySchemaFactory;

/**
 * Implementation of ArraySchemaInterface.
 *
 * This class represents an array schema and provides array-specific validations.
 */
final class ArraySchema extends AbstractPropertySchema implements ArraySchemaInterface
{
    /**
     * The schema for array items.
     *
     * @var PropertySchemaInterface|null
     */
    protected ?PropertySchemaInterface $items = null;

    /**
     * The maximum number of items.
     *
     * @var int|null
     */
    protected ?int $maxItems = null;

    /**
     * The minimum number of items.
     *
     * @var int|null
     */
    protected ?int $minItems = null;

    /**
     * The schema that at least one item must match.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $contains = null;

    /**
     * The maximum number of items matching the "contains" schema.
     *
     * @var int|null
     */
    protected ?int $maxContains = null;

    /**
     * The minimum number of items matching the "contains" schema.
     *
     * @var int|null
     */
    protected ?int $minContains = null;

    /**
     * Whether all items must be unique.
     *
     * @var bool|null
     */
    protected ?bool $uniqueItems = null;

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'array';
    }

    /**
     * {@inheritDoc}
     */
    public function getItems(): ?PropertySchemaInterface
    {
        return $this->items;
    }

    /**
     * {@inheritDoc}
     */
    public function setItems(PropertySchemaInterface $items): static
    {
        $this->items = $items;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxItems(): ?int
    {
        return $this->maxItems;
    }

    /**
     * {@inheritDoc}
     */
    public function setMaxItems(int $maxItems): static
    {
        $this->maxItems = $maxItems;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMinItems(): ?int
    {
        return $this->minItems;
    }

    /**
     * {@inheritDoc}
     */
    public function setMinItems(int $minItems): static
    {
        $this->minItems = $minItems;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>|null
     */
    public function getContains(): ?array
    {
        return $this->contains;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $contains
     */
    public function setContains(array $contains): static
    {
        $this->contains = $contains;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxContains(): ?int
    {
        return $this->maxContains;
    }

    /**
     * {@inheritDoc}
     */
    public function setMaxContains(int $maxContains): static
    {
        $this->maxContains = $maxContains;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMinContains(): ?int
    {
        return $this->minContains;
    }

    /**
     * {@inheritDoc}
     */
    public function setMinContains(int $minContains): static
    {
        $this->minContains = $minContains;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function needUniqueItems(): ?bool
    {
        return $this->uniqueItems;
    }

    /**
     * {@inheritDoc}
     */
    public function setUniqueItems(bool $uniqueItems = true): static
    {
        $this->uniqueItems = $uniqueItems;
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

        if ($this->items !== null) {
            $array['items'] = $this->items->toArray();
        }

        if ($this->maxItems !== null) {
            $array['maxItems'] = $this->maxItems;
        }

        if ($this->minItems !== null) {
            $array['minItems'] = $this->minItems;
        }

        if ($this->contains !== null) {
            $array['contains'] = $this->contains;
        }

        if ($this->maxContains !== null) {
            $array['maxContains'] = $this->maxContains;
        }

        if ($this->minContains !== null) {
            $array['minContains'] = $this->minContains;
        }

        if ($this->uniqueItems !== null) {
            $array['uniqueItems'] = $this->uniqueItems;
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

        // Set array-specific properties.
        if (is_array($def->items)) {
            $schema->setItems(PropertySchemaFactory::create($def->items));
        }

        if ($def->maxItems !== null) {
            $schema->setMaxItems($def->maxItems);
        }

        if ($def->minItems !== null) {
            $schema->setMinItems($def->minItems);
        }

        if ($def->contains !== null) {
            $schema->setContains($def->contains);
        }

        if ($def->maxContains !== null) {
            $schema->setMaxContains($def->maxContains);
        }

        if ($def->minContains !== null) {
            $schema->setMinContains($def->minContains);
        }

        if ($def->uniqueItems !== null) {
            $schema->setUniqueItems($def->uniqueItems);
        }

        return $schema;
    }
}
