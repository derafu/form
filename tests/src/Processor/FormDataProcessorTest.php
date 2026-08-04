<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Tests\Processor;

use Derafu\DataProcessor\ProcessorFactory;
use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Data\FormData;
use Derafu\Form\Exception\ValidationException;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Processor\FormDataProcessor;
use Derafu\Form\Processor\FormRulesResolver;
use Derafu\Form\Processor\ProcessResult;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\BooleanSchema;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\IntegerSchema;
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

#[CoversClass(FormDataProcessor::class)]
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(FormRulesResolver::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(ProcessResult::class)]
#[CoversClass(ValidationException::class)]
#[CoversClass(FormData::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversClass(Form::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(BooleanSchema::class)]
#[CoversClass(FormSchema::class)]
#[CoversClass(IntegerSchema::class)]
#[CoversClass(NumberSchema::class)]
#[CoversTrait(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(Control::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Widget::class)]
#[CoversClass(WidgetFactory::class)]
final class FormDataProcessorTest extends TestCase
{
    private FormDataProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new FormDataProcessor(
            new FormRulesResolver(),
            ProcessorFactory::create()
        );
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function simpleForm(array $extraProps = [], array $required = []): Form
    {
        $properties = array_merge([
            'name' => ['type' => 'string', 'title' => 'Name'],
            'email' => ['type' => 'string', 'format' => 'email', 'title' => 'Email'],
        ], $extraProps);

        $elements = array_map(
            fn (string $name) => ['type' => 'Control', 'scope' => "#/properties/{$name}"],
            array_keys($properties)
        );

        $schema = ['type' => 'object', 'properties' => $properties];
        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return Form::fromArray([
            'schema' => $schema,
            'uischema' => ['type' => 'VerticalLayout', 'elements' => $elements],
        ]);
    }

    // =========================================================================
    // Tests
    // =========================================================================

    public function testProcessValidData(): void
    {
        $form = $this->simpleForm(required: ['name', 'email']);

        $result = $this->processor->process($form, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertInstanceOf(ProcessResult::class, $result);
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());

        $data = $result->getProcessedData();
        $this->assertSame('John Doe', $data['name']);
        $this->assertSame('john@example.com', $data['email']);
    }

    public function testProcessDataWithValidationErrors(): void
    {
        $form = $this->simpleForm(required: ['name', 'email']);

        $result = $this->processor->process($form, [
            'name' => '',
            'email' => 'invalid-email',
        ]);

        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());

        $errors = $result->getErrors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);

        // Original values preserved for invalid fields.
        $data = $result->getProcessedData();
        $this->assertSame('', $data['name']);
        $this->assertSame('invalid-email', $data['email']);
    }

    public function testProcessDataWithPartialErrors(): void
    {
        $form = $this->simpleForm(required: ['name', 'email']);

        $result = $this->processor->process($form, [
            'name' => 'John Doe',
            'email' => 'invalid-email',
        ]);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayNotHasKey('name', $errors);

        $data = $result->getProcessedData();
        $this->assertSame('John Doe', $data['name']);
    }

    public function testProcessDataWithExtraFields(): void
    {
        $form = $this->simpleForm(required: ['name']);

        $result = $this->processor->process($form, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'extra_field' => 'extra value',
        ]);

        $this->assertTrue($result->isValid());
        $data = $result->getProcessedData();
        $this->assertArrayHasKey('extra_field', $data);
        $this->assertSame('extra value', $data['extra_field']);
    }

    public function testGetFormReturnsNewInstanceWithProcessedData(): void
    {
        $form = $this->simpleForm(required: ['name', 'email']);

        $result = $this->processor->process($form, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertTrue($result->isValid());
        $formWithData = $result->getForm();
        $this->assertNotSame($form, $formWithData);
    }

    public function testProcessDataPreservesInvalidData(): void
    {
        $form = $this->simpleForm(
            ['age' => ['type' => 'integer', 'minimum' => 18, 'title' => 'Age']],
            ['name', 'email', 'age']
        );

        $result = $this->processor->process($form, [
            'name' => 'Jo',           // too short after trim? no minLength set, valid
            'email' => 'invalid',     // invalid email
            'age' => 'not-a-number',  // invalid integer
        ]);

        $this->assertFalse($result->isValid());
        $data = $result->getProcessedData();
        $this->assertSame('invalid', $data['email']);
        $this->assertSame('not-a-number', $data['age']);
    }

    public function testProcessDataPreservesMixedValidAndInvalidData(): void
    {
        $form = $this->simpleForm(
            [
                'age' => ['type' => 'integer', 'minimum' => 18, 'title' => 'Age'],
                'phone' => ['type' => 'string', 'minLength' => 10, 'title' => 'Phone'],
            ],
            ['name', 'email', 'age', 'phone']
        );

        $result = $this->processor->process($form, [
            'name' => 'John Doe',          // valid
            'email' => 'invalid-email',    // invalid
            'age' => 25,                   // valid
            'phone' => '123',              // too short
        ]);

        $this->assertFalse($result->isValid());
        $data = $result->getProcessedData();
        $this->assertSame('John Doe', $data['name']);
        $this->assertSame('invalid-email', $data['email']);
        $this->assertSame(25, $data['age']);
        $this->assertSame('123', $data['phone']);
    }

    public function testCastIsAppliedToValidValues(): void
    {
        $form = Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'age' => ['type' => 'integer', 'minimum' => 0],
                    'active' => ['type' => 'boolean'],
                    'score' => ['type' => 'number'],
                ],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/age'],
                    ['type' => 'Control', 'scope' => '#/properties/active'],
                    ['type' => 'Control', 'scope' => '#/properties/score'],
                ],
            ],
        ]);

        $result = $this->processor->process($form, [
            'age' => '25',
            'active' => '1',
            'score' => '9.5',
        ]);

        $this->assertTrue($result->isValid());
        $data = $result->getProcessedData();
        $this->assertSame(25, $data['age']);
        $this->assertTrue($data['active']);
        $this->assertSame(9.5, $data['score']);
    }

    public function testExplicitRulesFromRulesSectionAreApplied(): void
    {
        $form = Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'sort_order' => ['type' => 'string', 'title' => 'Sort Order'],
                    'sku' => ['type' => 'string', 'title' => 'SKU'],
                ],
                'required' => ['sku'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/sort_order'],
                    ['type' => 'Control', 'scope' => '#/properties/sku'],
                ],
            ],
            'rules' => [
                'sort_order' => ['cast' => 'integer'],
                'sku' => ['transform' => ['uppercase']],
            ],
        ]);

        $result = $this->processor->process($form, [
            'sort_order' => '3',
            'sku' => 'wbh-001-blk',
        ]);

        $this->assertTrue($result->isValid());
        $data = $result->getProcessedData();
        $this->assertSame(3, $data['sort_order']);
        $this->assertSame('WBH-001-BLK', $data['sku']);
    }
}
