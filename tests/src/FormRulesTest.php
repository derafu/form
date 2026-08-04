<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm;

use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Contract\Rules\FormRulesInterface;
use Derafu\Form\Data\FormData;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\NumberSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

#[CoversClass(Form::class)]
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(FormData::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(FormSchema::class)]
#[CoversClass(NumberSchema::class)]
#[CoversTrait(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(Control::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Widget::class)]
#[CoversClass(WidgetFactory::class)]
final class FormRulesTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Minimal valid definition reused across tests
    // -------------------------------------------------------------------------

    private function baseDefinition(): array
    {
        return [
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string', 'title' => 'Name'],
                    'price' => ['type' => 'number', 'title' => 'Price', 'minimum' => 0],
                ],
                'required' => ['name', 'price'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                    ['type' => 'Control', 'scope' => '#/properties/price', 'options' => ['type' => 'money', 'symbol' => '$']],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // getRules() default
    // -------------------------------------------------------------------------

    public function testGetRulesReturnsEmptyArrayByDefault(): void
    {
        $form = Form::fromArray($this->baseDefinition());

        $this->assertInstanceOf(FormRulesInterface::class, $form->getRules());
        $this->assertSame([], $form->getRules()->toArray());
    }

    // -------------------------------------------------------------------------
    // fromArray() reading the 'rules' key
    // -------------------------------------------------------------------------

    public function testFromArrayReadsRulesSection(): void
    {
        $definition = $this->baseDefinition();
        $definition['rules'] = [
            'name' => [
                'transform' => ['uppercase'],
            ],
            'price' => [
                'validate' => ['gt:0'],
            ],
        ];

        $form = Form::fromArray($definition);

        $this->assertSame($definition['rules'], $form->getRules()->toArray());
    }

    public function testFromArrayWithoutRulesKeyReturnsEmptyRules(): void
    {
        $form = Form::fromArray($this->baseDefinition());

        $this->assertSame([], $form->getRules()->toArray());
    }

    public function testFromArrayWithEmptyRulesKeyReturnsEmptyRules(): void
    {
        $definition = $this->baseDefinition();
        $definition['rules'] = [];

        $form = Form::fromArray($definition);

        $this->assertSame([], $form->getRules()->toArray());
    }

    // -------------------------------------------------------------------------
    // toArray() serialisation
    // -------------------------------------------------------------------------

    public function testToArrayIncludesRulesWhenNotEmpty(): void
    {
        $definition = $this->baseDefinition();
        $definition['rules'] = [
            'name' => ['sanitize' => ['strip_tags']],
        ];

        $form = Form::fromArray($definition);
        $array = $form->toArray();

        $this->assertArrayHasKey('rules', $array);
        $this->assertSame($definition['rules'], $array['rules']);
        // Also verify via getRules() typed accessor.
        $this->assertSame($definition['rules'], $form->getRules()->toArray());
    }

    public function testToArrayRoundTrip(): void
    {
        $definition = $this->baseDefinition();
        $definition['rules'] = [
            'name' => [
                'transform' => ['uppercase'],
                'validate'  => ['alpha'],
            ],
            'price' => [
                'validate' => ['gt:0'],
            ],
        ];

        $array = Form::fromArray($definition)->toArray();

        // Round-trip: re-create from the serialised form and compare rules.
        $rebuilt = Form::fromArray($array);
        $this->assertSame($definition['rules'], $rebuilt->getRules()->toArray());
    }

    // -------------------------------------------------------------------------
    // withData() preserves rules
    // -------------------------------------------------------------------------

    public function testWithDataPreservesRules(): void
    {
        $definition = $this->baseDefinition();
        $definition['rules'] = [
            'price' => ['validate' => ['gt:0']],
        ];

        $original = Form::fromArray($definition);
        $updated  = $original->withData(FormData::fromArray(['name' => 'Widget', 'price' => 9.99]));

        $this->assertSame(
            $original->getRules()->toArray(),
            $updated->getRules()->toArray()
        );
    }

    public function testWithDataPreservesEmptyRules(): void
    {
        $original = Form::fromArray($this->baseDefinition());
        $updated  = $original->withData(FormData::fromArray(['name' => 'Widget', 'price' => 9.99]));

        $this->assertSame([], $updated->getRules()->toArray());
    }

    // -------------------------------------------------------------------------
    // Rules do not interfere with form rendering
    // -------------------------------------------------------------------------

    public function testFormWithRulesHasSameFieldsAsWithout(): void
    {
        $without = Form::fromArray($this->baseDefinition());

        $with = Form::fromArray(array_merge($this->baseDefinition(), [
            'rules' => [
                'name'  => ['transform' => ['uppercase']],
                'price' => ['validate'  => ['gt:0']],
            ],
        ]));

        $this->assertSame(array_keys($without->getFields()), array_keys($with->getFields()));
    }
}
