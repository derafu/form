<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\UiSchema;

use JsonSerializable;

/**
 * Represents a UI Element within a UI Schema.
 *
 * UI Elements define how specific parts of the form should be rendered, such as
 * controls, groups, or layouts.
 */
interface UiSchemaElementInterface extends JsonSerializable
{
    /**
     * Gets the type of UI element (e.g., "Control", "Group", "VerticalLayout").
     *
     * @return string The element type.
     */
    public function getType(): string;

    /**
     * Gets the HTML id for this element.
     *
     * Returns options['id'] if set, otherwise generates a stable unique string
     * using the element type as prefix. Always returns a non-empty string.
     *
     * @return string The element id.
     */
    public function getId(): string;

    /**
     * Gets the rule that conditionally controls the behavior of this element.
     *
     * A rule defines whether this element is shown, hidden, enabled, or
     * disabled based on the value of another field.
     *
     * @return UiSchemaRuleInterface|null The rule, or null if none is defined.
     */
    public function getRule(): ?UiSchemaRuleInterface;

    /**
     * Gets the options of the UI element.
     *
     * @return array The options of the UI element.
     */
    public function getOptions(): array;

    /**
     * Converts the UI Element to an array representation.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Converts the UI Element to a JSON representation.
     *
     * @return string
     */
    public function toJson(): string;

    /**
     * Create a UiSchemaElement instance from an array.
     *
     * @param array $definition The UI schema element definition as an array.
     * @return static
     */
    public static function fromArray(array $definition): static;
}
