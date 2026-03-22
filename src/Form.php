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

use Derafu\Form\Contract\Data\FormDataInterface;
use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Options\FormOptionsInterface;
use Derafu\Form\Contract\Schema\FormSchemaInterface;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use Derafu\Form\Contract\UiSchema\ElementsAwareInterface;
use Derafu\Form\Contract\UiSchema\FormUiSchemaInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Contract\Widget\WidgetFactoryInterface;
use Derafu\Form\Data\FormData;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Widget\WidgetFactory;
use Derafu\Support\JsonSerializer;

/**
 * Represents a form defined using a declarative approach.
 *
 * A form consists of three main components:
 *
 *   - Schema: Defines the data structure and validation rules.
 *   - UiSchema: Defines how the form should be rendered.
 *   - Data: The actual data values for the form fields.
 *   - Options: Additional options for the form.
 */
final class Form implements FormInterface
{
    /**
     * Fields of the form.
     *
     * @var array<string,FormFieldInterface>
     */
    private array $fields;

    /**
     * Creates a new Form.
     *
     * @param FormSchemaInterface $schema
     * @param FormUiSchemaInterface $uischema
     * @param FormDataInterface|null $data
     * @param FormOptionsInterface|null $options
     * @param array<string, array>|null $errors Optional errors for each field (by name)
     * @param WidgetFactoryInterface|null $widgetFactory Optional widget factory.
     */
    public function __construct(
        private readonly FormSchemaInterface $schema,
        private readonly FormUiSchemaInterface $uischema,
        private readonly ?FormDataInterface $data = null,
        private readonly ?FormOptionsInterface $options = null,
        private readonly ?array $errors = null,
        private ?WidgetFactoryInterface $widgetFactory = null,
    ) {
        if ($this->widgetFactory === null) {
            $this->widgetFactory = new WidgetFactory();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getSchema(): FormSchemaInterface
    {
        return $this->schema;
    }

    /**
     * {@inheritDoc}
     */
    public function getUiSchema(): FormUiSchemaInterface
    {
        return $this->uischema;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): ?FormDataInterface
    {
        return $this->data;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(): ?FormOptionsInterface
    {
        return $this->options;
    }

    /**
     * {@inheritDoc}
     */
    public function getFields(): array
    {
        if (!isset($this->fields)) {
            $this->fields = [];
            $this->collectFieldsFromElements(
                $this->getUiSchema()->getElements(),
                $this->fields
            );
        }

        return $this->fields;
    }

    /**
     * Recursively collects fields from UI elements.
     *
     * @param array<UiSchemaElementInterface> $elements The UI elements to process.
     * @param array<string,FormFieldInterface> &$fields The collected fields.
     * @return void
     */
    private function collectFieldsFromElements(array $elements, array &$fields): void
    {
        foreach ($elements as $element) {
            // If it's a control, create a field.
            if ($element instanceof ControlInterface) {
                $name = $element->getPropertyName();
                $property = $this->getSchema()->getProperty($name);
                if ($property) {
                    // Create the field and set the widget.
                    $field = new FormField($property, $element);
                    $field->setWidget($this->widgetFactory->create($field));

                    // Initialize field data from form data if available
                    if ($this->data !== null) {
                        $fieldValue = $this->data->get($name);
                        if ($fieldValue !== null) {
                            $field->setData($fieldValue);
                        }
                    }

                    // Initialize field errors from errors array if available
                    if ($this->errors !== null && array_key_exists($name, $this->errors)) {
                        $field->setErrors($this->errors[$name]);
                    }

                    $fields[$name] = $field;
                }
            }

            // If it's a layout element with child elements (VerticalLayout,
            // HorizontalLayout, Group, etc.)
            if ($element instanceof ElementsAwareInterface) {
                $this->collectFieldsFromElements(
                    $element->getElements(),
                    $fields
                );
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getField(string $name): ?FormFieldInterface
    {
        $fields = $this->getFields();

        return $fields[$name] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function withData(FormDataInterface $data, ?array $errors = null): static
    {
        return new static($this->schema, $this->uischema, $data, $this->options, $errors);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return [
            'schema' => $this->schema->toArray(),
            'uischema' => $this->uischema->toArray(),
            'data' => $this->data?->toArray(),
            'options' => $this->options?->toArray(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            'schema' => $this->schema,
            'uischema' => $this->uischema,
            'data' => $this->data,
            'options' => $this->options,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function toJson(): string
    {
        return JsonSerializer::serialize($this->jsonSerialize());
    }

    /**
     * {@inheritDoc}
     */
    public function toJsonFormDefinition(): array
    {
        $array = $this->toArray();

        return [
            'schema' => $array['schema'],
            'uischema' => $array['uischema'],
            'data' => $array['data'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public static function fromArray(array $definition): static
    {
        $schema = FormSchema::fromArray($definition['schema'] ?? []);
        $uischema = FormUiSchemaFactory::create($definition['uischema'] ?? []);
        $data = isset($definition['data']) ? FormData::fromArray($definition['data']) : null;
        $options = FormOptions::fromArray($definition['options'] ?? []);
        $errors = $definition['errors'] ?? null;

        return new static($schema, $uischema, $data, $options, $errors);
    }
}
