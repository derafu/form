<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form;

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\Schema\PropertySchemaInterface;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use Derafu\Form\Contract\Widget\WidgetInterface;
use Derafu\Support\JsonSerializer;

/**
 * Implementation of the FormFieldInterface.
 *
 * This class represents a field within a form, connecting a schema property
 * with its UI representation.
 */
final class FormField implements FormFieldInterface
{
    /**
     * The widget associated with this field.
     *
     * @var WidgetInterface
     */
    private WidgetInterface $widget;

    /**
     * The name pattern for the field.
     *
     * @var string
     */
    private string $namePattern = '%s';

    /**
     * Whether the field is required.
     *
     * @var bool
     */
    private bool $required = false;

    /**
     * Whether the field has been rendered.
     *
     * @var bool
     */
    private bool $rendered = false;

    /**
     * The data for the field.
     *
     * @var mixed
     */
    private mixed $data = null;

    /**
     * The errors for the field.
     *
     * @var array
     */
    private array $errors = [];

    /**
     * Constructs a new form field.
     *
     * @param PropertySchemaInterface $property The property associated with
     * this field, defining its data structure and validation rules.
     * @param ControlInterface $control The control associated with this field,
     * defining how it should be visually represented.
     */
    public function __construct(
        private readonly PropertySchemaInterface $property,
        private readonly ControlInterface $control
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getProperty(): PropertySchemaInterface
    {
        return $this->property;
    }

    /**
     * {@inheritDoc}
     */
    public function getControl(): ControlInterface
    {
        return $this->control;
    }

    /**
     * {@inheritDoc}
     */
    public function getWidget(): WidgetInterface
    {
        return $this->widget;
    }

    /**
     * {@inheritDoc}
     */
    public function setWidget(WidgetInterface $widget): static
    {
        $this->widget = $widget;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return sprintf($this->namePattern, $this->property->getName());
    }

    /**
     * {@inheritDoc}
     */
    public function setNamePattern(string $pattern): static
    {
        $this->namePattern = $pattern;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * {@inheritDoc}
     */
    public function setRequired(bool $required = true): static
    {
        $this->required = $required;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isRendered(): bool
    {
        return $this->rendered;
    }

    /**
     * {@inheritDoc}
     */
    public function setRendered(bool $rendered = true): static
    {
        $this->rendered = $rendered;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function setData(mixed $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * {@inheritDoc}
     */
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'property' => $this->property->toArray(),
            'control' => $this->control->toArray(),
            'required' => $this->required,
            'rendered' => $this->rendered,
            'data' => $this->data,
            'errors' => $this->errors,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'property' => $this->property,
            'control' => $this->control,
            'required' => $this->required,
            'rendered' => $this->rendered,
            'data' => $this->data,
            'errors' => $this->errors,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function toJson(): string
    {
        return JsonSerializer::serialize($this->jsonSerialize());
    }
}
