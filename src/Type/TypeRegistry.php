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

use Derafu\Form\Contract\Type\TypeInterface;
use Derafu\Form\Contract\Type\TypeProviderInterface;
use Derafu\Form\Contract\Type\TypeRegistryInterface;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Registry of types.
 */
final class TypeRegistry implements TypeRegistryInterface
{
    /**
     * Registry of types.
     *
     * @var array<string, TypeInterface>
     */
    private array $types = [];

    /**
     * List of guessable types.
     *
     * @var array<TypeInterface>
     */
    private array $guessableTypes;

    /**
     * Constructor.
     *
     * @param array<TypeInterface>|TypeProviderInterface $types
     */
    public function __construct(array|TypeProviderInterface $types = [])
    {
        if ($types instanceof TypeProviderInterface) {
            $types = $types->getTypes();
        }

        foreach ($types as $type) {
            if (is_string($type)) {
                $type = new $type();
            }

            $this->register($type);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function register(TypeInterface $type): static
    {
        $this->types[$type->getName()] = $type;
        $this->types[get_class($type)] = $type;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $type): TypeInterface
    {
        if (!$this->has($type)) {
            throw new InvalidArgumentException([
                "Type ''{type}'' not found in registry. Available types: {types}.",
                'type' => $type,
                'types' => implode(', ', array_keys($this->types)),
            ]);
        }

        return $this->types[$type];
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $type): bool
    {
        return isset($this->types[$type]);
    }

    /**
     * {@inheritDoc}
     */
    public function getGuessableTypes(): array
    {
        if (!isset($this->guessableTypes)) {
            $this->guessableTypes = [];
            foreach ($this->types as $type) {
                if ($type->isGuessable() && !in_array($type, $this->guessableTypes)) {
                    $this->guessableTypes[] = $type;
                }
            }
        }

        return $this->guessableTypes;
    }
}
