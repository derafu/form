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

use Derafu\Form\Contract\Data\FormDataInterface;
use Derafu\Support\JsonSerializer;

/**
 * Represents the data for a form.
 *
 * Contains the actual values for the form fields, which can be used to populate
 * the form or extracted after submission.
 */
final class FormData implements FormDataInterface
{
    /**
     * Creates a new Data.
     *
     * @param array<string, mixed> $data
     */
    public function __construct(private array $data)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $propertyPath): bool
    {
        $parts = $this->parsePath($propertyPath);
        $current = $this->data;

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return false;
            }
            $current = $current[$part];
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $propertyPath, mixed $default = null): mixed
    {
        $parts = $this->parsePath($propertyPath);
        $current = $this->data;

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
    public function set(string $propertyPath, mixed $value): static
    {
        $parts = $this->parsePath($propertyPath);
        $current = &$this->data;

        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                $current[$part] = [];
            }
            /** @var array<string, mixed> $current */
            $current = &$current[$part];
        }

        $current = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
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
     * Parse property path from various formats to array segments.
     *
     * @param string $path Path in dot notation or JSON Forms scope format.
     * @return list<string> Path segments
     */
    private function parsePath(string $path): array
    {
        // Check if this is a JSON Forms scope.
        if (str_starts_with($path, '#/properties/')) {
            // Remove the prefix.
            $path = substr($path, 13);

            // Split by "/properties/" and flatten.
            return explode('/properties/', $path);
        }

        // Handle dot notation.
        if (str_contains($path, '.')) {
            return explode('.', $path);
        }

        // Simple property name.
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
