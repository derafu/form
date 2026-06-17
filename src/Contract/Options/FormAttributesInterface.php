<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Options;

use JsonSerializable;

/**
 * Represents the attributes of an HTML form.
 *
 * This interface defines the methods for getting and setting the attributes of
 * an HTML form.
 */
interface FormAttributesInterface extends JsonSerializable
{
    /**
     * Get the character encoding for the form.
     *
     * @return string The character encoding.
     */
    public function getAcceptCharset(): string;

    /**
     * Set the character encoding for the form.
     *
     * @param string $acceptCharset The character encoding to set.
     * @return static The current instance.
     */
    public function setAcceptCharset(string $acceptCharset): static;

    /**
     * Get the URL of the form.
     *
     * @return string|null The URL of the form.
     */
    public function getAction(): ?string;

    /**
     * Set the URL of the form.
     *
     * @param string $action The URL to set.
     * @return static The current instance.
     */
    public function setAction(string $action): static;

    /**
     * Get whether the form should be autocomplete.
     *
     * @return bool Whether the form should be autocomplete.
     */
    public function getAutocomplete(): bool;

    /**
     * Set whether the form should be autocomplete.
     *
     * @param bool $autocomplete Whether the form should be autocomplete.
     * @return static The current instance.
     */
    public function setAutocomplete(bool $autocomplete = true): static;

    /**
     * Get the MIME type of the form data.
     *
     * @return string|null The MIME type of the form data.
     */
    public function getEnctype(): ?string;

    /**
     * Set the MIME type of the form data.
     *
     * @param string $enctype The MIME type to set.
     * @return static The current instance.
     */
    public function setEnctype(string $enctype): static;

    /**
     * Get the ID of the form.
     *
     * @return string The ID of the form.
     */
    public function getId(): string;

    /**
     * Set the ID of the form.
     *
     * @param string $id The ID to set.
     * @return static The current instance.
     */
    public function setId(string $id): static;

    /**
     * Get the HTTP method to use for the form.
     *
     * @return string The HTTP method.
     */
    public function getMethod(): string;

    /**
     * Set the HTTP method to use for the form.
     *
     * @param string $method The HTTP method to set.
     * @return static The current instance.
     */
    public function setMethod(string $method): static;

    /**
     * Get the name of the form.
     *
     * @return string|null The name of the form.
     */
    public function getName(): ?string;

    /**
     * Set the name of the form.
     *
     * @param string $name The name to set.
     * @return static The current instance.
     */
    public function setName(string $name): static;

    /**
     * Get whether the form should not be validated.
     *
     * @return bool Whether the form should not be validated.
     */
    public function getNovalidate(): bool;

    /**
     * Set whether the form should not be validated.
     *
     * @param bool $novalidate Whether the form should not be validated.
     * @return static The current instance.
     */
    public function setNovalidate(bool $novalidate = true): static;

    /**
     * Get the relationship between the document and the form.
     *
     * @return string|null The relationship.
     */
    public function getRel(): ?string;

    /**
     * Set the relationship between the document and the form.
     *
     * @param string $rel The relationship to set.
     * @return static The current instance.
     */
    public function setRel(string $rel): static;

    /**
     * Get the target frame for the form.
     *
     * @return string The target frame.
     */
    public function getTarget(): string;

    /**
     * Set the target frame for the form.
     *
     * @param string $target The target frame to set.
     * @return static The current instance.
     */
    public function setTarget(string $target): static;

    /**
     * Converts the object to an associative array.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Converts the Attributes to a JSON representation.
     *
     * @return string
     */
    public function toJson(): string;

    /**
     * Creates a FormAttributes instance from an associative array.
     *
     * @param array $attributes Associative array of form attributes.
     * @return static
     */
    public static function fromArray(array $attributes): static;
}
