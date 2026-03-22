<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer;

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Renderer\ElementRendererRegistryInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Contract\Renderer\WidgetRendererRegistryInterface;
use Derafu\Form\Contract\Schema\ArraySchemaInterface;
use Derafu\Form\Contract\Schema\PropertySchemaInterface;
use Derafu\Form\Contract\Schema\StringSchemaInterface;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Renderer\Contract\RendererInterface;

/**
 * Form renderer implementation.
 */
final class FormRenderer implements FormRendererInterface
{
    public function __construct(
        private readonly RendererInterface $renderer,
        private readonly ElementRendererRegistryInterface $elementRendererRegistry,
        private readonly WidgetRendererRegistryInterface $widgetRendererRegistry
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function render(FormInterface $form, array $options = []): string
    {
        $context = [
            'form' => $form,
            'options' => $options,
        ];

        return $this->renderer->render('form/form', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderStart(
        FormInterface $form,
        array $options = []
    ): string {
        $context = [
            'form' => $form,
            'options' => $options,
        ];

        return $this->renderer->render('form/form_start', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderEnd(
        FormInterface $form,
        array $options = []
    ): string {
        $context = [
            'form' => $form,
            'options' => $options,
        ];

        return $this->renderer->render('form/form_end', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderLabel(
        FormFieldInterface $field,
        ?string $label = null,
        array $options = []
    ): string {
        $context = [
            'field' => $field,
            'label' => $label
                ?? $field->getControl()->getLabel()
                ?? $field->getProperty()->getTitle()
                ?? $field->getProperty()->getName()
            ,
            'options' => $options,
        ];

        return $this->renderer->render('form/label', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderErrors(
        FormFieldInterface $field,
        array $options = []
    ): string {
        $context = [
            'field' => $field,
            'errors' => $field->getErrors(),
            'options' => $options,
        ];

        return $this->renderer->render('form/errors', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderWidget(
        FormFieldInterface $field,
        array $options = []
    ): string {
        $property = $field->getProperty();
        $control = $field->getControl();

        $widgetType = $this->determineWidgetType($property, $control);

        $renderer = $this->widgetRendererRegistry->getRenderer($widgetType);

        $html = $renderer->render($field, array_merge([
            'renderer' => $this,
        ], $options));

        $field->setRendered();

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderHelp(
        FormFieldInterface $field,
        array $options = []
    ): string {
        $context = [
            'field' => $field,
            'help' => $field->getProperty()->getDescription(),
            'options' => $options,
        ];

        return $this->renderer->render('form/help', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderRow(
        FormFieldInterface $field,
        array $options = []
    ): string {
        $options = array_merge([
            'floating_labels' => true,
        ], $options);

        if (
            $options['floating_labels']
            && empty($options['attr']['placeholder'])
        ) {
            $options['attr']['placeholder'] = $field->getControl()->getLabel();
        }

        $context = [
            'field' => $field,
            'label' => $this->renderLabel(
                $field,
                $options['label'] ?? null,
                $options
            ),
            'errors' => $this->renderErrors($field, $options),
            'widget' => $this->renderWidget($field, $options),
            'help' => $this->renderHelp($field, $options),
            'options' => $options,
        ];

        return $this->renderer->render('form/row', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function renderBody(
        FormInterface $form,
        array $options = []
    ): string {
        $html = $this->renderElement($form->getUiSchema(), $form, $options);

        $csrfProtection = $options['csrf_protection'] ?? true;
        if ($csrfProtection) {
            $html .= $this->renderCsrf($form);
        }

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderRest(
        FormInterface $form,
        array $options = []
    ): string {
        $fields = $form->getFields();

        $html = '';
        foreach ($fields as $field) {
            if ($field->isRendered()) {
                continue;
            }
            $html .= $this->renderElement($field->getControl(), $form, $options);
        }

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderEnctype(FormInterface $form): string
    {
        $fields = $form->getFields();

        foreach ($fields as $field) {
            if (($field->getControl()->getOptions()['type'] ?? null) === 'file') {
                return 'multipart/form-data';
            }
        }

        return 'application/x-www-form-urlencoded';
    }

    /**
     * {@inheritDoc}
     */
    public function renderElement(
        UiSchemaElementInterface $element,
        FormInterface $form,
        array $options = []
    ): string {
        $renderer = $this->elementRendererRegistry->getRenderer(
            $element->getType()
        );

        return $renderer->render($element, $form, array_merge([
            'renderer' => $this,
        ], $options));
    }

    /**
     * {@inheritDoc}
     */
    public function renderElements(
        array $elements,
        FormInterface $form,
        array $options = []
    ): array {
        $html = [];
        foreach ($elements as $element) {
            $html[] = $this->renderElement($element, $form, $options);
        }

        return $html;
    }

    /**
     * {@inheritDoc}
     */
    public function renderCsrf(FormInterface $form): string
    {
        $context = [
            'form' => $form,
        ];

        return $this->renderer->render('form/csrf', $context);
    }

    /**
     * {@inheritDoc}
     */
    public function getRenderer(): RendererInterface
    {
        return $this->renderer;
    }

    /**
     * Determines the appropriate widget type based on property and control.
     *
     * @param PropertySchemaInterface $property The property.
     * @param ControlInterface $control The control.
     * @return string The widget type (e.g., 'text', 'email', 'select', etc.).
     */
    private function determineWidgetType(
        PropertySchemaInterface $property,
        ControlInterface $control
    ): string {
        // Check if the control has a specific widget type defined in options.
        $options = $control->getOptions();
        if (isset($options['widget'])) {
            return $options['widget'];
        }

        // Determine widget type based on property type and format.
        $type = $property->getType();
        $format = $property instanceof StringSchemaInterface
            ? $property->getFormat()
            : null
        ;

        // Map property type and format to widget type.
        if ($property instanceof StringSchemaInterface) {
            if ($format === 'email') {
                return 'email';
            } elseif ($format === 'date') {
                return 'date';
            } elseif ($format === 'date-time') {
                return 'datetime';
            } elseif ($format === 'week') {
                return 'week';
            } elseif ($format === 'month') {
                return 'month';
            } elseif ($format === 'password') {
                return 'password';
            } elseif ($format === 'time') {
                return 'time';
            } elseif ($format === 'color') {
                return 'color';
            } elseif (
                isset($options['type'])
                && $options['type'] === 'radio'
            ) {
                return 'radio';
            } elseif ($format === 'uri') {
                return 'url';
            } elseif ($property->getEnum() !== null) {
                return 'select';
            } elseif (
                !empty($options['multi'])
                || (
                    $property->getMaxLength() !== null
                    && $property->getMaxLength() > 255
                )
                || (
                    isset($options['type'])
                    && $options['type'] === 'textarea'
                )
            ) {
                return 'textarea';
            }
            return 'text';
        } elseif (
            $type === 'number'
            || $type === 'integer'
            || $type === 'float'
            || (isset($options['type']) && $options['type'] === 'float')
            || $type === 'percent'
        ) {
            // Check if slider widget is explicitly requested.
            if (isset($options['type']) && $options['type'] === 'slider') {
                return 'slider';
            }
            // Check if range widget is explicitly requested (alias for slider).
            if (isset($options['type']) && $options['type'] === 'range') {
                return 'range';
            }
            return 'number';
        } elseif ($type === 'boolean') {
            return 'checkbox';
        } elseif ($type === 'array') {
            $schema = $property->toArray();
            if (isset($schema['items']['enum'])) {
                return 'checkboxes';
            }
            return 'collection';
        } elseif ($property instanceof ArraySchemaInterface) {
            return 'collection';
        } elseif ($type === 'object') {
            return 'compound';
        }

        // Check if the control has a specific format defined in options.
        if (isset($options['format'])) {
            if ($options['format'] === 'checkbox') {
                // For boolean fields, use single checkbox
                // For array fields with enum, use multiple checkboxes
                if ($property->getType() === 'boolean') {
                    return 'checkbox';
                } else {
                    return 'checkboxes';
                }
            }
        }

        // Default to text.
        return 'text';
    }
}
