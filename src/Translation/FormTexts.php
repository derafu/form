<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Translation;

use Closure;

/**
 * The texts of a form definition that a person reads.
 *
 * It is the only place that knows which keys of a definition are text, so what
 * is translated when the form is created (`TranslatingFormFactory`) and what is
 * audited (`Derafu\Form\Lint\FormDefinitionScanner`) are always the same.
 *
 * The texts are:
 *
 *   - In the schema, in the form and in each property (also the ones inside
 *     `items`, `$defs` and `definitions`): `title` and `description`, and the
 *     `title` of each option of `oneOf`, `anyOf` and `allOf`.
 *   - In each element of the UI schema (also the ones inside `elements` and
 *     inside `options.detail`): `label`, and `text` (the one of a `Label`).
 *   - In the `options` of an element: `placeholder`, `help`, `footer`,
 *     `error_message`, `label`, `unit`, `input_group_prepend_text` and
 *     `input_group_append_text`, and the title of each choice of `choices`
 *     (at any depth, so the ones of `cascade.choices` too).
 *
 * Everything else is data or configuration and is never touched: `scope`,
 * `type`, `const`, `enum`, `default`, `pattern`, `widget`, the icons, the
 * attributes, the rules, the keys of `choices`, and the `data`.
 */
final class FormTexts
{
    /**
     * Keys of a schema (the form or a property) with a text.
     */
    private const SCHEMA_TEXTS = ['title', 'description'];

    /**
     * Keys of a schema with schemas inside, by name.
     */
    private const SCHEMA_MAPS = ['properties', '$defs', 'definitions'];

    /**
     * Keys of a schema with a list of schemas inside.
     */
    private const SCHEMA_LISTS = ['oneOf', 'anyOf', 'allOf'];

    /**
     * Keys of an element of the UI schema with a text.
     */
    private const ELEMENT_TEXTS = ['label', 'text'];

    /**
     * Keys of the options of an element with a text.
     */
    private const OPTION_TEXTS = [
        'placeholder',
        'help',
        'footer',
        'error_message',
        'label',
        'unit',
        'input_group_prepend_text',
        'input_group_append_text',
    ];

    /**
     * Gives the definition with each of its texts replaced by what the callback
     * returns for it.
     *
     * @param array<string, mixed> $definition
     * @param Closure(string, string): string $translate Receives the text and
     * where it is (for example `schema.properties.name.title`).
     * @return array<string, mixed>
     */
    public function map(array $definition, Closure $translate): array
    {
        if (isset($definition['schema']) && is_array($definition['schema'])) {
            $definition['schema'] = $this->mapSchema($definition['schema'], 'schema', $translate);
        }

        if (isset($definition['uischema']) && is_array($definition['uischema'])) {
            $definition['uischema'] = $this->mapElement($definition['uischema'], 'uischema', $translate);
        }

        return $definition;
    }

    /**
     * The texts of the definition, in the order they are found.
     *
     * @param array<string, mixed> $definition
     * @return list<array{text: string, path: string}>
     */
    public function collect(array $definition): array
    {
        $texts = [];

        $this->map($definition, function (string $text, string $path) use (&$texts): string {
            $texts[] = ['text' => $text, 'path' => $path];

            return $text;
        });

        return $texts;
    }

    /**
     * @param array<mixed> $schema
     * @param Closure(string, string): string $translate
     * @return array<mixed>
     */
    private function mapSchema(array $schema, string $path, Closure $translate): array
    {
        $schema = $this->mapTexts($schema, self::SCHEMA_TEXTS, $path, $translate);

        foreach (self::SCHEMA_MAPS as $key) {
            if (isset($schema[$key]) && is_array($schema[$key])) {
                foreach ($schema[$key] as $name => $child) {
                    if (is_array($child)) {
                        $schema[$key][$name] = $this->mapSchema(
                            $child,
                            $path . '.' . $key . '.' . $name,
                            $translate
                        );
                    }
                }
            }
        }

        foreach (self::SCHEMA_LISTS as $key) {
            if (isset($schema[$key]) && is_array($schema[$key])) {
                foreach ($schema[$key] as $index => $child) {
                    if (is_array($child)) {
                        $schema[$key][$index] = $this->mapSchema(
                            $child,
                            $path . '.' . $key . '.' . $index,
                            $translate
                        );
                    }
                }
            }
        }

        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = array_is_list($schema['items'])
                ? array_map(
                    fn (mixed $child, int $index) => is_array($child)
                        ? $this->mapSchema($child, $path . '.items.' . $index, $translate)
                        : $child,
                    $schema['items'],
                    array_keys($schema['items'])
                )
                : $this->mapSchema($schema['items'], $path . '.items', $translate);
        }

        return $schema;
    }

    /**
     * @param array<mixed> $element
     * @param Closure(string, string): string $translate
     * @return array<mixed>
     */
    private function mapElement(array $element, string $path, Closure $translate): array
    {
        $element = $this->mapTexts($element, self::ELEMENT_TEXTS, $path, $translate);

        if (isset($element['elements']) && is_array($element['elements'])) {
            foreach ($element['elements'] as $index => $child) {
                if (is_array($child)) {
                    $element['elements'][$index] = $this->mapElement(
                        $child,
                        $path . '.elements.' . $index,
                        $translate
                    );
                }
            }
        }

        if (isset($element['options']) && is_array($element['options'])) {
            $element['options'] = $this->mapOptions($element['options'], $path . '.options', $translate);
        }

        return $element;
    }

    /**
     * @param array<mixed> $options
     * @param Closure(string, string): string $translate
     * @return array<mixed>
     */
    private function mapOptions(array $options, string $path, Closure $translate): array
    {
        $options = $this->mapTexts($options, self::OPTION_TEXTS, $path, $translate);

        if (isset($options['choices']) && is_array($options['choices'])) {
            $options['choices'] = $this->mapChoices($options['choices'], $path . '.choices', $translate);
        }

        if (isset($options['cascade']['choices']) && is_array($options['cascade']['choices'])) {
            $options['cascade']['choices'] = $this->mapChoices(
                $options['cascade']['choices'],
                $path . '.cascade.choices',
                $translate
            );
        }

        if (isset($options['detail']) && is_array($options['detail'])) {
            $options['detail'] = $this->mapElement($options['detail'], $path . '.detail', $translate);
        }

        return $options;
    }

    /**
     * The title of each choice: the values of the dict, never its keys.
     *
     * @param array<mixed> $choices
     * @param Closure(string, string): string $translate
     * @return array<mixed>
     */
    private function mapChoices(array $choices, string $path, Closure $translate): array
    {
        foreach ($choices as $key => $choice) {
            if (is_string($choice)) {
                $choices[$key] = $choice === '' ? $choice : $translate($choice, $path . '.' . $key);
            } elseif (is_array($choice)) {
                $choices[$key] = $this->mapChoices($choice, $path . '.' . $key, $translate);
            }
        }

        return $choices;
    }

    /**
     * @param array<mixed> $node
     * @param list<string> $keys
     * @param Closure(string, string): string $translate
     * @return array<mixed>
     */
    private function mapTexts(array $node, array $keys, string $path, Closure $translate): array
    {
        foreach ($keys as $key) {
            if (isset($node[$key]) && is_string($node[$key]) && $node[$key] !== '') {
                $node[$key] = $translate($node[$key], $path . '.' . $key);
            }
        }

        return $node;
    }
}
