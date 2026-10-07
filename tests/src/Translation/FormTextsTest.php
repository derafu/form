<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Translation;

use Derafu\Form\Translation\FormTexts;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Which keys of a definition are text, with the shapes that the real forms use
 * (taken from the forms of the packages and of the sites).
 */
#[CoversClass(FormTexts::class)]
final class FormTextsTest extends TestCase
{
    /**
     * A definition with a text of every kind, and with the same text as data in
     * every place that is not a text.
     *
     * @return array<string, mixed>
     */
    private function definition(): array
    {
        return [
            'options' => ['translation_domain' => 'Text'],
            'schema' => [
                'title' => 'Text',
                'description' => 'Text',
                'type' => 'object',
                'properties' => [
                    'name' => [
                        'type' => 'string',
                        'title' => 'Text',
                        'description' => 'Text',
                        'default' => 'Text',
                        'pattern' => 'Text',
                        'enum' => ['Text'],
                    ],
                    'kind' => [
                        'oneOf' => [
                            ['const' => 'Text', 'title' => 'Text'],
                        ],
                    ],
                    'lines' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'item' => ['type' => 'string', 'title' => 'Text'],
                            ],
                        ],
                    ],
                    'title' => ['type' => 'string', 'title' => 'Text'],
                ],
                '$defs' => [
                    'address' => ['type' => 'string', 'title' => 'Text'],
                ],
                'allOf' => [['title' => 'Text']],
                'anyOf' => [['description' => 'Text']],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'label' => 'Text',
                'elements' => [
                    [
                        'type' => 'Control',
                        'scope' => 'Text',
                        'label' => 'Text',
                        'options' => [
                            'placeholder' => 'Text',
                            'help' => 'Text',
                            'footer' => 'Text',
                            'error_message' => 'Text',
                            'label' => 'Text',
                            'unit' => 'Text',
                            'input_group_prepend_text' => 'Text',
                            'input_group_append_text' => 'Text',
                            'input_group_prepend_icon' => 'Text',
                            'widget' => 'Text',
                            'accept' => 'Text',
                            'id' => 'Text',
                            'attr' => ['name' => 'Text', 'title' => 'Text'],
                            'choices' => ['Text' => 'Text', 'other' => '', 'list' => 'Text'],
                            'cascade' => [
                                'dependsOn' => 'Text',
                                'choices' => ['pdf' => ['static' => 'Text', 'Text' => 'Text']],
                            ],
                            'detail' => [
                                'elements' => [
                                    ['type' => 'Control', 'scope' => 'Text', 'options' => ['placeholder' => 'Text']],
                                ],
                            ],
                        ],
                        'rule' => ['effect' => 'SHOW', 'condition' => ['scope' => 'Text', 'schema' => ['const' => 'Text']]],
                    ],
                    ['type' => 'Label', 'text' => 'Text'],
                ],
            ],
            'data' => ['name' => 'Text'],
            'rules' => ['name' => 'Text'],
        ];
    }

    #[Test]
    public function findsEveryTextAndNothingElse(): void
    {
        $paths = array_column((new FormTexts())->collect($this->definition()), 'path');

        $this->assertSame([
            'schema.title',
            'schema.description',
            'schema.properties.name.title',
            'schema.properties.name.description',
            'schema.properties.kind.oneOf.0.title',
            'schema.properties.lines.items.properties.item.title',
            'schema.properties.title.title',
            'schema.$defs.address.title',
            'schema.anyOf.0.description',
            'schema.allOf.0.title',
            'uischema.label',
            'uischema.elements.0.label',
            'uischema.elements.0.options.placeholder',
            'uischema.elements.0.options.help',
            'uischema.elements.0.options.footer',
            'uischema.elements.0.options.error_message',
            'uischema.elements.0.options.label',
            'uischema.elements.0.options.unit',
            'uischema.elements.0.options.input_group_prepend_text',
            'uischema.elements.0.options.input_group_append_text',
            'uischema.elements.0.options.choices.Text',
            'uischema.elements.0.options.choices.list',
            'uischema.elements.0.options.cascade.choices.pdf.static',
            'uischema.elements.0.options.cascade.choices.pdf.Text',
            'uischema.elements.0.options.detail.elements.0.options.placeholder',
            'uischema.elements.1.text',
        ], $paths);
    }

    #[Test]
    public function replacesTheTextsAndLeavesTheRestAsItWas(): void
    {
        $definition = $this->definition();

        $mapped = (new FormTexts())->map($definition, fn (string $text, string $path) => 'T:' . $text);

        // What is not a text is exactly the same: the definition with its texts
        // put back is the original one.
        $back = (new FormTexts())->map($mapped, fn (string $text) => substr($text, 2));
        $this->assertSame($definition, $back);

        // And every text was replaced.
        foreach ((new FormTexts())->collect($mapped) as $found) {
            $this->assertStringStartsWith('T:', $found['text'], $found['path']);
        }
        $this->assertSame('Text', $mapped['schema']['properties']['name']['default']);

        // The keys of the choices are values, not texts.
        $this->assertSame(
            ['Text' => 'T:Text', 'other' => '', 'list' => 'T:Text'],
            $mapped['uischema']['elements'][0]['options']['choices']
        );
    }

    #[Test]
    public function leavesWhatIsNotAStringOrIsEmpty(): void
    {
        $definition = [
            'schema' => ['title' => '', 'description' => 7, 'properties' => ['a' => 'not a schema']],
            'uischema' => ['label' => null, 'options' => ['help' => ['x'], 'choices' => ['a' => 3]]],
        ];

        $this->assertSame([], (new FormTexts())->collect($definition));
        $this->assertSame(
            $definition,
            (new FormTexts())->map($definition, fn (string $text) => 'T:' . $text)
        );
    }

    #[Test]
    public function aDefinitionWithoutSchemaOrUiSchemaHasNoTexts(): void
    {
        $this->assertSame([], (new FormTexts())->collect(['data' => ['name' => 'Text'], 'options' => ['translation_domain' => 'x']]));
    }
}
