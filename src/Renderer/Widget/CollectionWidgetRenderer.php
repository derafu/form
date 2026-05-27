<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer\Widget;

use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Contract\Renderer\WidgetRendererInterface;
use Derafu\Form\Contract\Schema\ArraySchemaInterface;
use Derafu\Form\Contract\Schema\ObjectSchemaInterface;
use Derafu\Form\Contract\Schema\PropertySchemaInterface;
use Derafu\Form\Contract\UiSchema\ControlInterface;
use Derafu\Form\Contract\UiSchema\FormUiSchemaInterface;
use Derafu\Form\Data\FormData;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Form;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\VerticalLayout;
use InvalidArgumentException;

/**
 * Renderer for collection widgets (array of objects).
 *
 * Supports two rendering modes:
 * - Simple: all detail elements are direct Controls → table-like layout with
 *   a header row and compact widget-only rows.
 * - Complex: detail contains nested layouts → bordered block rows with the
 *   full sub-form rendered inside each.
 */
final class CollectionWidgetRenderer implements WidgetRendererInterface
{
    /**
     * {@inheritDoc}
     */
    public function render(
        FormFieldInterface $field,
        array $options = []
    ): string {
        $formRenderer = $options['renderer'] ?? null;
        if (!$formRenderer instanceof FormRendererInterface) {
            throw new InvalidArgumentException(
                'The "renderer" option in CollectionWidgetRenderer must be an '
                . 'instance of FormRendererInterface.'
            );
        }

        $property = $field->getProperty();
        if (!$property instanceof ArraySchemaInterface) {
            throw new InvalidArgumentException(
                'CollectionWidgetRenderer requires an ArraySchemaInterface property.'
            );
        }

        $itemsSchema = $property->getItems();
        $controlOptions = $field->getControl()->getOptions();

        $detailUiSchema = isset($controlOptions['detail'])
            ? FormUiSchemaFactory::create($controlOptions['detail'])
            : ($itemsSchema !== null
                ? $this->buildDefaultDetail($itemsSchema)
                : new VerticalLayout());

        $rows = $field->getData() ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $fieldName = $field->getName();
        $isSimple = $this->isSimpleDetail($detailUiSchema);

        if ($isSimple) {
            $headers = $this->extractHeaders($itemsSchema, $detailUiSchema);
            $renderedRows = [];
            foreach ($rows as $i => $rowData) {
                $renderedRows[] = $this->renderSimpleRow(
                    $formRenderer,
                    $itemsSchema,
                    $detailUiSchema,
                    is_array($rowData) ? $rowData : [],
                    $fieldName,
                    $i
                );
            }
            $templateRow = $this->renderSimpleRow(
                $formRenderer,
                $itemsSchema,
                $detailUiSchema,
                [],
                $fieldName,
                '__INDEX__'
            );
        } else {
            $headers = [];
            $renderedRows = [];
            foreach ($rows as $i => $rowData) {
                $renderedRows[] = $this->renderRow(
                    $formRenderer,
                    $itemsSchema,
                    $detailUiSchema,
                    $rowData,
                    $fieldName,
                    $i
                );
            }
            $templateRow = $this->renderRow(
                $formRenderer,
                $itemsSchema,
                $detailUiSchema,
                [],
                $fieldName,
                '__INDEX__'
            );
        }

        return $formRenderer->getRenderer()->render('form/widget/collection', [
            'field' => $field,
            'field_name' => $fieldName,
            'rows' => $renderedRows,
            'template_row' => $templateRow,
            'is_simple' => $isSimple,
            'headers' => $headers,
            'options' => $options,
        ]);
    }

    /**
     * Builds a sub-form for a single row and applies the name pattern.
     */
    private function buildSubForm(
        ?PropertySchemaInterface $itemsSchema,
        FormUiSchemaInterface $detailUiSchema,
        array $rowData,
        string $fieldName,
        int|string $index
    ): Form {
        $schema = FormSchema::fromArray($itemsSchema?->toArray() ?? []);

        $subForm = new Form(
            schema: $schema,
            uischema: $detailUiSchema,
            data: $rowData ? FormData::fromArray($rowData) : null,
        );

        $namePattern = $fieldName . '[' . $index . '][%s]';
        foreach ($subForm->getFields() as $subField) {
            $subField->setNamePattern($namePattern);
        }

        return $subForm;
    }

    /**
     * Renders a complex row as a full sub-form (block mode).
     */
    private function renderRow(
        FormRendererInterface $formRenderer,
        ?PropertySchemaInterface $itemsSchema,
        FormUiSchemaInterface $detailUiSchema,
        mixed $rowData,
        string $fieldName,
        int|string $index
    ): string {
        if (is_string($rowData)) {
            $rowData = json_decode($rowData, true) ?? [];
        }
        if (!is_array($rowData)) {
            $rowData = [];
        }

        $subForm = $this->buildSubForm(
            $itemsSchema,
            $detailUiSchema,
            $rowData,
            $fieldName,
            $index
        );

        return $formRenderer->renderElement(
            $subForm->getUiSchema(),
            $subForm,
            ['floating_labels' => false]
        );
    }

    /**
     * Renders a simple row as an array of widget HTML strings (table mode).
     */
    private function renderSimpleRow(
        FormRendererInterface $formRenderer,
        ?PropertySchemaInterface $itemsSchema,
        FormUiSchemaInterface $detailUiSchema,
        array $rowData,
        string $fieldName,
        int|string $index
    ): array {
        $subForm = $this->buildSubForm(
            $itemsSchema,
            $detailUiSchema,
            $rowData,
            $fieldName,
            $index
        );

        $widgets = [];
        foreach ($subForm->getUiSchema()->getElements() as $controlElement) {
            $widgets[] = $formRenderer->renderElement(
                $controlElement,
                $subForm,
                ['render_mode' => 'widget']
            );
        }

        return $widgets;
    }

    /**
     * Returns true if all detail elements are direct Controls (no nested layouts).
     */
    private function isSimpleDetail(FormUiSchemaInterface $detailUiSchema): bool
    {
        foreach ($detailUiSchema->getElements() as $element) {
            if (!$element instanceof ControlInterface) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extracts header labels from items schema in detail element order.
     */
    private function extractHeaders(
        ?PropertySchemaInterface $itemsSchema,
        FormUiSchemaInterface $detailUiSchema
    ): array {
        $headers = [];
        foreach ($detailUiSchema->getElements() as $element) {
            if (!$element instanceof ControlInterface) {
                continue;
            }
            $propName = $element->getPropertyName();
            $title = null;
            if ($itemsSchema instanceof ObjectSchemaInterface) {
                $title = $itemsSchema->getProperty($propName)?->getTitle();
            }
            $headers[] = $title ?? ucfirst($propName);
        }

        return $headers;
    }

    /**
     * Builds a default VerticalLayout detail from items schema properties.
     */
    private function buildDefaultDetail(?PropertySchemaInterface $itemsSchema): FormUiSchemaInterface
    {
        $layout = new VerticalLayout();
        if ($itemsSchema instanceof ObjectSchemaInterface) {
            foreach (array_keys($itemsSchema->getProperties()) as $propName) {
                $layout->addElement(Control::fromArray([
                    'type' => 'Control',
                    'scope' => '#/properties/' . $propName,
                ]));
            }
        }

        return $layout;
    }
}
