<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract;

use Derafu\Form\Contract\Schema\PropertySchemaInterface;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use JsonSerializable;

/**
 * Represents a field within a form.
 *
 * A form field connects a schema property (data definition) with a UI element
 * (visual representation), creating a complete field that can be rendered and
 * processed.
 */
interface FormFieldInterface extends JsonSerializable
{
    /**
     * Gets the property associated with this field.
     *
     * @return PropertySchemaInterface The property that defines the data
     * structure and validation rules for this field.
     */
    public function getProperty(): PropertySchemaInterface;

    /**
     * Gets the control associated with this field.
     *
     * @return ControlInterface The control that defines how the field should be
     * visually represented.
     */
    public function getControl(): ControlInterface;

    /**
     * Gets the name for the field.
     *
     * @return string The name for the field.
     */
    public function getName(): string;

    /**
     * Sets the name pattern for the field.
     *
     * @param string $pattern The name pattern for the field.
     * @return static The current instance.
     */
    public function setNamePattern(string $pattern): static;

    /**
     * Checks if the field is required.
     *
     * @return bool True if the field is required, false otherwise.
     */
    public function isRequired(): bool;

    /**
     * Sets the required state of the field.
     *
     * @param bool $required True if the field is required, false otherwise.
     * @return static The current instance.
     */
    public function setRequired(bool $required = true): static;

    /**
     * Checks if the field has been rendered.
     *
     * @return bool True if the field has been rendered, false otherwise.
     */
    public function isRendered(): bool;

    /**
     * Sets the rendered state of the field.
     *
     * @param bool $rendered True if the field has been rendered, false otherwise.
     * @return static The current instance.
     */
    public function setRendered(bool $rendered = true): static;

    /**
     * Gets the data for the field.
     *
     * @return mixed The data for the field.
     */
    public function getData(): mixed;

    /**
     * Sets the data for the field.
     *
     * @param mixed $data The data for the field.
     * @return static The current instance.
     */
    public function setData(mixed $data): static;

    /**
     * Gets the errors for the field.
     *
     * @return array The errors for the field.
     */
    public function getErrors(): array;

    /**
     * Sets the errors for the field.
     *
     * @param array $errors The errors for the field.
     * @return static The current instance.
     */
    public function setErrors(array $errors): static;

    /**
     * Checks if the field is valid.
     *
     * @return bool True if the field has no errors, false otherwise.
     */
    public function isValid(): bool;

    /**
     * Converts the FormField to an array representation.
     *
     * @return array The complete form field as an array.
     */
    public function toArray(): array;

    /**
     * Converts the FormField to a JSON representation.
     *
     * @return string
     */
    public function toJson(): string;
}
