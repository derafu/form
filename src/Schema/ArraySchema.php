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
     * @var array|null
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
     */
    public function getContains(): ?array
    {
        return $this->contains;
    }

    /**
     * {@inheritDoc}
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
     */
    public static function fromArray(array $definition): static
    {
        $schema = new static($definition['name'] ?? '');

        // Set common properties.
        if (isset($definition['title'])) {
            $schema->setTitle($definition['title']);
        }

        if (isset($definition['description'])) {
            $schema->setDescription($definition['description']);
        }

        if (isset($definition['default'])) {
            $schema->setDefault($definition['default']);
        }

        if (isset($definition['deprecated'])) {
            $schema->setDeprecated($definition['deprecated']);
        }

        if (isset($definition['examples'])) {
            $schema->setExamples($definition['examples']);
        }

        if (isset($definition['readOnly'])) {
            $schema->setReadOnly($definition['readOnly']);
        }

        if (isset($definition['writeOnly'])) {
            $schema->setWriteOnly($definition['writeOnly']);
        }

        if (isset($definition['enum'])) {
            $schema->setEnum($definition['enum']);
        }

        if (isset($definition['const'])) {
            $schema->setConst($definition['const']);
        }

        if (isset($definition['allOf'])) {
            $schema->setAllOf($definition['allOf']);
        }

        if (isset($definition['anyOf'])) {
            $schema->setAnyOf($definition['anyOf']);
        }

        if (isset($definition['oneOf'])) {
            $schema->setOneOf($definition['oneOf']);
        }

        // Set array-specific properties.
        if (isset($definition['items']) && is_array($definition['items'])) {
            $schema->setItems(PropertySchemaFactory::create($definition['items']));
        }

        if (isset($definition['maxItems'])) {
            $schema->setMaxItems($definition['maxItems']);
        }

        if (isset($definition['minItems'])) {
            $schema->setMinItems($definition['minItems']);
        }

        if (isset($definition['contains'])) {
            $schema->setContains($definition['contains']);
        }

        if (isset($definition['maxContains'])) {
            $schema->setMaxContains($definition['maxContains']);
        }

        if (isset($definition['minContains'])) {
            $schema->setMinContains($definition['minContains']);
        }

        if (isset($definition['uniqueItems'])) {
            $schema->setUniqueItems($definition['uniqueItems']);
        }

        return $schema;
    }
}
