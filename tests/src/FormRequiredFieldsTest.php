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
use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Data\FormData;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormDefinition;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\ObjectSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * A field is required when the object that has its property lists it in
 * `required`.
 */
#[CoversClass(Form::class)]
#[UsesClass(FormDefinition::class)]
#[UsesClass(AbstractPropertySchema::class)]
#[UsesClass(AbstractUiSchemaElement::class)]
#[UsesClass(FormData::class)]
#[UsesClass(FormUiSchemaFactory::class)]
#[UsesClass(PropertySchemaFactory::class)]
#[UsesClass(UiSchemaElementFactory::class)]
#[UsesClass(FormOptions::class)]
#[UsesClass(FormRules::class)]
#[UsesClass(FormSchema::class)]
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(StringSchema::class)]
#[UsesClass(Control::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(VerticalLayout::class)]
#[UsesTrait(ObjectSchemaTrait::class)]
#[UsesClass(FormField::class)]
#[UsesClass(ObjectSchema::class)]
#[UsesClass(Widget::class)]
#[UsesClass(WidgetFactory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Options\FormAttributes::class)]
final class FormRequiredFieldsTest extends TestCase
{
    #[Test]
    public function shouldTakeTheRequiredFieldsFromTheSchema(): void
    {
        $form = Form::fromArray([
            'schema' => [
                'type' => 'object',
                'required' => ['name', 'address'],
                'properties' => [
                    'name' => ['type' => 'string'],
                    'nick' => ['type' => 'string'],
                    'address' => [
                        'type' => 'object',
                        'required' => ['city'],
                        'properties' => ['city' => ['type' => 'string'], 'zip' => ['type' => 'string']],
                    ],
                ],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                    ['type' => 'Control', 'scope' => '#/properties/nick'],
                    ['type' => 'Control', 'scope' => '#/properties/address/properties/city'],
                    ['type' => 'Control', 'scope' => '#/properties/address/properties/zip'],
                ],
            ],
        ]);

        $required = [];
        foreach ($form->getFields() as $name => $field) {
            $required[$name] = $field->isRequired();
        }

        $this->assertSame(
            ['name' => true, 'nick' => false, 'address.city' => true, 'address.zip' => false],
            $required
        );
    }

    /**
     * @param array<string, mixed> $options
     */
    private function fieldWithOptions(array $options): FormFieldInterface
    {
        $form = Form::fromArray([
            'schema' => ['type' => 'object', 'required' => ['name'], 'properties' => ['name' => ['type' => 'string']]],
            'uischema' => ['type' => 'VerticalLayout', 'elements' => [['type' => 'Control', 'scope' => '#/properties/name']]],
            'options' => $options,
        ]);
        $field = $form->getField('name');
        $this->assertNotNull($field);

        return $field;
    }

    #[Test]
    public function shouldAskInTheBrowserAndShowTheAsteriskUnlessTheFormSaysNot(): void
    {
        $field = $this->fieldWithOptions([]);
        $this->assertTrue($field->isNativeRequired());
        $this->assertFalse($field->isRequiredAsteriskHidden());

        $this->assertFalse($this->fieldWithOptions(['native_required' => false])->isNativeRequired());
        $this->assertFalse($this->fieldWithOptions(['attributes' => ['novalidate' => true]])->isNativeRequired());
        $this->assertTrue($this->fieldWithOptions(['attributes' => ['novalidate' => true], 'native_required' => true])->isNativeRequired());
        $this->assertTrue($this->fieldWithOptions(['hide_required_asterisk' => true])->isRequiredAsteriskHidden());
    }

    #[Test]
    public function shouldNotAcceptAnOptionThatIsNotABoolean(): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The key "native_required" must be of type bool, string given.');

        $this->fieldWithOptions(['native_required' => 'no']);
    }
}
