<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\UiSchema;

use Derafu\Array\ArrayReader;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Contract\UiSchema\LabelInterface;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;
use Derafu\Value\ValueType;

/**
 * Represents a label in a UI Schema.
 *
 * Labels are used to provide a human-readable name for a form element.
 */
final class Label extends AbstractUiSchemaElement implements LabelInterface
{
    /**
     * The label text.
     *
     * @var string
     */
    private string $text;

    /**
     * Constructor.
     *
     * @param string $text The label text.
     */
    public function __construct(string $text)
    {
        parent::__construct([]);

        $this->text = $text;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'Label';
    }

    /**
     * {@inheritDoc}
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->getType(),
            'label' => $this->text,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->text;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $definition
     * @throws InvalidArgumentException If a key is missing or has a wrong type.
     */
    public static function fromArray(array $definition): static
    {
        return new static(ArrayReader::fromArray($definition)->require('text', ValueType::string()));
    }
}
