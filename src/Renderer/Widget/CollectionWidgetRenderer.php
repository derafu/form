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
use Derafu\Form\Form;
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
        $detailDefinition = $controlOptions['detail']
            ?? $this->buildDefaultDetail($itemsSchema);

        $rows = $field->getData() ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $fieldName = $field->getName();
        $isSimple = $this->isSimpleDetail($detailDefinition);

        if ($isSimple) {
            $headers = $this->extractHeaders($itemsSchema, $detailDefinition);
            $renderedRows = [];
            foreach ($rows as $i => $rowData) {
                $renderedRows[] = $this->renderSimpleRow(
                    $formRenderer,
                    $itemsSchema,
                    $detailDefinition,
                    is_array($rowData) ? $rowData : [],
                    $fieldName,
                    $i
                );
            }
            $templateRow = $this->renderSimpleRow(
                $formRenderer,
                $itemsSchema,
                $detailDefinition,
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
                    $detailDefinition,
                    $rowData,
                    $fieldName,
                    $i
                );
            }
            $templateRow = $this->renderRow(
                $formRenderer,
                $itemsSchema,
                $detailDefinition,
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
        array $itemsSchema,
        array $detailDefinition,
        array $rowData,
        string $fieldName,
        int|string $index
    ): Form {
        $subForm = Form::fromArray([
            'schema' => $itemsSchema,
            'uischema' => $detailDefinition,
            'data' => $rowData ?: null,
        ]);

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
        array $itemsSchema,
        array $detailDefinition,
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
            $detailDefinition,
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
        array $itemsSchema,
        array $detailDefinition,
        array $rowData,
        string $fieldName,
        int|string $index
    ): array {
        $subForm = $this->buildSubForm(
            $itemsSchema,
            $detailDefinition,
            $rowData,
            $fieldName,
            $index
        );

        $widgets = [];
        foreach ($detailDefinition['elements'] as $element) {
            $propName = $this->scopeToPropertyName($element['scope'] ?? '');
            $subField = $subForm->getField($propName);
            if ($subField !== null) {
                $widgets[] = $formRenderer->renderWidget($subField, []);
            }
        }

        return $widgets;
    }

    /**
     * Returns true if all detail elements are direct Controls (no nested layouts).
     */
    private function isSimpleDetail(array $detailDefinition): bool
    {
        foreach ($detailDefinition['elements'] ?? [] as $element) {
            if (($element['type'] ?? '') !== 'Control') {
                return false;
            }
        }

        return true;
    }

    /**
     * Extracts header labels from items schema in detail element order.
     */
    private function extractHeaders(array $itemsSchema, array $detailDefinition): array
    {
        $headers = [];
        foreach ($detailDefinition['elements'] ?? [] as $element) {
            $propName = $this->scopeToPropertyName($element['scope'] ?? '');
            $headers[] = $itemsSchema['properties'][$propName]['title']
                ?? ucfirst($propName);
        }

        return $headers;
    }

    /**
     * Extracts the property name from a JSON Forms scope string.
     */
    private function scopeToPropertyName(string $scope): string
    {
        $parts = explode('/', $scope);

        return end($parts);
    }

    /**
     * Builds a default VerticalLayout detail from items schema properties.
     */
    private function buildDefaultDetail(array $itemsSchema): array
    {
        $elements = [];
        foreach (array_keys($itemsSchema['properties'] ?? []) as $propName) {
            $elements[] = [
                'type' => 'Control',
                'scope' => '#/properties/' . $propName,
            ];
        }

        return ['type' => 'VerticalLayout', 'elements' => $elements];
    }
}
