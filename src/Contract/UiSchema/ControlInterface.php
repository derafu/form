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

/**
 * Represents a UI Element within a UI Schema.
 *
 * UI Elements define how specific parts of the form should be rendered, such as
 * controls, groups, or layouts.
 */
interface ControlInterface extends UiSchemaElementInterface
{
    /**
     * Gets the scope of the element, which links it to a schema property.
     *
     * @return string The scope of the element.
     */
    public function getScope(): string;

    /**
     * Gets the path of the property associated with the element.
     *
     * This is the path of the property that is associated with the element with
     * the full path to the property in the schema (extracted from the scope).
     *
     * @return string The path of the property.
     */
    public function getPropertyPath(): string;

    /**
     * Gets the name of the property associated with the element.
     *
     * This is the name of the property that is associated with the element with
     * the full path to the property in the schema (extracted from the scope).
     *
     * @return string The name of the property.
     */
    public function getPropertyName(): string;

    /**
     * Gets the label for the element.
     *
     * @return string|null The label or `null` if not defined.
     */
    public function getLabel(): ?string;

    /**
     * Gets the UI control type override, or null when none is set.
     *
     * The control type is declared via the `type` key inside the control's
     * `options` object (e.g. `{ "options": { "type": "radio" } }`) and
     * overrides the default widget selection that is based on the JSON Schema
     * property type and format.
     *
     * Common values: `"file"`, `"image"`, `"editor"`, `"radio"`,
     * `"choice"`, `"textarea"`, `"slider"`, `"range"`, `"float"`.
     *
     * @return string|null The declared control type, or null when absent.
     */
    public function getControlType(): ?string;
}
