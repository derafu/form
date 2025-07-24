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

use Derafu\ExamplesForm\Example;
use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Contract\Renderer\FormRendererInterface;
use Derafu\Form\Data\FormData;
use Derafu\Form\Factory\FormFactory;
use Derafu\Form\Factory\FormRendererFactory;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormAttributes;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Renderer\Element\CategorizationRenderer;
use Derafu\Form\Renderer\Element\ControlRenderer;
use Derafu\Form\Renderer\Element\GroupRenderer;
use Derafu\Form\Renderer\Element\HorizontalLayoutRenderer;
use Derafu\Form\Renderer\Element\LabelRenderer;
use Derafu\Form\Renderer\Element\VerticalLayoutRenderer;
use Derafu\Form\Renderer\ElementRendererProvider;
use Derafu\Form\Renderer\ElementRendererRegistry;
use Derafu\Form\Renderer\FormRenderer;
use Derafu\Form\Renderer\FormTwigExtension;
use Derafu\Form\Renderer\Widget\InputWidgetRenderer;
use Derafu\Form\Renderer\Widget\RadioWidgetRenderer;
use Derafu\Form\Renderer\WidgetRendererProvider;
use Derafu\Form\Renderer\WidgetRendererRegistry;
use Derafu\Form\Schema\ArraySchema;
use Derafu\Form\Schema\BooleanSchema;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\IntegerSchema;
use Derafu\Form\Schema\NumberSchema;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\Type\BooleanType;
use Derafu\Form\Type\ChoiceType;
use Derafu\Form\Type\ColorType;
use Derafu\Form\Type\DatetimeType;
use Derafu\Form\Type\DateType;
use Derafu\Form\Type\EmailType;
use Derafu\Form\Type\FloatType;
use Derafu\Form\Type\IntegerType;
use Derafu\Form\Type\Ipv4Type;
use Derafu\Form\Type\Ipv6Type;
use Derafu\Form\Type\MonthType;
use Derafu\Form\Type\TextareaType;
use Derafu\Form\Type\TextType;
use Derafu\Form\Type\TimeType;
use Derafu\Form\Type\TypeProvider;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use Derafu\Form\Type\UriType;
use Derafu\Form\Type\UrlType;
use Derafu\Form\Type\UuidType;
use Derafu\Form\Type\WeekType;
use Derafu\Form\UiSchema\Categorization;
use Derafu\Form\UiSchema\Category;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\Group;
use Derafu\Form\UiSchema\HorizontalLayout;
use Derafu\Form\UiSchema\Label;
use Derafu\Form\UiSchema\VerticalLayout;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(FormFactory::class)]
#[CoversClass(Form::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(FormAttributes::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormSchema::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(ArraySchema::class)]
#[CoversClass(BooleanSchema::class)]
#[CoversClass(IntegerSchema::class)]
#[CoversClass(NumberSchema::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(Categorization::class)]
#[CoversClass(Category::class)]
#[CoversClass(Control::class)]
#[CoversClass(Group::class)]
#[CoversClass(HorizontalLayout::class)]
#[CoversClass(Label::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(FormData::class)]
#[CoversClass(FormRendererFactory::class)]
#[CoversClass(FormRenderer::class)]
#[CoversClass(FormRendererFactory::class)]
#[CoversClass(ElementRendererRegistry::class)]
#[CoversClass(WidgetRendererRegistry::class)]
#[CoversClass(ElementRendererProvider::class)]
#[CoversClass(CategorizationRenderer::class)]
#[CoversClass(ControlRenderer::class)]
#[CoversClass(GroupRenderer::class)]
#[CoversClass(HorizontalLayoutRenderer::class)]
#[CoversClass(LabelRenderer::class)]
#[CoversClass(VerticalLayoutRenderer::class)]
#[CoversClass(FormTwigExtension::class)]
#[CoversClass(WidgetRendererProvider::class)]
#[CoversClass(InputWidgetRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Widget\TextareaWidgetRenderer::class)]
#[CoversClass(BooleanType::class)]
#[CoversClass(ChoiceType::class)]
#[CoversClass(ColorType::class)]
#[CoversClass(DateType::class)]
#[CoversClass(DatetimeType::class)]
#[CoversClass(EmailType::class)]
#[CoversClass(FloatType::class)]
#[CoversClass(IntegerType::class)]
#[CoversClass(Ipv4Type::class)]
#[CoversClass(Ipv6Type::class)]
#[CoversClass(MonthType::class)]
#[CoversClass(RadioWidgetRenderer::class)]
#[CoversClass(TextType::class)]
#[CoversClass(TextareaType::class)]
#[CoversClass(TimeType::class)]
#[CoversClass(TypeProvider::class)]
#[CoversClass(TypeRegistry::class)]
#[CoversClass(TypeResolver::class)]
#[CoversClass(UriType::class)]
#[CoversClass(UrlType::class)]
#[CoversClass(UuidType::class)]
#[CoversClass(WeekType::class)]

final class ExamplesTest extends TestCase
{
    private FormRendererInterface $renderer;

    protected function setUp(): void
    {
        $this->renderer = FormRendererFactory::create();

        Example::setFormFactory(
            new FormFactory(
                new TypeResolver(new TypeRegistry(new TypeProvider()))
            )
        );
    }

    #[DataProvider('provideExamples')]
    public function testCreateExamples(Example $example): void
    {
        $jsonForms = $example->getForm()->toJsonFormDefinition();
        $this->assertNotEmpty($jsonForms);

        $form = $example->getForm();
        $this->assertInstanceOf(FormInterface::class, $form);
        $this->assertNotEmpty($form->toArray());

        $html = $this->renderer->render($form);
        $this->assertNotEmpty($html);
    }

    public static function provideExamples(): array
    {
        $examples = [];

        foreach (Example::all() as $example) {
            $examples[$example->getId()] = [$example];
        }

        return $examples;
    }
}
