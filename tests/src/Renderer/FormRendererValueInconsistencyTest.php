<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Tests\Renderer;

use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Data\FormData;
use Derafu\Form\Form;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test for form rendering value inconsistency between form_rest() and form_element().
 */
#[CoversClass(\Derafu\Form\Form::class)]
#[CoversClass(\Derafu\Form\FormField::class)]
#[CoversClass(\Derafu\Form\Renderer\FormRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Element\ControlRenderer::class)]
#[CoversClass(\Derafu\Form\Abstract\AbstractPropertySchema::class)]
#[CoversClass(\Derafu\Form\Abstract\AbstractUiSchemaElement::class)]
#[CoversClass(\Derafu\Form\Data\FormData::class)]
#[CoversClass(\Derafu\Form\Factory\UiSchemaElementFactory::class)]
#[CoversClass(\Derafu\Form\Schema\FormSchema::class)]
#[CoversClass(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[CoversClass(\Derafu\Form\Schema\StringSchema::class)]
#[CoversClass(\Derafu\Form\UiSchema\Control::class)]
#[CoversClass(\Derafu\Form\UiSchema\VerticalLayout::class)]
#[CoversClass(WidgetFactory::class)]
#[CoversClass(Widget::class)]
final class FormRendererValueInconsistencyTest extends TestCase
{
    /**
     * Test that demonstrates the inconsistency between form_rest() and form_element().
     *
     * The issue is that form_rest() uses FormField objects that may not have
     * the correct data initialized, while form_element() gets data directly
     * from the form's data container.
     */
    public function testFormRestAndFormElementValueInconsistency(): void
    {
        // Create a simple form with default data
        $schema = FormSchema::fromArray([
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'title' => 'Your Name',
                ],
                'email' => [
                    'type' => 'string',
                    'title' => 'Your Email',
                ],
            ],
        ]);

        $uiSchema = VerticalLayout::fromArray([
            'type' => 'VerticalLayout',
            'elements' => [
                [
                    'type' => 'Control',
                    'label' => 'Your Name',
                    'scope' => '#/properties/name',
                ],
                [
                    'type' => 'Control',
                    'label' => 'Your Email',
                    'scope' => '#/properties/email',
                ],
            ],
        ]);

        $data = new FormData([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $form = new Form($schema, $uiSchema, $data);

        // Mock the form renderer to capture what values are being used
        $mockRenderer = $this->createMock(FormRendererInterface::class);

        // Capture the values used in renderRest (form_rest())
        $restValues = [];
        $mockRenderer->method('renderRest')
            ->willReturnCallback(function (FormInterface $form) use (&$restValues) {
                $fields = $form->getFields();
                foreach ($fields as $field) {
                    $restValues[$field->getProperty()->getName()] = $field->getData();
                }
                return 'rest_html';
            });

        // Capture the values used in renderElement (form_element())
        $elementValues = [];
        $mockRenderer->method('renderElement')
            ->willReturnCallback(function ($element, FormInterface $form) use (&$elementValues) {
                $scope = $element->getScope();
                $propertyName = str_replace('#/properties/', '', $scope);
                $elementValues[$propertyName] = $form->getData()?->get($propertyName);
                return 'element_html';
            });

        // Simulate form_rest() behavior
        $mockRenderer->renderRest($form);

        // Simulate form_element() behavior for each element
        foreach ($uiSchema->getElements() as $element) {
            $mockRenderer->renderElement($element, $form);
        }

        // Now both should use the same data source and have the same values
        $this->assertSame(
            $elementValues,
            $restValues,
            'form_rest() and form_element() should use the same data source and have the same values'
        );

        // Both should contain the correct default values
        $this->assertSame('John Doe', $elementValues['name']);
        $this->assertSame('john@example.com', $elementValues['email']);

        $this->assertSame('John Doe', $restValues['name']);
        $this->assertSame('john@example.com', $restValues['email']);
    }

    /**
     * Test that shows the root cause: FormField objects don't get data initialized.
     */
    public function testFormFieldDataNotInitialized(): void
    {
        // Create a form with data
        $schema = FormSchema::fromArray([
            'type' => 'object',
            'properties' => [
                'name' => [
                    'type' => 'string',
                    'title' => 'Your Name',
                ],
            ],
        ]);

        $uiSchema = VerticalLayout::fromArray([
            'type' => 'VerticalLayout',
            'elements' => [
                [
                    'type' => 'Control',
                    'label' => 'Your Name',
                    'scope' => '#/properties/name',
                ],
            ],
        ]);

        $data = new FormData(['name' => 'John Doe']);
        $form = new Form($schema, $uiSchema, $data);

        // Get the fields (this is what form_rest() does)
        $fields = $form->getFields();
        $field = $fields['name'];

        // The field should now have the data from the form
        $this->assertSame(
            'John Doe',
            $field->getData(),
            'FormField objects should be initialized with data from the form'
        );

        // The form data also contains the value
        $this->assertSame(
            'John Doe',
            $form->getData()?->get('name'),
            'Form data should contain the default value'
        );
    }
}
