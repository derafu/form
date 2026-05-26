<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Contract\Type;

/**
 * Represents a type in the form.
 *
 * A type defines the properties and behavior of a form field.
 */
interface TypeInterface
{
    /**
     * Gets the name of the type.
     *
     * @return string The name of the type.
     */
    public function getName(): string;

    /**
     * Gets the options of the type.
     *
     * @return array The options of the type.
     */
    public function getOptions(): array;

    /**
     * Get an option from the type.
     *
     * @param string $key The key of the option.
     * @param mixed $default The default value if the option is not set.
     * @return mixed The value of the option.
     */
    public function getOption(string $key, mixed $default = null): mixed;

    /**
     * Sets the options of the type.
     *
     * @param array $options The options of the type.
     * @return static The current instance.
     */
    public function setOptions(array $options): static;

    /**
     * Checks if the value is valid for the type.
     *
     * @param mixed $value The value to check.
     * @return bool True if the value is valid, false otherwise.
     */
    public function validateValue(mixed $value): bool;

    /**
     * Casts a value to the type.
     *
     * @param mixed $value The value to cast.
     * @return mixed The casted value.
     */
    public function castValue(mixed $value): mixed;

    /**
     * Get the JSON schema for the type.
     *
     * @return array The JSON schema.
     */
    public function getJsonSchema(): array;

    /**
     * Returns the PCRE-ready regex for this type's pattern.
     *
     * The `PATTERN` constant is stored in ECMA format (no delimiters), which
     * is the canonical format used by JSON Schema and HTML `pattern=""`. This
     * method wraps it with `/` delimiters and escapes any literal `/` inside
     * the pattern so it can be passed directly to PHP's `preg_match()`.
     *
     * Returns null when the type has no pattern (i.e., `PATTERN === null`).
     *
     * @return string|null The PCRE pattern, or null if no pattern is defined.
     */
    public function getRegex(): ?string;

    /**
     * Checks if the type is guessable.
     *
     * @return bool True if the type is guessable, false otherwise.
     */
    public function isGuessable(): bool;
}
