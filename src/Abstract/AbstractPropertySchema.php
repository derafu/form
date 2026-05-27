<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Abstract;

use Derafu\Form\Contract\Schema\PropertySchemaInterface;
use Derafu\Support\JsonSerializer;

/**
 * Base implementation of PropertySchemaInterface.
 *
 * This class provides common functionality for all schema types.
 */
abstract class AbstractPropertySchema implements PropertySchemaInterface
{
    /**
     * The name of the property.
     *
     * @var string
     */
    protected string $name;

    /**
     * A short description about the purpose of the property.
     *
     * @var string|null
     */
    protected ?string $title = null;

    /**
     * An explanation about the purpose of the property.
     *
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * A default value for the property.
     *
     * @var mixed
     */
    protected mixed $default = null;

    /**
     * Indicates that users should refrain from using the property.
     *
     * @var bool|null
     */
    protected ?bool $deprecated = null;

    /**
     * Sample values for the property.
     *
     * @var array|null
     */
    protected ?array $examples = null;

    /**
     * Indicates that the property is read-only.
     *
     * @var bool|null
     */
    protected ?bool $readOnly = null;

    /**
     * Indicates that the property is write-only.
     *
     * @var bool|null
     */
    protected ?bool $writeOnly = null;

    /**
     * Enum values for the property.
     *
     * @var array<int,int|float|string|bool>|null
     */
    protected ?array $enum = null;

    /**
     * Constant value that the property must equal.
     *
     * @var mixed
     */
    protected mixed $const = null;

    /**
     * List of schemas that the property must match all of.
     *
     * @var array|null
     */
    protected ?array $allOf = null;

    /**
     * List of schemas that the property must match at least one of.
     *
     * @var array|null
     */
    protected ?array $anyOf = null;

    /**
     * List of schemas that the property must match exactly one of.
     *
     * @var array|null
     */
    protected ?array $oneOf = null;

    /**
     * Constructs a new PropertySchema.
     *
     * @param string $name The name of the property.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * {@inheritDoc}
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getDefault(): mixed
    {
        return $this->default;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefault(mixed $default): static
    {
        $this->default = $default;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isDeprecated(): ?bool
    {
        return $this->deprecated;
    }

    /**
     * {@inheritDoc}
     */
    public function setDeprecated(bool $deprecated = true): static
    {
        $this->deprecated = $deprecated;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getExamples(): ?array
    {
        return $this->examples;
    }

    /**
     * {@inheritDoc}
     */
    public function setExamples(array $examples): static
    {
        $this->examples = $examples;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isReadOnly(): ?bool
    {
        return $this->readOnly;
    }

    /**
     * {@inheritDoc}
     */
    public function setReadOnly(bool $readOnly = true): static
    {
        $this->readOnly = $readOnly;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isWriteOnly(): ?bool
    {
        return $this->writeOnly;
    }

    /**
     * {@inheritDoc}
     */
    public function setWriteOnly(bool $writeOnly = true): static
    {
        $this->writeOnly = $writeOnly;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    abstract public function getType(): string;

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
    public function setEnum(array $enum): static
    {
        $this->enum = $enum;

        return $this;
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
    public function setConst(mixed $const): static
    {
        $this->const = $const;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAllOf(): ?array
    {
        return $this->allOf;
    }

    /**
     * {@inheritDoc}
     */
    public function setAllOf(array $allOf): static
    {
        $this->allOf = $allOf;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAnyOf(): ?array
    {
        return $this->anyOf;
    }

    /**
     * {@inheritDoc}
     */
    public function setAnyOf(array $anyOf): static
    {
        $this->anyOf = $anyOf;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getOneOf(): ?array
    {
        return $this->oneOf;
    }

    /**
     * {@inheritDoc}
     */
    public function setOneOf(array $oneOf): static
    {
        $this->oneOf = $oneOf;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $array = [
            'type' => $this->getType(),
        ];

        // Add common properties if they are set.
        if ($this->title !== null) {
            $array['title'] = $this->title;
        }

        if ($this->description !== null) {
            $array['description'] = $this->description;
        }

        if ($this->default !== null) {
            $array['default'] = $this->default;
        }

        if ($this->deprecated !== null) {
            $array['deprecated'] = $this->deprecated;
        }

        if ($this->examples !== null) {
            $array['examples'] = $this->examples;
        }

        if ($this->readOnly !== null) {
            $array['readOnly'] = $this->readOnly;
        }

        if ($this->writeOnly !== null) {
            $array['writeOnly'] = $this->writeOnly;
        }

        if ($this->enum !== null) {
            $array['enum'] = $this->enum;
        }

        if ($this->const !== null) {
            $array['const'] = $this->const;
        }

        if ($this->allOf !== null) {
            $array['allOf'] = $this->allOf;
        }

        if ($this->anyOf !== null) {
            $array['anyOf'] = $this->anyOf;
        }

        if ($this->oneOf !== null) {
            $array['oneOf'] = $this->oneOf;
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     */
    public function toJson(): string
    {
        return JsonSerializer::serialize($this->toArray());
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * {@inheritDoc}
     */
    abstract public static function fromArray(array $definition): static;
}
