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
use Derafu\Form\Contract\UiSchema\CategorizationInterface;
use Derafu\Form\Contract\UiSchema\CategoryInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;

/**
 * Represents a categorization in a UI Schema.
 *
 * Categorizations are used to organize UI elements into a logical collection.
 */
final class Categorization extends AbstractUiSchemaElement implements CategorizationInterface
{
    /**
     * The categories of the categorization.
     *
     * @var array<CategoryInterface>
     */
    private array $categories;

    /**
     * Constructor.
     *
     * @param array<CategoryInterface> $categories The categories of the categorization.
     * @param array<string, mixed> $options The options of the categorization.
     */
    public function __construct(array $categories = [], array $options = [])
    {
        parent::__construct($options ? ['options' => $options] : []);

        $this->categories = $categories;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'Categorization';
    }

    /**
     * {@inheritDoc}
     *
     * @return array<CategoryInterface>
     */
    public function getElements(): array
    {
        return $this->categories;
    }

    /**
     * {@inheritDoc}
     */
    public function addElement(UiSchemaElementInterface $element): static
    {
        assert($element instanceof CategoryInterface);

        $this->categories[] = $element;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * {@inheritDoc}
     */
    public function addCategory(CategoryInterface $category): static
    {
        $this->categories[] = $category;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        $array = [
            'type' => $this->getType(),
            'elements' => array_map(
                fn (CategoryInterface $category) => $category->toArray(),
                $this->categories
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
     */
    public static function fromArray(array $definition): static
    {
        $categories = [];

        foreach ($definition['elements'] as $categoryDefinition) {
            $categories[] = Category::fromArray($categoryDefinition);
        }

        return new static($categories, $definition['options'] ?? []);
    }
}
