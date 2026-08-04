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

use Derafu\Form\Contract\UiSchema\ControlInterface;
use Derafu\Form\Data\FormData;
use Derafu\Form\Factory\FormRendererFactory;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Form;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Test for form rendering value inconsistency between form_rest() and form_element().
 */
#[CoversClass(\Derafu\Form\Form::class)]
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(\Derafu\Form\FormField::class)]
#[CoversClass(\Derafu\Form\Renderer\FormRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Element\ControlRenderer::class)]
#[CoversClass(\Derafu\Form\Abstract\AbstractPropertySchema::class)]
#[CoversClass(\Derafu\Form\Abstract\AbstractUiSchemaElement::class)]
#[CoversClass(\Derafu\Form\Data\FormData::class)]
#[CoversClass(\Derafu\Form\Factory\UiSchemaElementFactory::class)]
#[CoversClass(\Derafu\Form\Schema\FormSchema::class)]
#[CoversTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[CoversClass(\Derafu\Form\Schema\StringSchema::class)]
#[CoversClass(\Derafu\Form\UiSchema\Control::class)]
#[CoversClass(\Derafu\Form\UiSchema\VerticalLayout::class)]
#[CoversClass(WidgetFactory::class)]
#[CoversClass(Widget::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(FormRendererFactory::class)]
#[UsesClass(\Derafu\Form\Renderer\ElementRendererProvider::class)]
#[UsesClass(\Derafu\Form\Renderer\ElementRendererRegistry::class)]
#[UsesClass(\Derafu\Form\Renderer\FormTwigExtension::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererProvider::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererRegistry::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\InputWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\RadioWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\SliderWidgetRenderer::class)]
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

        $form = new Form($schema, $uiSchema, data: $data);

        // Use the real, fully wired renderer (Twig + widget/element registries)
        // instead of a mock, so the test exercises the actual rendering pipeline.
        $renderer = FormRendererFactory::create();

        // form_rest() behavior: renders every field not yet individually rendered.
        $restHtml = $renderer->renderRest($form);

        // form_element() behavior: renders each element on demand.
        $elementValues = [];
        foreach ($uiSchema->getElements() as $element) {
            assert($element instanceof ControlInterface);
            $elementHtml = $renderer->renderElement($element, $form);
            $propertyName = $element->getPropertyName();
            $elementValues[$propertyName] = $this->extractInputValue($elementHtml, $propertyName);
        }

        $restValues = [
            'name' => $this->extractInputValue($restHtml, 'name'),
            'email' => $this->extractInputValue($restHtml, 'email'),
        ];

        // Now both should use the same data source and have the same values.
        $this->assertSame(
            $elementValues,
            $restValues,
            'form_rest() and form_element() should use the same data source and have the same values'
        );

        // Both should contain the correct default values.
        $this->assertSame('John Doe', $elementValues['name']);
        $this->assertSame('john@example.com', $elementValues['email']);

        $this->assertSame('John Doe', $restValues['name']);
        $this->assertSame('john@example.com', $restValues['email']);
    }

    /**
     * Extracts the `value` attribute of the `<input id="{name}_field" ...>`
     * tag rendered for a given property, out of a chunk of rendered HTML.
     */
    private function extractInputValue(string $html, string $propertyName): ?string
    {
        if (!preg_match('/<input\b[^>]*\bid="' . preg_quote($propertyName, '/') . '_field"[^>]*>/', $html, $tag)) {
            return null;
        }

        if (!preg_match('/\bvalue="([^"]*)"/', $tag[0], $value)) {
            return null;
        }

        return html_entity_decode($value[1], ENT_QUOTES | ENT_HTML5);
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
        $form = new Form($schema, $uiSchema, data: $data);

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
