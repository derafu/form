<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Schema;

/**
 * Represents a string property within a Schema.
 */
interface StringSchemaInterface extends PropertySchemaInterface
{
    /**
     * Gets the semantic information about a string property.
     *
     * @return string|null The format or `null` if not defined.
     * @link https://www.learnjsonschema.com/2020-12/format-annotation/format/
     */
    public function getFormat(): ?string;

    /**
     * Sets the format of the property.
     *
     * @param string $format
     * @return static The current instance.
     */
    public function setFormat(string $format): static;

    /**
     * Gets the maximum length of a string.
     *
     * @return integer|null
     * @link https://www.learnjsonschema.com/2020-12/validation/maxlength/
     */
    public function getMaxLength(): ?int;

    /**
     * Sets the maximum length of a string.
     *
     * @param int $maxLength
     * @return static The current instance.
     */
    public function setMaxLength(int $maxLength): static;

    /**
     * Gets the minimum length of a string.
     *
     * @return integer|null
     * @link https://www.learnjsonschema.com/2020-12/validation/minlength/
     */
    public function getMinLength(): ?int;

    /**
     * Sets the minimum length of a string.
     *
     * @param int $minLength
     * @return static The current instance.
     */
    public function setMinLength(int $minLength): static;

    /**
     * Gets the pattern that a string must match.
     *
     * @return string|null
     * @link https://www.learnjsonschema.com/2020-12/validation/pattern/
     */
    public function getPattern(): ?string;

    /**
     * Sets the pattern that a string must match.
     *
     * @param string $pattern
     * @return static The current instance.
     */
    public function setPattern(string $pattern): static;

    /**
     * Gets the media type of the string content.
     *
     * @return string|null
     * @link https://www.learnjsonschema.com/2020-12/content/contentmediatype/
     */
    public function getContentMediaType(): ?string;

    /**
     * Sets the media type of the string content.
     *
     * @param string $contentMediaType
     * @return static The current instance.
     */
    public function setContentMediaType(string $contentMediaType): static;
}
