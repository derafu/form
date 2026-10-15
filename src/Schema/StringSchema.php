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
use Derafu\Form\Contract\Schema\StringSchemaInterface;

/**
 * Implementation of StringSchemaInterface.
 *
 * This class represents a string schema and provides string-specific validations.
 */
final class StringSchema extends AbstractPropertySchema implements StringSchemaInterface
{
    /**
     * The semantic format of the string.
     *
     * @var string|null
     */
    protected ?string $format = null;

    /**
     * The maximum length of the string.
     *
     * @var int|null
     */
    protected ?int $maxLength = null;

    /**
     * The minimum length of the string.
     *
     * @var int|null
     */
    protected ?int $minLength = null;

    /**
     * The pattern that the string must match.
     *
     * @var string|null
     */
    protected ?string $pattern = null;

    /**
     * The media type of the string content.
     *
     * @var string|null
     */
    protected ?string $contentMediaType = null;

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'string';
    }

    /**
     * {@inheritDoc}
     */
    public function getFormat(): ?string
    {
        return $this->format;
    }

    /**
     * {@inheritDoc}
     */
    public function setFormat(string $format): static
    {
        $this->format = $format;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    /**
     * {@inheritDoc}
     */
    public function setMaxLength(int $maxLength): static
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getMinLength(): ?int
    {
        return $this->minLength;
    }

    /**
     * {@inheritDoc}
     */
    public function setMinLength(int $minLength): static
    {
        $this->minLength = $minLength;
        return $this;
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
    public function setPattern(string $pattern): static
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getContentMediaType(): ?string
    {
        return $this->contentMediaType;
    }

    /**
     * {@inheritDoc}
     */
    public function setContentMediaType(string $contentMediaType): static
    {
        $this->contentMediaType = $contentMediaType;
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

        if ($this->format !== null) {
            $array['format'] = $this->format;
        }

        if ($this->maxLength !== null) {
            $array['maxLength'] = $this->maxLength;
        }

        if ($this->minLength !== null) {
            $array['minLength'] = $this->minLength;
        }

        if ($this->pattern !== null) {
            $array['pattern'] = $this->pattern;
        }

        if ($this->contentMediaType !== null) {
            $array['contentMediaType'] = $this->contentMediaType;
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

        // Set string-specific properties.
        if ($def->format !== null) {
            $schema->setFormat($def->format);
        }

        if ($def->maxLength !== null) {
            $schema->setMaxLength($def->maxLength);
        }

        if ($def->minLength !== null) {
            $schema->setMinLength($def->minLength);
        }

        if ($def->pattern !== null) {
            $schema->setPattern($def->pattern);
        }

        if ($def->contentMediaType !== null) {
            $schema->setContentMediaType($def->contentMediaType);
        }

        return $schema;
    }
}
