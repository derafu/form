<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Abstract;

use Derafu\Form\Contract\Type\TypeInterface;

/**
 * Base implementation of TypeInterface.
 *
 * This class provides common functionality for all type implementations.
 */
abstract class AbstractType implements TypeInterface
{
    /**
     * The options of the type.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * The pattern of the type.
     *
     * @var string|null
     */
    public const PATTERN = null;

    /**
     * Constructor.
     *
     * @param array $options The options of the type.
     */
    public function __construct(array $options = [])
    {
        $this->options = array_replace_recursive(
            $this->getDefaultOptions(),
            $options
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getRegex(): ?string
    {
        if (static::PATTERN === null) {
            return null;
        }

        return '/' . str_replace('/', '\/', static::PATTERN) . '/';
    }

    /**
     * {@inheritDoc}
     */
    public function validateValue(mixed $value): bool
    {
        $regex = $this->getRegex();
        if ($regex !== null) {
            if (!preg_match($regex, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function castValue(mixed $value): mixed
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getJsonSchema(): array
    {
        $schema = [
            'type' => 'string',
        ];

        if (static::PATTERN !== null) {
            $schema['pattern'] = static::PATTERN;
        }

        return $schema;
    }

    /**
     * {@inheritDoc}
     */
    public function isGuessable(): bool
    {
        return false;
    }

    /**
     * Get the default options for the type.
     *
     * @return array The default options.
     */
    protected function getDefaultOptions(): array
    {
        return [];
    }
}
