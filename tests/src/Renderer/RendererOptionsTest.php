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

use Derafu\Form\Contract\UiSchema\UiSchemaElementInterface;
use Derafu\Form\Factory\ChoicesShorthand;
use Derafu\Form\Factory\FormFactory;
use Derafu\Form\Factory\FormRendererFactory;
use Derafu\Form\FormDefinition;
use Derafu\Form\Renderer\Widget\TextareaWidgetRenderer;
use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Form\Type\TypeProvider;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use Derafu\Form\UiSchema\Group;
use Derafu\Form\UiSchema\Label;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * The options of the renderers are read with the type that each one has: one that
 * is not of that type is an error of the definition, and it says which one.
 */
#[CoversClass(\Derafu\Form\Renderer\Widget\InputWidgetRenderer::class)]
#[CoversClass(TextareaWidgetRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Widget\SelectWidgetRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Widget\SliderWidgetRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Element\ControlRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Element\LabelRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Element\GroupRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Widget\CollectionWidgetRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Support\InputActionResolver::class)]
#[CoversClass(\Derafu\Form\Renderer\FormRenderer::class)]
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
#[UsesClass(\Derafu\Form\Renderer\FormTwigExtension::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererProvider::class)]
#[UsesClass(\Derafu\Form\Renderer\WidgetRendererRegistry::class)]
#[UsesClass(\Derafu\Form\Renderer\Widget\RadioWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Rules\FormRules::class)]
#[UsesClass(\Derafu\Form\Schema\FormSchema::class)]
#[UsesTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[UsesClass(\Derafu\Form\Schema\StringSchema::class)]
#[UsesClass(\Derafu\Form\Schema\IntegerSchema::class)]
#[UsesClass(\Derafu\Form\Schema\ArraySchema::class)]
#[UsesClass(\Derafu\Form\Schema\ObjectSchema::class)]
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
#[UsesClass(\Derafu\Form\UiSchema\Group::class)]
#[UsesClass(\Derafu\Form\UiSchema\Label::class)]
#[UsesClass(\Derafu\Form\Widget\Widget::class)]
#[UsesClass(\Derafu\Form\Widget\WidgetFactory::class)]
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(ChoicesShorthand::class)]
#[UsesClass(FormDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Renderer\Support\RequiredField::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Options\FormAttributes::class)]
final class RendererOptionsTest extends TestCase
{
    /**
     * Renders the row of a field.
     *
     * @param array<string, mixed> $property
     * @param array<string, mixed> $control
     * @param array<string, mixed> $options
     */
    private function row(array $property, array $control, array $options = []): string
    {
        $form = (new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider()))))->create([
            'schema' => ['type' => 'object', 'properties' => ['field' => $property + ['type' => 'string']]],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/field'] + $control],
            ],
        ]);

        $field = $form->getField('field');
        $this->assertNotNull($field);

        return html_entity_decode(
            FormRendererFactory::create()->renderElement($field->getControl(), $form, $options)
        );
    }

    /**
     * @return array<string, array{array<string, mixed>, array<string, mixed>, array<string, mixed>, string}>
     */
    public static function wrongOptionsProvider(): array
    {
        return [
            'widget_class that is not a string' => [
                [], [], ['widget_class' => ['a', 'b']],
                'The key "widget_class" must be of type string, array given.',
            ],
            'attr that is not an object' => [
                [], [], ['attr' => 'x'],
                'The key "attr" must be of type map<bool|float|int|string>, string given.',
            ],
            'attr with a value that is not scalar' => [
                [], [], ['attr' => ['data-x' => ['a']]],
                'The key "attr.data-x" must be of type bool|float|int|string, array given.',
            ],
            'rows that are not an integer' => [
                [], ['options' => ['multi' => true]], ['rows' => 'many'],
                'The key "rows" must be of type int, string given.',
            ],
            'floating_labels that is not a boolean' => [
                [], [], ['floating_labels' => 'yes'],
                'The key "floating_labels" must be of type bool, string given.',
            ],
            'label that is not a string' => [
                [], [], ['label' => 5],
                'The key "label" must be of type string, int given.',
            ],
            'size that is not a number' => [
                ['enum' => ['a', 'b']], [], ['size' => 'big'],
                'The value at "$" must be of type float|int, string given.',
            ],
            'step that is not a number' => [
                ['type' => 'integer'], ['options' => ['type' => 'slider', 'step' => 'big']], [],
                'The value at "$" must be of type float|int, string given.',
            ],
            'actions with a length that is not an integer' => [
                [], ['options' => ['type' => 'password', 'actions' => [['type' => 'generate-password', 'length' => 'long']]]], [],
                'The key "length" must be of type int, string given.',
            ],
            'detail that is not an object' => [
                ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['a' => ['type' => 'string']]]],
                ['options' => ['detail' => 'x']], [],
                'The key "detail" must be of type map, string given.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $property
     * @param array<string, mixed> $control
     * @param array<string, mixed> $options
     */
    #[Test]
    #[DataProvider('wrongOptionsProvider')]
    public function shouldRejectAnOptionThatIsNotWhatIsExpected(
        array $property,
        array $control,
        array $options,
        string $message
    ): void {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->row($property, $control, $options);
    }

    #[Test]
    public function shouldAcceptTheOptionsThatAreWhatIsExpected(): void
    {
        $row = $this->row(
            [],
            [],
            ['widget_class' => 'extra-class', 'attr' => ['data-x' => 'y', 'tabindex' => 3, 'autofocus' => true]]
        );

        $this->assertStringContainsString('extra-class', $row);
        $this->assertStringContainsString('data-x="y"', $row);
        $this->assertStringContainsString('tabindex="3"', $row);
    }

    #[Test]
    public function shouldAcceptASizeThatIsANumberOrANumericString(): void
    {
        $this->assertStringContainsString('size="3"', $this->row(['enum' => ['a', 'b']], [], ['size' => 3]));
        $this->assertStringContainsString('size="4"', $this->row(['enum' => ['a', 'b']], [], ['size' => '4']));
    }

    #[Test]
    public function shouldSetThePlaceholderOfAFloatingLabelFromTheLabelOfTheControl(): void
    {
        $row = $this->row([], ['label' => 'Your name'], ['floating_labels' => true]);

        $this->assertStringContainsString('placeholder="Your name"', $row);
    }

    /**
     * Renders an element that is not a control, inside a form with one field.
     *
     * @param array<string, mixed> $options
     */
    private function element(UiSchemaElementInterface $element, array $options = []): string
    {
        $form = (new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider()))))->create([
            'schema' => ['type' => 'object', 'properties' => ['field' => ['type' => 'string']]],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/field']],
            ],
        ]);

        return FormRendererFactory::create()->renderElement($element, $form, $options);
    }

    #[Test]
    public function shouldRenderALabelAsAHeadingOrWithItsOwnClass(): void
    {
        $this->assertSame(
            '<h2 class="form-label">Hi</h2>',
            $this->element(new Label('Hi'), ['heading' => true, 'heading_level' => 2])
        );
        $this->assertStringContainsString(
            'class="custom"',
            $this->element(new Label('Hi'), ['label_attr' => ['class' => 'custom']])
        );
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function wrongLabelOptionsProvider(): array
    {
        return [
            'heading that is not a boolean' => [['heading' => 'yes'], 'The key "heading" must be of type bool, string given.'],
            'heading_level that is not an integer' => [['heading_level' => 'big'], 'The key "heading_level" must be of type int, string given.'],
            'label_attr with a value that is not scalar' => [['label_attr' => ['class' => ['a']]], 'The key "label_attr.class" must be of type bool|float|int|string, array given.'],
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Test]
    #[DataProvider('wrongLabelOptionsProvider')]
    public function shouldRejectALabelOptionThatIsNotWhatIsExpected(array $options, string $message): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->element(new Label('Hi'), $options);
    }

    #[Test]
    public function shouldRejectOptionsOfTheElementsOfAGroupThatAreNotAList(): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The key "elements_options" must be of type list<map>, string given.');

        $this->element(new Group('Group', [new Label('Hi')]), ['elements_options' => 'x']);
    }

    #[Test]
    public function shouldGiveTheOptionsOfEachElementToTheElementOfAGroup(): void
    {
        $html = $this->element(
            new Group('Group', [new Label('A'), new Label('B')]),
            ['elements_options' => [[], ['label_attr' => ['class' => 'second']]]]
        );

        $this->assertSame(1, substr_count($html, 'class="second"'));
    }
}
