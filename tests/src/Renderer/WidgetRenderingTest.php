<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm\Renderer;

use Derafu\Form\Factory\ChoicesShorthand;
use Derafu\Form\Factory\FormFactory;
use Derafu\Form\Factory\FormRendererFactory;
use Derafu\Form\FormDefinition;
use Derafu\Form\Renderer\Widget\TextareaWidgetRenderer;
use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Form\Type\TypeProvider;
use Derafu\Form\Type\TypeRegistry;
use Derafu\Form\Type\TypeResolver;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;

/**
 * What the renderers of the widgets and of the elements write for the values
 * that the definition of a field can have.
 */
#[CoversClass(\Derafu\Form\Renderer\Widget\InputWidgetRenderer::class)]
#[CoversClass(TextareaWidgetRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Widget\SelectWidgetRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Widget\CheckboxWidgetRenderer::class)]
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
#[CoversClass(\Derafu\Form\Renderer\Widget\RadioWidgetRenderer::class)]
#[UsesClass(\Derafu\Form\Rules\FormRules::class)]
#[UsesClass(\Derafu\Form\Schema\FormSchema::class)]
#[UsesTrait(\Derafu\Form\Schema\ObjectSchemaTrait::class)]
#[UsesClass(\Derafu\Form\Schema\StringSchema::class)]
#[UsesClass(\Derafu\Form\Schema\IntegerSchema::class)]
#[UsesClass(\Derafu\Form\Schema\NumberSchema::class)]
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
#[UsesClass(\Derafu\Form\Options\FormAttributes::class)]
#[UsesClass(\Derafu\Form\Processor\UiSchemaRuleEvaluator::class)]
#[CoversClass(\Derafu\Form\Renderer\Element\VerticalLayoutRenderer::class)]
#[CoversClass(\Derafu\Form\Renderer\Element\HorizontalLayoutRenderer::class)]
#[UsesClass(\Derafu\Form\UiSchema\ConditionSchema::class)]
#[UsesClass(\Derafu\Form\UiSchema\UiSchemaCompositeCondition::class)]
#[UsesClass(\Derafu\Form\UiSchema\UiSchemaCondition::class)]
#[UsesClass(\Derafu\Form\UiSchema\UiSchemaRule::class)]
#[UsesClass(\Derafu\Form\UiSchema\HorizontalLayout::class)]
final class WidgetRenderingTest extends TestCase
{
    /**
     * Renders the row of a field.
     *
     * @param array<string, mixed> $property
     * @param array<string, mixed> $control
     * @param array<string, mixed> $options
     * @param array<string, mixed> $form Keys of the definition of the form to add.
     */
    private function row(
        array $property,
        array $control,
        array $options = [],
        array $form = [],
        bool $required = false
    ): string {
        $definition = array_replace_recursive([
            'schema' => ['type' => 'object', 'properties' => ['field' => $property + ['type' => 'string']]],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/field'] + $control],
            ],
        ], $form);
        $form = (new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider()))))->create($definition);

        $field = $form->getField('field');
        $this->assertNotNull($field);
        $field->setRequired($required);

        return html_entity_decode(
            FormRendererFactory::create()->renderElement($field->getControl(), $form, $options)
        );
    }

    #[Test]
    public function shouldRenderASelectWithItsStateAndItsExtraClass(): void
    {
        $html = $this->row(
            ['enum' => ['a', 'b']],
            ['options' => ['placeholder' => 'Pick one']],
            ['widget_class' => 'extra'],
            ['errors' => ['field' => ['Bad']]],
            true
        );

        $this->assertStringContainsString('class="form-select is-invalid extra"', $html);
        $this->assertStringContainsString('required="required"', $html);
        $this->assertStringContainsString('data-placeholder="Pick one"', $html);
    }

    #[Test]
    public function shouldRenderAMultipleSelectWithTheLimitsOfTheItems(): void
    {
        $html = $this->row(
            ['type' => 'array', 'items' => ['enum' => ['a', 'b']], 'minItems' => 1, 'maxItems' => 2],
            ['options' => ['widget' => 'select']]
        );

        $this->assertStringContainsString('name="field[]"', $html);
        $this->assertStringContainsString('multiple="multiple"', $html);
        $this->assertStringContainsString('data-min-items="1"', $html);
        $this->assertStringContainsString('data-max-items="2"', $html);
    }

    #[Test]
    public function shouldRenderARadioWithItsStateAndTheValueAsJson(): void
    {
        $html = $this->row(
            ['enum' => ['a', 'b']],
            ['options' => ['widget' => 'radio', 'placeholder' => 'Pick one']],
            ['has_errors' => true, 'widget_class' => 'extra'],
            [],
            true
        );

        $this->assertStringContainsString('class="form-check-input is-invalid extra"', $html);
        $this->assertStringContainsString('required="required"', $html);

        $html = $this->row(['enum' => ['a', 'b']], ['options' => ['widget' => 'radio']], ['value' => ['a']]);
        $this->assertStringContainsString('class="form-check-input"', $html);

        $html = $this->row(['enum' => ['a', 'b']], ['options' => ['widget' => 'radio']], ['value' => 'a']);
        $this->assertStringContainsString('class="form-check-input"', $html);
    }

    #[Test]
    public function shouldRenderASliderWithTheLimitsOfTheSchemaAndOfTheControl(): void
    {
        $html = $this->row(
            ['type' => 'integer', 'minimum' => 10, 'maximum' => 20, 'multipleOf' => 5],
            ['options' => ['widget' => 'slider']],
            ['has_errors' => true, 'widget_class' => 'extra', 'value' => 15],
            [],
            true
        );

        $this->assertStringContainsString('min="10"', $html);
        $this->assertStringContainsString('max="20"', $html);
        $this->assertStringContainsString('step="5"', $html);
        $this->assertStringContainsString('value="15"', $html);
        $this->assertStringContainsString('required="required"', $html);

        $html = $this->row(
            ['type' => 'integer'],
            ['options' => ['widget' => 'slider', 'min' => 2, 'max' => 8, 'step' => 2]],
            ['value' => [1]]
        );
        $this->assertStringContainsString('min="2"', $html);
        $this->assertStringContainsString('max="8"', $html);
        $this->assertStringContainsString('step="2"', $html);
        $this->assertStringContainsString('value="[1]"', $html);
    }

    #[Test]
    public function shouldRenderTheCheckboxesWithTheirStateAndFailWithoutChoices(): void
    {
        $html = $this->row(
            ['type' => 'array', 'items' => ['enum' => ['a', 'b']]],
            [],
            ['has_errors' => true, 'widget_class' => 'extra'],
            [],
            true
        );
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('extra', $html);

        $html = $this->row(
            ['enum' => ['a', 'b']],
            ['options' => ['widget' => 'checkboxes']],
            [],
            ['errors' => ['field' => ['Bad']]]
        );
        $this->assertStringContainsString('is-invalid', $html);

        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('No choices found');
        $this->row(['type' => 'array', 'items' => ['type' => 'string']], ['options' => ['widget' => 'checkboxes']]);
    }

    #[Test]
    public function shouldRenderAnInputWithTheTypeOfTheAttributesAndTheFileThatIsMultiple(): void
    {
        $html = $this->row([], [], ['attr' => ['type' => 'tel']], [], true);
        $this->assertStringContainsString('type="tel"', $html);
        $this->assertStringContainsString('required="required"', $html);

        $html = $this->row(['type' => 'number', 'multipleOf' => 0.5], ['options' => ['widget' => 'number']]);
        $this->assertStringContainsString('step="0.5"', $html);

        $html = $this->row([], ['options' => ['widget' => 'file', 'multiple' => true]]);
        $this->assertStringContainsString('name="field[]"', $html);
        $this->assertStringContainsString('multiple="multiple"', $html);
    }

    #[Test]
    public function shouldRenderATextareaWithItsStateAndThePattern(): void
    {
        $html = $this->row(
            ['pattern' => '^a'],
            ['options' => ['multi' => true]],
            [],
            ['errors' => ['field' => ['Bad']]],
            true
        );

        $this->assertStringContainsString('is-invalid', $html);
        $this->assertStringContainsString('required="required"', $html);
        $this->assertStringContainsString('pattern="^a"', $html);
    }

    #[Test]
    public function shouldDisableTheFieldWhenTheRuleThatEnablesItIsNotActive(): void
    {
        $definition = [
            'schema' => ['type' => 'object', 'properties' => ['kind' => ['type' => 'string'], 'field' => ['type' => 'string']]],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/kind'],
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/field',
                        'rule' => [
                            'effect' => 'ENABLE',
                            'condition' => ['scope' => '#/properties/kind', 'schema' => ['const' => 'yes']],
                        ],
                    ],
                ],
            ],
            'data' => ['kind' => 'no'],
            'options' => ['csrf_protection' => false],
        ];
        $form = (new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider()))))->create($definition);

        $html = FormRendererFactory::create()->render($form);

        $this->assertMatchesRegularExpression('/<input[^>]*name="field"[^>]*disabled/', html_entity_decode($html));
    }

    #[Test]
    public function shouldFailToRenderAControlThatIsNotAFieldOfTheForm(): void
    {
        $form = (new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider()))))->create([
            'schema' => ['type' => 'object', 'properties' => ['field' => ['type' => 'string']]],
            'uischema' => ['type' => 'VerticalLayout', 'elements' => []],
        ]);

        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('not found in form');
        FormRendererFactory::create()->renderElement(new Control(['type' => 'Control', 'scope' => '#/properties/nope']), $form);
    }

    /**
     * @param array<string, mixed> $uischema
     * @param array<string, mixed> $options
     */
    private function layout(array $uischema, array $options = []): string
    {
        $form = (new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider()))))->create([
            'schema' => ['type' => 'object', 'properties' => ['a' => ['type' => 'string'], 'b' => ['type' => 'string']]],
            'uischema' => $uischema,
            'options' => ['csrf_protection' => false],
        ]);

        return html_entity_decode(FormRendererFactory::create()->renderElement($form->getUiSchema(), $form, $options));
    }

    #[Test]
    public function shouldRenderTheElementsOfAHorizontalLayoutWithTheSizeOfTheColumn(): void
    {
        $html = $this->layout([
            'type' => 'HorizontalLayout',
            'elements' => [
                ['type' => 'Control', 'scope' => '#/properties/a', 'options' => ['col_size' => 4]],
                ['type' => 'Control', 'scope' => '#/properties/b'],
            ],
        ]);

        $this->assertStringContainsString('name="a"', $html);
        $this->assertStringContainsString('name="b"', $html);
    }

    #[Test]
    public function shouldRenderALabelWithAttributesThatAreFlags(): void
    {
        $uischema = ['type' => 'VerticalLayout', 'elements' => [['type' => 'Label', 'text' => 'Hello']]];

        $html = $this->layout($uischema, ['label_attr' => ['class' => 'x', 'hidden' => true, 'data-off' => false, 'title' => 'T']]);
        $this->assertStringContainsString('<label class="x" hidden title="T">Hello</label>', $html);
        $this->assertStringNotContainsString('data-off', $html);

        $html = $this->layout($uischema, ['heading' => true, 'heading_level' => 2]);
        $this->assertStringContainsString('>Hello</h2>', $html);
    }

    /**
     * @return array<string, mixed>
     */
    private static function collection(): array
    {
        return ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['a' => ['type' => 'string']]]];
    }

    #[Test]
    public function shouldRenderACollectionWithoutRowsWhenTheDataIsNotAList(): void
    {
        $html = $this->row(self::collection(), [], [], ['data' => ['field' => 'x']]);

        $this->assertStringContainsString('field', $html);
    }

    #[Test]
    public function shouldRenderTheRowsOfAComplexCollectionFromJsonOrFromNothingUseful(): void
    {
        $detail = [
            'type' => 'VerticalLayout',
            'elements' => [
                ['type' => 'Group', 'label' => 'G', 'elements' => [['type' => 'Control', 'scope' => '#/properties/a']]],
            ],
        ];

        $html = $this->row(
            self::collection(),
            ['options' => ['detail' => $detail]],
            [],
            ['data' => ['field' => ['{"a":"from json"}', 5]]]
        );

        $this->assertStringContainsString('from json', $html);
    }

    #[Test]
    public function shouldNotRenderACollectionWithoutTheRendererOrOfAnotherTypeOfProperty(): void
    {
        $form = (new FormFactory(new TypeResolver(new TypeRegistry(new TypeProvider()))))->create([
            'schema' => ['type' => 'object', 'properties' => ['field' => ['type' => 'string']]],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/field']],
            ],
        ]);
        $field = $form->getField('field');
        $this->assertNotNull($field);
        $widget = new \Derafu\Form\Renderer\Widget\CollectionWidgetRenderer();

        try {
            $widget->render($field);
            $this->fail('The renderer is required.');
        } catch (TranslatableInvalidArgumentException $e) {
            $this->assertStringContainsString('"renderer" option', $e->getMessage());
        }

        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('requires an ArraySchemaInterface');
        $widget->render($field, ['renderer' => FormRendererFactory::create()]);
    }
}
