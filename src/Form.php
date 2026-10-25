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

use Derafu\Array\ArrayReader;
use Derafu\Form\Contract\Data\FormDataInterface;
use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Options\FormOptionsInterface;
use Derafu\Form\Contract\Rules\FormRulesInterface;
use Derafu\Form\Contract\Schema\FormSchemaInterface;
use Derafu\Form\Contract\Schema\ObjectSchemaInterface;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use Derafu\Form\Contract\UiSchema\ElementsAwareInterface;
use Derafu\Form\Contract\UiSchema\FormUiSchemaInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Contract\Widget\WidgetFactoryInterface;
use Derafu\Form\Data\FormData;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Widget\WidgetFactory;
use Derafu\Support\JsonSerializer;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

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
     * The factory of the widgets of the fields.
     */
    private WidgetFactoryInterface $widgetFactory;

    /**
     * Creates a new Form.
     *
     * @param FormSchemaInterface $schema
     * @param FormUiSchemaInterface $uischema
     * @param FormRulesInterface $rules Explicit processing rules.
     * @param FormDataInterface|null $data
     * @param FormOptionsInterface|null $options
     * @param array<string, array<string>>|null $errors Optional errors for each field (by name)
     * @param WidgetFactoryInterface|null $widgetFactory Optional widget factory.
     * @param array<string>|null $formErrors Optional errors of the form as a whole.
     */
    public function __construct(
        private readonly FormSchemaInterface $schema,
        private readonly FormUiSchemaInterface $uischema,
        private readonly FormRulesInterface $rules = new FormRules(),
        private readonly ?FormDataInterface $data = null,
        private readonly ?FormOptionsInterface $options = null,
        private readonly ?array $errors = null,
        ?WidgetFactoryInterface $widgetFactory = null,
        private readonly ?array $formErrors = null,
    ) {
        $this->widgetFactory = $widgetFactory ?? new WidgetFactory();
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
    public function getRules(): FormRulesInterface
    {
        return $this->rules;
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
                    $field->setRequired($this->isRequiredProperty($name));
                    $field->setNativeRequired(
                        $this->optionBool('native_required') ?? !$this->isNotValidatedByBrowser()
                    );
                    $field->setRequiredAsteriskHidden($this->optionBool('hide_required_asterisk') ?? false);
                    $field->setWidget($this->widgetFactory->create($field));

                    // Initialize field data: explicit form data takes precedence
                    // (even a `null`, that is a field that was left empty), the
                    // schema default is the fallback for empty/new forms.
                    $fieldValue = $this->data !== null && $this->data->has($name)
                        ? $this->data->get($name)
                        : $property->getDefault()
                    ;
                    if ($fieldValue !== null) {
                        $field->setData($fieldValue);
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
     * A boolean option of the form, or `null` if it does not have it.
     *
     * @throws InvalidArgumentException If the option is not a boolean.
     */
    private function optionBool(string $key): ?bool
    {
        return ArrayReader::fromArray($this->options?->toArray() ?? [])->getBool($key);
    }

    /**
     * Whether the form says that the browser does not validate it (`novalidate`).
     */
    private function isNotValidatedByBrowser(): bool
    {
        return $this->options?->getAttributes()->getNovalidate() ?? false;
    }

    /**
     * Whether the property is in the `required` list of the object that has it.
     *
     * @param string $name The name of the property, with dots for the ones that
     * are nested.
     */
    private function isRequiredProperty(string $name): bool
    {
        $path = explode('.', $name);
        $last = array_pop($path);

        $parent = $path === []
            ? $this->getSchema()
            : $this->getSchema()->getProperty(implode('.', $path))
        ;

        return $parent instanceof ObjectSchemaInterface
            && in_array($last, $parent->getRequired(), true);
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
    public function getErrors(): array
    {
        return $this->formErrors ?? [];
    }

    /**
     * {@inheritDoc}
     */
    public function isCsrfProtected(): bool
    {
        return (bool) ($this->options?->get('csrf_protection', true) ?? true);
    }

    /**
     * {@inheritDoc}
     */
    public function getId(): string
    {
        $name = $this->schema->getName();

        return $name !== '' ? $name : 'form';
    }

    /**
     * {@inheritDoc}
     */
    public function getCsrfTokenId(): string
    {
        return $this->getId();
    }

    /**
     * {@inheritDoc}
     */
    public function isCaptchaProtected(): bool
    {
        return (bool) ($this->options?->get('captcha_protection', false) ?? false);
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, array<string>>|null $errors
     */
    public function withData(
        FormDataInterface $data,
        ?array $errors = null,
        ?array $formErrors = null
    ): static {
        return new static(
            $this->schema,
            $this->uischema,
            $this->rules,
            $data,
            $this->options,
            $errors,
            null,
            $formErrors
        );
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'schema'   => $this->schema->toArray(),
            'uischema' => $this->uischema->toArray(),
            'rules'    => $this->getRules()->toArray(),
            'data'     => $this->data?->toArray(),
            'options'  => $this->options?->toArray(),
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $array = [
            'schema'   => $this->schema,
            'uischema' => $this->uischema,
        ];

        $rules = $this->getRules();
        if (!empty($rules->toArray())) {
            $array['rules'] = $rules;
        }

        $array['data']    = $this->data;
        $array['options'] = $this->options;

        return $array;
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
     *
     * @return array<string, mixed>
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
     *
     * @param array<string, mixed> $definition
     * @throws InvalidArgumentException If a key of the first level has a wrong
     * type.
     */
    public static function fromArray(array $definition): static
    {
        $form = FormDefinition::fromArray($definition);

        return new static(
            FormSchema::fromArray($form->schema),
            FormUiSchemaFactory::create($form->uischema),
            FormRules::fromArray($form->rules),
            $form->data !== null ? FormData::fromArray($form->data) : null,
            FormOptions::fromArray($form->options),
            $form->errors
        );
    }
}
