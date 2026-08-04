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

use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormField;
use Derafu\Form\Processor\ProcessResult;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\BooleanSchema;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\IntegerSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ProcessResult.
 */
#[CoversClass(\Derafu\Form\Processor\ProcessResult::class)]
#[CoversClass(\Derafu\Form\Data\FormData::class)]
#[UsesClass(Form::class)]
#[UsesClass(FormField::class)]
#[UsesClass(FormRules::class)]
#[UsesClass(AbstractPropertySchema::class)]
#[UsesClass(AbstractUiSchemaElement::class)]
#[UsesClass(PropertySchemaFactory::class)]
#[UsesClass(UiSchemaElementFactory::class)]
#[UsesClass(FormSchema::class)]
#[UsesTrait(ObjectSchemaTrait::class)]
#[UsesClass(StringSchema::class)]
#[UsesClass(IntegerSchema::class)]
#[UsesClass(BooleanSchema::class)]
#[UsesClass(Control::class)]
#[UsesClass(VerticalLayout::class)]
#[UsesClass(Widget::class)]
#[UsesClass(WidgetFactory::class)]
final class ProcessResultTest extends TestCase
{
    private FormInterface $form;

    protected function setUp(): void
    {
        $this->form = $this->makeForm();
    }

    public function testValidResult(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $result = new ProcessResult($this->form, $data);

        $this->assertSame($data, $result->getProcessedData());
        $this->assertEmpty($result->getErrors());
        $this->assertTrue($result->isValid());
        $this->assertFalse($result->hasErrors());
        $this->assertEmpty($result->getAllErrors());
    }

    public function testInvalidResultWithErrors(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'invalid-email'];
        $errors = [
            'email' => ['Invalid email format'],
            'name' => ['Name is too short'],
        ];
        $result = new ProcessResult($this->form, $data, $errors, false);

        $this->assertSame($data, $result->getProcessedData());
        $this->assertSame($errors, $result->getErrors());
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertSame(['Invalid email format', 'Name is too short'], $result->getAllErrors());
    }

    public function testGetFieldErrors(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'invalid-email'];
        $errors = [
            'email' => ['Invalid email format'],
            'name' => ['Name is too short'],
        ];
        $result = new ProcessResult($this->form, $data, $errors, false);

        $this->assertSame(['Invalid email format'], $result->getFieldErrors('email'));
        $this->assertSame(['Name is too short'], $result->getFieldErrors('name'));
        $this->assertEmpty($result->getFieldErrors('non_existent_field'));
    }

    public function testHasFieldErrors(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'invalid-email'];
        $errors = [
            'email' => ['Invalid email format'],
            'name' => [],
        ];
        $result = new ProcessResult($this->form, $data, $errors, false);

        $this->assertTrue($result->hasFieldErrors('email'));
        $this->assertFalse($result->hasFieldErrors('name'));
        $this->assertFalse($result->hasFieldErrors('non_existent_field'));
    }

    public function testEmptyErrorsArray(): void
    {
        $data = ['name' => 'John Doe'];
        $errors = [];
        $result = new ProcessResult($this->form, $data, $errors, true);

        $this->assertSame($data, $result->getProcessedData());
        $this->assertEmpty($result->getErrors());
        $this->assertTrue($result->isValid());
        $this->assertFalse($result->hasErrors());
        $this->assertEmpty($result->getAllErrors());
    }

    public function testMultipleErrorsPerField(): void
    {
        $data = ['email' => 'invalid-email'];
        $errors = [
            'email' => [
                'Invalid email format',
                'Email must be from allowed domain',
            ],
        ];
        $result = new ProcessResult($this->form, $data, $errors, false);

        $this->assertSame($errors, $result->getErrors());
        $this->assertSame(['Invalid email format', 'Email must be from allowed domain'], $result->getFieldErrors('email'));
        $this->assertSame(['Invalid email format', 'Email must be from allowed domain'], $result->getAllErrors());
    }

    public function testComplexDataTypes(): void
    {
        $data = [
            'user' => ['id' => 1, 'name' => 'John'],
            'settings' => ['theme' => 'dark', 'notifications' => true],
            'tags' => ['php', 'testing', 'forms'],
        ];
        $errors = [
            'user' => ['User data is invalid'],
            'settings' => ['Invalid theme value'],
        ];
        $result = new ProcessResult($this->form, $data, $errors, false);

        $this->assertSame($data, $result->getProcessedData());
        $this->assertSame($errors, $result->getErrors());
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertSame(['User data is invalid', 'Invalid theme value'], $result->getAllErrors());
    }

    public function testNullData(): void
    {
        $result = new ProcessResult($this->form, null, [], true);

        $this->assertNull($result->getProcessedData());
        $this->assertEmpty($result->getErrors());
        $this->assertTrue($result->isValid());
        $this->assertFalse($result->hasErrors());
    }

    public function testEmptyData(): void
    {
        $result = new ProcessResult($this->form, [], [], true);

        $this->assertSame([], $result->getProcessedData());
        $this->assertEmpty($result->getErrors());
        $this->assertTrue($result->isValid());
        $this->assertFalse($result->hasErrors());
    }

    public function testValidResultWithDefaultParameters(): void
    {
        $data = ['name' => 'John Doe'];
        $result = new ProcessResult($this->form, $data);

        $this->assertSame($data, $result->getProcessedData());
        $this->assertEmpty($result->getErrors());
        $this->assertTrue($result->isValid());
    }

    public function testInvalidResultWithDefaultParameters(): void
    {
        $data = ['name' => 'John Doe'];
        $errors = ['name' => ['Name is required']];
        $result = new ProcessResult($this->form, $data, $errors, true);

        $this->assertSame($data, $result->getProcessedData());
        $this->assertSame($errors, $result->getErrors());
        $this->assertTrue($result->isValid());
    }

    public function testGetForm(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $result = new ProcessResult($this->form, $data);

        $formWithData = $result->getForm();

        $this->assertNotSame($this->form, $formWithData);
        $this->assertSame($data, $formWithData->getData()?->toArray());
    }

    public function testGetFormWithProcessedData(): void
    {
        $processedData = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $result = new ProcessResult($this->form, $processedData);

        $formWithData = $result->getForm();

        $this->assertNotSame($this->form, $formWithData);
        $this->assertSame($processedData, $formWithData->getData()?->toArray());
    }

    public function testGetFormPreservesInvalidData(): void
    {
        $invalidData = [
            'name' => 'Jo', // Too short
            'email' => 'invalid-email', // Invalid format
            'age' => 'not-a-number', // Invalid type
        ];
        $result = new ProcessResult($this->form, $invalidData, [
            'name' => ['Name must be at least 3 characters'],
            'email' => ['Invalid email format'],
            'age' => ['Age must be a number'],
        ], false);

        $formWithData = $result->getForm();

        $this->assertSame($invalidData, $formWithData->getData()?->toArray());
        $this->assertSame(
            ['Name must be at least 3 characters'],
            $formWithData->getField('name')?->getErrors()
        );
        $this->assertSame(
            ['Invalid email format'],
            $formWithData->getField('email')?->getErrors()
        );
        $this->assertSame(
            ['Age must be a number'],
            $formWithData->getField('age')?->getErrors()
        );
    }

    public function testGetFormPreservesMixedValidAndInvalidData(): void
    {
        $mixedData = [
            'name' => 'John Doe', // Valid
            'email' => 'invalid-email', // Invalid
            'age' => 25, // Valid
            'phone' => '123', // Invalid (too short)
        ];
        $result = new ProcessResult($this->form, $mixedData, [
            'email' => ['Invalid email format'],
            'phone' => ['Phone number is too short'],
        ], false);

        $formWithData = $result->getForm();

        $this->assertSame($mixedData, $formWithData->getData()?->toArray());
        $this->assertEmpty($formWithData->getField('name')?->getErrors());
        $this->assertSame(
            ['Invalid email format'],
            $formWithData->getField('email')?->getErrors()
        );
        $this->assertEmpty($formWithData->getField('age')?->getErrors());
        $this->assertSame(
            ['Phone number is too short'],
            $formWithData->getField('phone')?->getErrors()
        );
    }

    public function testGetFormPreservesEmptyAndNullValues(): void
    {
        $dataWithEmptyValues = [
            'name' => '', // Empty string
            'email' => null, // Null value
            'age' => 0, // Zero value
            'active' => false, // Boolean false
        ];
        $result = new ProcessResult($this->form, $dataWithEmptyValues, [
            'name' => ['Name is required'],
            'email' => ['Email is required'],
        ], false);

        $formWithData = $result->getForm();

        $this->assertSame($dataWithEmptyValues, $formWithData->getData()?->toArray());
        $this->assertSame(
            ['Name is required'],
            $formWithData->getField('name')?->getErrors()
        );
        $this->assertSame(
            ['Email is required'],
            $formWithData->getField('email')?->getErrors()
        );
    }

    /**
     * Builds a minimal real Form covering every field name exercised by the
     * getForm() tests, so getField()/withData() run against production code
     * instead of a mock.
     */
    private function makeForm(): Form
    {
        $schema = FormSchema::fromArray([
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'email' => ['type' => 'string'],
                'age' => ['type' => 'integer'],
                'phone' => ['type' => 'string'],
                'active' => ['type' => 'boolean'],
            ],
        ]);

        $uiSchema = VerticalLayout::fromArray([
            'type' => 'VerticalLayout',
            'elements' => [
                ['type' => 'Control', 'label' => 'Name', 'scope' => '#/properties/name'],
                ['type' => 'Control', 'label' => 'Email', 'scope' => '#/properties/email'],
                ['type' => 'Control', 'label' => 'Age', 'scope' => '#/properties/age'],
                ['type' => 'Control', 'label' => 'Phone', 'scope' => '#/properties/phone'],
                ['type' => 'Control', 'label' => 'Active', 'scope' => '#/properties/active'],
            ],
        ]);

        return new Form($schema, $uiSchema);
    }
}
