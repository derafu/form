<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Renderer\Element;

use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Renderer\ElementRendererInterface;
use Derafu\Form\Contract\UiSchema\LabelInterface;
use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Support\ArrayReader;
use Derafu\Support\ArrayType;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException as InvalidArgumentException;

/**
 * Renderer for label elements.
 *
 * Label elements are used to display static text in a form.
 */
final class LabelRenderer implements ElementRendererInterface
{
    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $options
     */
    public function render(
        UiSchemaElementInterface $element,
        FormInterface $form,
        array $options = []
    ): string {
        // Check if the element is a LabelInterface.
        if (!$element instanceof LabelInterface) {
            throw new InvalidArgumentException([
                'Element must be an instance of {expected} in LabelRenderer, {given} given.',
                'expected' => LabelInterface::class,
                'given' => get_class($element),
            ]);
        }

        // Merge options.
        $finalOptions = array_merge($options, $element->getOptions());

        // Get label attributes.
        $reader = ArrayReader::from($finalOptions);
        $labelAttr = $reader->mapOf('label_attr', ArrayType::scalar()) ?? [];
        $labelClass = isset($labelAttr['class']) ? (string) $labelAttr['class'] : 'form-label';

        // Determine if this is a heading.
        $isHeading = $reader->bool('heading') ?? false;
        $headingLevel = $reader->int('heading_level') ?? 3; // Default to h3

        // Generate HTML.
        if ($isHeading) {
            $html = sprintf(
                '<h%d class="%s">%s</h%d>',
                $headingLevel,
                htmlspecialchars($labelClass),
                $element->getText(),
                $headingLevel
            );
        } else {
            // Build HTML attributes string.
            $attrString = '';
            $attrs = array_merge(['class' => $labelClass], $labelAttr);

            foreach ($attrs as $name => $value) {
                if ($value === true) {
                    $attrString .= ' ' . $name;
                } elseif ($value !== false) {
                    $attrString .= ' ' . $name . '="' . htmlspecialchars((string)$value, ENT_QUOTES) . '"';
                }
            }

            $html = sprintf(
                '<label%s>%s</label>',
                $attrString,
                $element->getText()
            );
        }

        return $html;
    }
}
