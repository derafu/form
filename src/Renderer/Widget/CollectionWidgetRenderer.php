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
 * Renders a dynamic list of rows where each row is rendered using the
 * `detail` layout defined in the control options, or a default vertical
 * layout built from the items schema properties.
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
        // Get the main form renderer from options.
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

        return $formRenderer->getRenderer()->render('form/widget/collection', [
            'field' => $field,
            'field_name' => $fieldName,
            'rows' => $renderedRows,
            'template_row' => $templateRow,
            'options' => $options,
        ]);
    }

    /**
     * Renders a single row of the collection.
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

        $subForm = Form::fromArray([
            'schema' => $itemsSchema,
            'uischema' => $detailDefinition,
            'data' => $rowData ?: null,
        ]);

        $namePattern = $fieldName . '[' . $index . '][%s]';
        foreach ($subForm->getFields() as $subField) {
            $subField->setNamePattern($namePattern);
        }

        return $formRenderer->renderElement(
            $subForm->getUiSchema(),
            $subForm,
            ['floating_labels' => false]
        );
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
