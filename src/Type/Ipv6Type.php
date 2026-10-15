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
 * Represents an IPv6 type in the form.
 *
 * An IPv6 type allows users to enter an IPv6 address.
 */
final class Ipv6Type extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'ipv6';
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
            'format' => 'ipv6',

            // RFC 4291.
            'maxLength' => 39,
            'minLength' => 2,
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

        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * {@inheritDoc}
     */
    public function isGuessable(): bool
    {
        return true;
    }
}
