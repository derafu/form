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

use DateTimeInterface;
use Derafu\Form\Contract\Type\TypeInterface;
use Derafu\Form\Contract\Type\TypeRegistryInterface;
use Derafu\Form\Contract\Type\TypeResolverInterface;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Resolves the type of a value.
 */
final class TypeResolver implements TypeResolverInterface
{
    /**
     * Constructor.
     *
     * @param TypeRegistryInterface $typeRegistry The type registry.
     */
    public function __construct(
        private readonly TypeRegistryInterface $typeRegistry
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function guess(mixed $value): TypeInterface
    {
        // Handle large integer strings that exceed PHP's integer range.
        if (is_string($value) && preg_match('/^-?\d+$/', $value)) {
            $bigNumber = bccomp($value, (string)PHP_INT_MAX, 0) > 0
                || bccomp($value, (string)PHP_INT_MIN, 0) < 0;

            if ($bigNumber) {
                return $this->typeRegistry->get(TextType::class);
            }
            return $this->typeRegistry->get(IntegerType::class);
        }

        // Handle date and time types.
        if ($value instanceof DateTimeInterface) {
            return $this->typeRegistry->get(DatetimeType::class);
        }

        // Handle other types.
        return match (true) {
            is_string($value) => $this->guessStringType($value),
            is_int($value) => $this->typeRegistry->get(IntegerType::class),
            is_float($value) => $this->typeRegistry->get(FloatType::class),
            is_bool($value) => $this->typeRegistry->get(BooleanType::class),
            is_array($value) => $this->typeRegistry->get(ArrayType::class),
            is_object($value) => $this->typeRegistry->get(ObjectType::class),
            default => throw new InvalidArgumentException([
                'Cannot guess type for value of type {type}.',
                'type' => get_debug_type($value),
            ]),
        };
    }

    /**
     * Guess the type of a string value.
     *
     * @param string $value The value to guess the type of.
     * @return TypeInterface The guessed type.
     */
    public function guessStringType(string $value): TypeInterface
    {
        foreach ($this->typeRegistry->getGuessableTypes() as $type) {
            if ($type->validateValue($value)) {
                return $type;
            }
        }

        return $this->typeRegistry->get(TextType::class);
    }
}
