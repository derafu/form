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

use Derafu\Form\Data\FormData;
use Derafu\Form\Factory\FormFactory;
use Derafu\Form\Factory\FormRendererFactory;
use Derafu\Form\Renderer\FormRenderer;
use Derafu\Form\Type\TypeProvider;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * The row of a field has the marks of the errors only when the field has errors,
 * which is what the field says and not what the HTML of its errors is (it is
 * never empty: it has a line break even when there is nothing to show).
 */
#[CoversClass(FormRenderer::class)]
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
#[UsesClass(\Derafu\Form\Renderer\FormTwigExtension::class)]
#[UsesClass(\Derafu\Form\Renderer\Support\InputActionResolver::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererProvider::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererRegistry::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\InputWidgetRenderer::class)]
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
final class FormRowErrorsTest extends TestCase
{
    private function row(?array $errors): string
    {
        $form = (new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider()))))->create([
            'schema' => [
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string', 'title' => 'Your name']],
            ],
        ]);

        if ($errors !== null) {
            $form = $form->withData(FormData::fromArray(['name' => 'x']), ['name' => $errors]);
        }

        $field = $form->getField('name');
        $this->assertNotNull($field);

        return FormRendererFactory::create()->renderRow($field);
    }

    #[Test]
    public function aFieldWithoutErrorsHasNoMarksOfErrors(): void
    {
        $row = $this->row(null);

        $this->assertStringNotContainsString('has-error', $row);
        $this->assertStringNotContainsString('invalid-feedback', $row);
        $this->assertStringNotContainsString('has-validation', $row);
    }

    #[Test]
    public function aFieldWithErrorsHasTheMarksAndTheErrors(): void
    {
        $row = $this->row(['The name is too short.', 'The name is not valid.']);

        $this->assertStringContainsString('has-error', $row);
        $this->assertStringContainsString('invalid-feedback d-block', $row);
        $this->assertStringContainsString('The name is too short.', $row);
        $this->assertStringContainsString('The name is not valid.', $row);
    }
}
