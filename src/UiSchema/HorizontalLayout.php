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

use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Contract\UiSchema\HorizontalLayoutInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Support\ArrayReader;
use Derafu\Support\ArrayType;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Represents a horizontal layout in a UI Schema.
 *
 * HorizontalLayout elements are used to arrange UI elements in a horizontal row.
 */
final class HorizontalLayout extends AbstractUiSchemaElement implements HorizontalLayoutInterface
{
    /**
     * Elements of the horizontal layout.
     *
     * @var array<UiSchemaElementInterface>
     */
    private array $elements;

    /**
     * Constructor.
     *
     * @param array<UiSchemaElementInterface> $elements
     * @param array<string, mixed> $options The options of the layout.
     */
    public function __construct(array $elements = [], array $options = [])
    {
        parent::__construct($options ? ['options' => $options] : []);

        $this->elements = $elements;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'HorizontalLayout';
    }

    /**
     * {@inheritDoc}
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * {@inheritDoc}
     */
    public function addElement(UiSchemaElementInterface $element): static
    {
        $this->elements[] = $element;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [
            'type' => $this->getType(),
            'elements' => array_map(
                fn (UiSchemaElementInterface $element) => $element->toArray(),
                $this->elements
            ),
        ];

        $options = $this->getOptions();
        if ($options) {
            $array['options'] = $options;
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $definition
     * @throws InvalidArgumentException If a key is missing or has a wrong type.
     */
    public static function fromArray(array $definition): static
    {
        $reader = ArrayReader::from($definition);

        $elements = [];
        foreach ($reader->require('elements', ArrayType::listOf(ArrayType::map())) as $element) {
            $elements[] = UiSchemaElementFactory::create($element);
        }

        return new static($elements, $reader->map('options') ?? []);
    }
}
