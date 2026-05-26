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
use Derafu\Form\Contract\UiSchema\CategoryInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaRuleInterface;
use Derafu\Form\Factory\UiSchemaElementFactory;

/**
 * Represents a category in a UI Schema.
 *
 * Categories are used to organize UI elements into a logical collection.
 */
final class Category extends AbstractUiSchemaElement implements CategoryInterface
{
    /**
     * The label text.
     *
     * @var string
     */
    private string $label;

    /**
     * The elements of the category.
     *
     * @var array<UiSchemaElementInterface>
     */
    private array $elements;

    /**
     * The rule for the category.
     *
     * @var UiSchemaRuleInterface|null
     */
    private ?UiSchemaRuleInterface $rule;

    /**
     * Constructor.
     *
     * @param string $label The label text.
     * @param array<UiSchemaElementInterface> $elements The elements of the category.
     * @param UiSchemaRuleInterface|null $rule The rule for the category.
     */
    public function __construct(string $label, array $elements = [], ?UiSchemaRuleInterface $rule = null)
    {
        parent::__construct([]);

        $this->label = $label;
        $this->elements = $elements;
        $this->rule = $rule;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(): string
    {
        return 'Category';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return $this->label;
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
    public function getRule(): ?UiSchemaRuleInterface
    {
        return $this->rule;
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
     */
    public function toArray(): array
    {
        $array = [
            'type' => $this->getType(),
            'label' => $this->label,
            'elements' => array_map(
                fn (UiSchemaElementInterface $element) => $element->toArray(),
                $this->elements
            ),
        ];

        if ($this->rule !== null) {
            $array['rule'] = $this->rule->toArray();
        }

        return $array;
    }

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $definition): static
    {
        $elements = [];
        foreach ($definition['elements'] as $element) {
            $elements[] = UiSchemaElementFactory::create($element);
        }

        return new static(
            $definition['label'],
            $elements,
            isset($definition['rule']) ? UiSchemaRule::fromArray($definition['rule']) : null,
        );
    }
}
