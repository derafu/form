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

        // Set string-specific properties.
        if (isset($definition['format'])) {
            $schema->setFormat($definition['format']);
        }

        if (isset($definition['maxLength'])) {
            $schema->setMaxLength($definition['maxLength']);
        }

        if (isset($definition['minLength'])) {
            $schema->setMinLength($definition['minLength']);
        }

        if (isset($definition['pattern'])) {
            $schema->setPattern($definition['pattern']);
        }

        if (isset($definition['contentMediaType'])) {
            $schema->setContentMediaType($definition['contentMediaType']);
        }

        return $schema;
    }
}
