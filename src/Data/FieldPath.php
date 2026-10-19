<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Data;

use Derafu\Support\Arr;

/**
 * The value of a field in the data of a form, by the name of the field.
 *
 * The fields of an object of the schema have the names of the path to them, with
 * dots (`address.city`), and their values are in the data as the object is
 * (`['address' => ['city' => 'Lima']]`).
 */
final class FieldPath
{
    /**
     * Gets the value of the field, or `null` if the data does not have it.
     *
     * A key of the data with the whole name (`'address.city'`) is the value of
     * that field too.
     *
     * @param array<array-key, mixed> $data The data.
     * @param string $name The name of the field.
     */
    public static function get(array $data, string $name): mixed
    {
        if (array_key_exists($name, $data)) {
            return $data[$name];
        }

        return Arr::getAtPath($data, $name);
    }

    /**
     * Sets the value of the field, creating the levels of its path.
     *
     * @param array<array-key, mixed> $data The data.
     * @param string $name The name of the field.
     * @return array<array-key, mixed> The data with the value.
     */
    public static function set(array $data, string $name, mixed $value): array
    {
        return Arr::setAtPath($data, $name, $value);
    }
}
