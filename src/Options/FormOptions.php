<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Options;

use ArrayObject;
use Derafu\Form\Contract\Options\FormAttributesInterface;
use Derafu\Form\Contract\Options\FormOptionsInterface;
use Derafu\Support\JsonSerializer;

/**
 * Implementation of the OptionsInterface that represents a form options.
 *
 * @extends ArrayObject<string, mixed>
 */
final class FormOptions extends ArrayObject implements FormOptionsInterface
{
    /**
     * Form attributes.
     *
     * @var FormAttributesInterface
     */
    private FormAttributesInterface $attributes;

    /**
     * {@inheritDoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $parts = $this->parsePath($key);
        $current = $this->toArray();

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }

        return $current;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value): static
    {
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttributes(): FormAttributesInterface
    {
        if (!isset($this->attributes)) {
            /** @var array<string, mixed> $attributes */
            $attributes = $this->get('attributes', []);
            $this->attributes = FormAttributes::fromArray($attributes);
        }

        return $this->attributes;
    }

    /**
     * {@inheritDoc}
     */
    public function setAttributes(FormAttributesInterface $attributes): static
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function toJson(): string
    {
        return JsonSerializer::serialize($this->jsonSerialize());
    }

    /**
     * Handle dot notation.
     *
     * @param string $path
     * @return list<string>
     */
    private function parsePath(string $path): array
    {
        if (str_contains($path, '.')) {
            return explode('.', $path);
        }

        return [$path];
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new static($data);
    }
}
