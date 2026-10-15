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

use ArrayAccess;
use JsonSerializable;

/**
 * Represents the options for a form.
 *
 * @extends ArrayAccess<string, mixed>
 */
interface FormOptionsInterface extends ArrayAccess, JsonSerializable
{
    /**
     * Gets the value of an option.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Sets the value of an option.
     *
     * @param string $key
     * @param mixed $value
     * @return static The current instance.
     */
    public function set(string $key, mixed $value): static;

    /**
     * Gets the attributes of the form.
     *
     * @return FormAttributesInterface
     */
    public function getAttributes(): FormAttributesInterface;

    /**
     * Sets the attributes of the form.
     *
     * @param FormAttributesInterface $attributes
     * @return static The current instance.
     */
    public function setAttributes(FormAttributesInterface $attributes): static;

    /**
     * Converts the Options to an array representation.
     *
     * @return array<string, mixed> The complete options as an array.
     */
    public function toArray(): array;

    /**
     * Converts the Options to a JSON representation.
     *
     * @return string
     */
    public function toJson(): string;

    /**
     * Creates a FormOptions instance from an array.
     *
     * @param array<string, mixed> $data The options data as an array.
     * @return static The FormOptions instance.
     */
    public static function fromArray(array $data): static;
}
