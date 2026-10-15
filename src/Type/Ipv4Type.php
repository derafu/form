<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Type;

use Derafu\Form\Abstract\AbstractType;

/**
 * Represents an IPv4 type in the form.
 *
 * An IPv4 type allows users to enter an IPv4 address.
 */
final class Ipv4Type extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'ipv4';
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function getJsonSchema(): array
    {
        return [
            'type' => 'string',
            'format' => 'ipv4',

            // "255.255.255.255" (15), "0.0.0.0" (7).
            'maxLength' => 15,
            'minLength' => 7,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function validateValue(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * {@inheritDoc}
     */
    public function isGuessable(): bool
    {
        return true;
    }
}
