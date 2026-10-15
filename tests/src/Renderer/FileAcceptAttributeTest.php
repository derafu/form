<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Renderer;

use Derafu\Form\Factory\FormFactory;
use Derafu\Form\Factory\FormRendererFactory;
use Derafu\Form\Renderer\Widget\InputWidgetRenderer;
use Derafu\Form\Type\TypeProvider;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * A file input offers the files that the control says (`accept`).
 *
 * The control of an image restricts the files to images; the option `accept` of
 * the control sets which ones (extensions or MIME types) and replaces that.
 */
#[CoversClass(InputWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractPropertySchema::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractType::class)]
#[UsesClass(\Derafu\Form\Abstract\AbstractUiSchemaElement::class)]
#[UsesClass(\Derafu\Form\Data\FormData::class)]
#[UsesClass(\Derafu\Form\Factory\FormFactory::class)]
#[UsesClass(\Derafu\Form\Factory\FormRendererFactory::class)]
#[UsesClass(\Derafu\Form\Factory\FormUiSchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\PropertySchemaFactory::class)]
#[UsesClass(\Derafu\Form\Factory\UiSchemaElementFactory::class)]
#[UsesClass(\Derafu\Form\Form::class)]
#[UsesClass(\Derafu\Form\FormField::class)]
#[UsesClass(\Derafu\Form\Options\FormOptions::class)]
#[UsesClass(\Derafu\Form\Renderer\ElementRendererProvider::class)]
#[UsesClass(\Derafu\Form\Renderer\ElementRendererRegistry::class)]
#[UsesClass(\Derafu\Form\Renderer\Element\ControlRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\FormRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\FormTwigExtension::class)]
#[UsesClass(\Derafu\Form\Renderer\Support\InputActionResolver::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererProvider::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererRegistry::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\RadioWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\SliderWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Rules\FormRules::class)]
#[UsesClass(\Derafu\Form\Schema\FormSchema::class)]
#[UsesTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[UsesClass(\Derafu\Form\Schema\StringSchema::class)]
#[UsesClass(\Derafu\Form\Type\BooleanType::class)]
#[UsesClass(\Derafu\Form\Type\ChoiceType::class)]
#[UsesClass(\Derafu\Form\Type\ColorType::class)]
#[UsesClass(\Derafu\Form\Type\DateType::class)]
#[UsesClass(\Derafu\Form\Type\DatetimeType::class)]
#[UsesClass(\Derafu\Form\Type\EmailType::class)]
#[UsesClass(\Derafu\Form\Type\FloatType::class)]
#[UsesClass(\Derafu\Form\Type\IntegerType::class)]
#[UsesClass(\Derafu\Form\Type\Ipv4Type::class)]
#[UsesClass(\Derafu\Form\Type\Ipv6Type::class)]
#[UsesClass(\Derafu\Form\Type\MonthType::class)]
#[UsesClass(\Derafu\Form\Type\TextType::class)]
#[UsesClass(\Derafu\Form\Type\TextareaType::class)]
#[UsesClass(\Derafu\Form\Type\TimeType::class)]
#[UsesClass(\Derafu\Form\Type\TypeProvider::class)]
#[UsesClass(\Derafu\Form\Type\TypeRegistry::class)]
#[UsesClass(\Derafu\Form\Type\TypeResolver::class)]
#[UsesClass(\Derafu\Form\Type\UriType::class)]
#[UsesClass(\Derafu\Form\Type\UrlType::class)]
#[UsesClass(\Derafu\Form\Type\UuidType::class)]
#[UsesClass(\Derafu\Form\Type\WeekType::class)]
#[UsesClass(\Derafu\Form\UiSchema\Control::class)]
#[UsesClass(\Derafu\Form\UiSchema\VerticalLayout::class)]
#[UsesClass(\Derafu\Form\Widget\Widget::class)]
#[UsesClass(\Derafu\Form\Widget\WidgetFactory::class)]
final class FileAcceptAttributeTest extends TestCase
{
    /**
     * Renders the input of a file control with the options.
     *
     * @param array<string, mixed> $options Options of the control.
     */
    private function input(array $options = [], string $control = 'file'): string
    {
        $form = (new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider()))))->create([
            'schema' => [
                'type' => 'object',
                'properties' => ['document' => ['type' => 'string', 'title' => 'Document']],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [[
                    'type' => 'Control',
                    'scope' => '#/properties/document',
                    'options' => ['type' => $control] + $options,
                ]],
            ],
        ]);

        $field = $form->getField('document');
        $this->assertNotNull($field);

        // The type of the control is also an option of the rendering, like when
        // the whole form is rendered (see ControlRenderer).
        return html_entity_decode(FormRendererFactory::create()->renderRow($field, ['type' => $control]));
    }

    #[Test]
    public function aFileInputWithoutTheOptionHasNoAccept(): void
    {
        $this->assertStringContainsString('type="file"', $this->input());
        $this->assertStringNotContainsString('accept=', $this->input());
    }

    #[Test]
    public function theOptionIsTheAcceptOfTheInput(): void
    {
        $this->assertStringContainsString('accept=".xml"', $this->input(['accept' => '.xml']));
        $this->assertStringContainsString('accept=".p12,.pfx"', $this->input(['accept' => '.p12,.pfx']));
        $this->assertStringContainsString('accept="application/pdf"', $this->input(['accept' => 'application/pdf']));
    }

    #[Test]
    public function theImageControlOnlyAcceptsImages(): void
    {
        $this->assertStringContainsString('accept="image/*"', $this->input([], 'image'));
    }

    #[Test]
    public function theOptionReplacesTheOneOfTheImageControl(): void
    {
        $input = $this->input(['accept' => '.png,.jpg'], 'image');

        $this->assertStringContainsString('accept=".png,.jpg"', $input);
        $this->assertStringNotContainsString('image/*', $input);
    }

    #[Test]
    public function theOptionIsNotAppliedToOtherInputs(): void
    {
        $this->assertStringNotContainsString('accept=', $this->input(['accept' => '.xml'], 'text'));
    }
}
