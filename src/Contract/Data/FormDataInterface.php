<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Data;

use JsonSerializable;

/**
 * Represents the data for a form.
 *
 * Contains the actual values for the form fields, which can be used to populate
 * the form or extracted after submission.
 */
interface FormDataInterface extends JsonSerializable
{
    /**
     * Checks if a property exists in the data.
     *
     * @param string $propertyPath The property path to check (supports dot notation).
     * @return bool True if the property exists, false otherwise.
     */
    public function has(string $propertyPath): bool;

    /**
     * Gets a property value from the data.
     *
     * @param string $propertyPath The property path (supports dot notation).
     * @param mixed $default The default value to return if property doesn't exist.
     * @return mixed The property value or the default value.
     */
    public function get(string $propertyPath, mixed $default = null): mixed;

    /**
     * Sets a property value in the data.
     *
     * @param string $propertyPath The property path (supports dot notation).
     * @param mixed $value The value to set.
     * @return static The current instance.
     */
    public function set(string $propertyPath, mixed $value): static;

    /**
     * Gets all data as an associative array.
     *
     * @return array<string, mixed> All form data.
     */
    public function all(): array;

    /**
     * Converts the data to an array representation.
     *
     * @return array<string, mixed> The complete data as an array.
     */
    public function toArray(): array;

    /**
     * Converts the Data to a JSON representation.
     *
     * @return string The JSON representation of the data.
     */
    public function toJson(): string;

    /**
     * Create a Data instance from an array.
     *
     * @param array<string, mixed> $data The data values as an array.
     * @return static The data instance.
     */
    public static function fromArray(array $data): static;
}
