<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsForm;

use Derafu\DataProcessor\Processor;
use Derafu\DataProcessor\Registrar\DefaultRuleRegistrar;
use Derafu\DataProcessor\RuleParser;
use Derafu\DataProcessor\RuleRegistry;
use Derafu\DataProcessor\RuleResolver;
use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractType;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Contract\FormFieldInterface;
use Derafu\Form\Contract\FormInterface;
use Derafu\Form\Data\FormData;
use Derafu\Form\Factory\ChoicesShorthand;
use Derafu\Form\Factory\FormFactory;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormDefinition;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormAttributes;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Processor\FormDataProcessor;
use Derafu\Form\Processor\FormRulesResolver;
use Derafu\Form\Processor\ProcessResult;
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
use Derafu\Form\Renderer\Support\InputActionResolver;
use Derafu\Form\Renderer\Widget\CheckboxWidgetRenderer;
use Derafu\Form\Renderer\Widget\CollectionWidgetRenderer;
use Derafu\Form\Renderer\Widget\InputWidgetRenderer;
use Derafu\Form\Renderer\Widget\RadioWidgetRenderer;
use Derafu\Form\Renderer\Widget\SelectWidgetRenderer;
use Derafu\Form\Renderer\Widget\SliderWidgetRenderer;
use Derafu\Form\Renderer\Widget\TextareaWidgetRenderer;
use Derafu\Form\Renderer\WidgetRendererProvider;
use Derafu\Form\Renderer\WidgetRendererRegistry;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\ArraySchema;
use Derafu\Form\Schema\BooleanSchema;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\IntegerSchema;
use Derafu\Form\Schema\NumberSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\PropertyDefinition;
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
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use Derafu\TestsForm\Fixture\FormTestContainer;
use Derafu\Translation\Exception\Logic\TranslatableInvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests for schema property `default` value behavior.
 *
 * Verifies three scenarios:
 *
 * A) GET (empty form): schema default pre-fills fields when no data is given.
 * B) Data section overrides: explicit form data always wins over schema default.
 * C) POST re-render: submitted values win, including falsy ones (false, '', 0).
 *    This guards against the ?? operator incorrectly falling back to default
 *    when the user submitted a legitimate falsy value.
 * D) Rendered HTML: the correct value appears in the rendered output.
 */
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Renderer\ChainElementRendererProvider::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Renderer\ChainWidgetRendererProvider::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Renderer\FormTemplatePathProvider::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Renderer\FormExtensionProvider::class)]
#[CoversClass(Form::class)]
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormData::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(FormSchema::class)]
#[CoversTrait(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(BooleanSchema::class)]
#[CoversClass(IntegerSchema::class)]
#[CoversClass(NumberSchema::class)]
#[CoversClass(ArraySchema::class)]
#[CoversClass(FormFactory::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(AbstractType::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(FormAttributes::class)]
#[CoversClass(TypeProvider::class)]
#[CoversClass(TypeRegistry::class)]
#[CoversClass(TypeResolver::class)]
#[CoversClass(WidgetFactory::class)]
#[CoversClass(Widget::class)]
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
#[CoversClass(TextType::class)]
#[CoversClass(TextareaType::class)]
#[CoversClass(TimeType::class)]
#[CoversClass(UriType::class)]
#[CoversClass(UrlType::class)]
#[CoversClass(UuidType::class)]
#[CoversClass(WeekType::class)]
#[CoversClass(Control::class)]
#[CoversClass(Categorization::class)]
#[CoversClass(Category::class)]
#[CoversClass(Group::class)]
#[CoversClass(HorizontalLayout::class)]
#[CoversClass(Label::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(FormRenderer::class)]
#[CoversClass(FormTwigExtension::class)]
#[CoversClass(ElementRendererRegistry::class)]
#[CoversClass(ElementRendererProvider::class)]
#[CoversClass(WidgetRendererRegistry::class)]
#[CoversClass(WidgetRendererProvider::class)]
#[CoversClass(CategorizationRenderer::class)]
#[CoversClass(ControlRenderer::class)]
#[CoversClass(InputActionResolver::class)]
#[CoversClass(GroupRenderer::class)]
#[CoversClass(HorizontalLayoutRenderer::class)]
#[CoversClass(LabelRenderer::class)]
#[CoversClass(VerticalLayoutRenderer::class)]
#[CoversClass(CheckboxWidgetRenderer::class)]
#[CoversClass(CollectionWidgetRenderer::class)]
#[CoversClass(InputWidgetRenderer::class)]
#[CoversClass(RadioWidgetRenderer::class)]
#[CoversClass(SelectWidgetRenderer::class)]
#[CoversClass(SliderWidgetRenderer::class)]
#[CoversClass(TextareaWidgetRenderer::class)]
#[CoversClass(FormDataProcessor::class)]
#[CoversClass(FormRulesResolver::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(ProcessResult::class)]
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(ChoicesShorthand::class)]
#[UsesClass(FormDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Renderer\Support\RequiredField::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Data\FieldPath::class)]
final class FormSchemaDefaultTest extends TestCase
{
    private FormFactory $formFactory;

    protected function setUp(): void
    {
        $this->formFactory = new FormFactory(
            new TypeResolver(new TypeRegistry(new TypeProvider()))
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Minimal form definition with a single string field.
     *
     * @return array<string, mixed>
     */
    private function stringFieldDef(
        string $name,
        mixed $default = null,
        ?string $dataValue = null,
        bool $dataKeyPresent = false,
    ): array {
        $property = ['type' => 'string'];
        if ($default !== null) {
            $property['default'] = $default;
        }

        $def = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [$name => $property],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => "#/properties/{$name}"]],
            ],
        ];

        if ($dataKeyPresent) {
            $def['data'] = [$name => $dataValue];
        }

        return $def;
    }

    /**
     * Minimal form definition with a single boolean field.
     *
     * @return array<string, mixed>
     */
    private function boolFieldDef(string $name, ?bool $default = null, ?bool $dataValue = null, bool $dataKeyPresent = false): array
    {
        $property = ['type' => 'boolean'];
        if ($default !== null) {
            $property['default'] = $default;
        }

        $def = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [$name => $property],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => "#/properties/{$name}"]],
            ],
        ];

        if ($dataKeyPresent) {
            $def['data'] = [$name => $dataValue];
        }

        return $def;
    }

    /**
     * Minimal form definition with a single integer field.
     *
     * @return array<string, mixed>
     */
    private function intFieldDef(string $name, ?int $default = null, ?int $dataValue = null, bool $dataKeyPresent = false): array
    {
        $property = ['type' => 'integer'];
        if ($default !== null) {
            $property['default'] = $default;
        }

        $def = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [$name => $property],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => "#/properties/{$name}"]],
            ],
        ];

        if ($dataKeyPresent) {
            $def['data'] = [$name => $dataValue];
        }

        return $def;
    }

    private function processor(): FormDataProcessor
    {
        $dataProcessorRegistry = new RuleRegistry();
        (new DefaultRuleRegistrar())->register($dataProcessorRegistry);
        return new FormDataProcessor(
            new FormRulesResolver(),
            new Processor(new RuleResolver($dataProcessorRegistry), new RuleParser())
        );
    }

    // =========================================================================
    // GROUP A — Schema default applied at render time (GET, no data section)
    // =========================================================================

    public function testStringDefaultAppliedWhenNoDataProvided(): void
    {
        $form = $this->formFactory->create($this->stringFieldDef('name', 'John'));

        $this->assertSame('John', $this->field($form, 'name')->getData());
    }

    public function testIntegerDefaultAppliedWhenNoDataProvided(): void
    {
        $form = $this->formFactory->create($this->intFieldDef('count', 42));

        $this->assertSame(42, $this->field($form, 'count')->getData());
    }

    public function testBooleanDefaultTrueAppliedWhenNoDataProvided(): void
    {
        $form = $this->formFactory->create($this->boolFieldDef('active', true));

        $this->assertTrue($this->field($form, 'active')->getData());
    }

    public function testBooleanDefaultFalseAppliedWhenNoDataProvided(): void
    {
        // false is a valid default — the field should be set to false,
        // not left as null. This means e.g. a checkbox renders as unchecked.
        $form = $this->formFactory->create($this->boolFieldDef('active', false));

        // false is explicitly not null, so setData(false) must be called.
        $this->assertFalse($this->field($form, 'active')->getData());
    }

    public function testFieldWithNoDefaultAndNoDataHasNullData(): void
    {
        $form = $this->formFactory->create($this->stringFieldDef('name'));

        $this->assertNull($this->field($form, 'name')->getData());
    }

    public function testMultipleFields_OnlyFieldsWithDefaultArePreFilled(): void
    {
        $form = $this->formFactory->create([
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'with_default' => ['type' => 'string', 'default' => 'hello'],
                    'no_default'   => ['type' => 'string'],
                ],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/with_default'],
                    ['type' => 'Control', 'scope' => '#/properties/no_default'],
                ],
            ],
        ]);

        $this->assertSame('hello', $this->field($form, 'with_default')->getData());
        $this->assertNull($this->field($form, 'no_default')->getData());
    }

    // =========================================================================
    // GROUP B — Data section overrides schema default
    // =========================================================================

    public function testDataSectionOverridesStringDefault(): void
    {
        $form = $this->formFactory->create(
            $this->stringFieldDef('name', 'John', 'Jane', dataKeyPresent: true)
        );

        $this->assertSame('Jane', $this->field($form, 'name')->getData());
    }

    public function testDataSectionFalseOverridesBooleanDefaultTrue(): void
    {
        // Explicit false in data must win. false is not null, so ?? must not
        // fall through to the default. This is the most critical edge case.
        $form = $this->formFactory->create(
            $this->boolFieldDef('active', true, false, dataKeyPresent: true)
        );

        $this->assertFalse($this->field($form, 'active')->getData());
    }

    public function testDataSectionZeroOverridesIntegerDefault(): void
    {
        // 0 is not null — data wins.
        $form = $this->formFactory->create(
            $this->intFieldDef('count', 42, 0, dataKeyPresent: true)
        );

        $this->assertSame(0, $this->field($form, 'count')->getData());
    }

    public function testDataSectionEmptyStringOverridesStringDefault(): void
    {
        // Empty string is not null — data wins.
        $form = $this->formFactory->create(
            $this->stringFieldDef('name', 'John', '', dataKeyPresent: true)
        );

        $this->assertSame('', $this->field($form, 'name')->getData());
    }

    // =========================================================================
    // GROUP C — POST re-render: submitted value wins, including falsy ones
    //
    // Flow: user submits form → processor processes POST data → result->getForm()
    // creates a re-renderable form with processed values.
    // =========================================================================

    public function testBooleanCheckboxUncheckedDoesNotFallBackToDefaultOnRerender(): void
    {
        // Scenario: checkbox has default=true (renders checked initially).
        // User unchecks it and submits. HTML does NOT send the field when
        // unchecked, so the key is absent from POST. The processor casts
        // null → false. Re-render must show false (unchecked), not the default.

        $def = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'newsletter' => ['type' => 'boolean', 'default' => true],
                ],
                'required' => [],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/newsletter']],
            ],
        ];

        $form = $this->formFactory->create($def);

        // POST does not contain 'newsletter' (unchecked checkbox).
        $postData = [];
        $result = $this->processor()->process($form, $postData);

        $reRenderedForm = $result->getForm();
        $field = $this->field($reRenderedForm, 'newsletter');

        $this->assertFalse(
            $field->getData(),
            'Unchecked checkbox must re-render as false, not fall back to default=true.'
        );
    }

    public function testStringFieldSubmittedEmptyDoesNotFallBackToDefaultOnRerender(): void
    {
        // Scenario: string field has default='John'. User clears the field
        // and submits empty string. Re-render must show it empty (an empty
        // value is `null`), not 'John'.

        $def = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string', 'default' => 'John'],
                ],
                'required' => [],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/name']],
            ],
        ];

        $form = $this->formFactory->create($def);

        $postData = ['name' => ''];
        $result = $this->processor()->process($form, $postData);

        $field = $this->field($result->getForm(), 'name');
        $this->assertNull(
            $field->getData(),
            'Empty string submitted by user must not fall back to schema default.'
        );
    }

    public function testStringFieldSubmittedValueWinsOverDefault(): void
    {
        $def = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'city' => ['type' => 'string', 'default' => 'Madrid'],
                ],
                'required' => [],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/city']],
            ],
        ];

        $form = $this->formFactory->create($def);

        $postData = ['city' => 'Barcelona'];
        $result = $this->processor()->process($form, $postData);

        $this->assertSame('Barcelona', $this->field($result->getForm(), 'city')->getData());
    }

    public function testMultipleFieldsRerenderPreservesEachSubmittedValue(): void
    {
        // Comprehensive re-render test: mix of fields with/without defaults,
        // boolean checkbox unchecked, string submitted, integer submitted.
        $def = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name'      => ['type' => 'string', 'default' => 'John'],
                    'newsletter' => ['type' => 'boolean', 'default' => true],
                    'age'       => ['type' => 'integer', 'default' => 18],
                ],
                'required' => [],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                    ['type' => 'Control', 'scope' => '#/properties/newsletter'],
                    ['type' => 'Control', 'scope' => '#/properties/age'],
                ],
            ],
        ];

        $form = $this->formFactory->create($def);

        // User changed name, unchecked newsletter, changed age.
        $postData = ['name' => 'Jane', 'age' => '25'];
        // 'newsletter' absent = unchecked checkbox.
        $result = $this->processor()->process($form, $postData);

        $fields = $result->getForm();
        $this->assertSame('Jane', $this->field($fields, 'name')->getData());
        $this->assertFalse($this->field($fields, 'newsletter')->getData());
        $this->assertSame(25, $this->field($fields, 'age')->getData());
    }

    // =========================================================================
    // GROUP D — Rendered HTML reflects field data correctly
    // =========================================================================

    public function testTextInputRendersWithDefaultValue(): void
    {
        $form = $this->formFactory->create($this->stringFieldDef('name', 'John'));
        $html = (new FormTestContainer())->getFormRenderer()->render($form);

        $this->assertStringContainsString('value="John"', $html);
    }

    public function testCheckboxRendersCheckedWhenDefaultIsTrue(): void
    {
        $form = $this->formFactory->create($this->boolFieldDef('active', true));
        $html = (new FormTestContainer())->getFormRenderer()->render($form);

        $this->assertStringContainsString('checked', $html);
    }

    public function testCheckboxRendersUncheckedWhenDefaultIsFalse(): void
    {
        // default=false → setData(false) → checkbox must NOT have checked attr.
        $form = $this->formFactory->create($this->boolFieldDef('active', false));
        $html = (new FormTestContainer())->getFormRenderer()->render($form);

        // The checkbox is present but must not be checked.
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringNotContainsString('checked', $html);
    }

    public function testSelectRendersWithDefaultOptionSelected(): void
    {
        $form = $this->formFactory->create([
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'status' => [
                        'type' => 'string',
                        'enum' => ['pending', 'active', 'closed'],
                        'default' => 'active',
                    ],
                ],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [['type' => 'Control', 'scope' => '#/properties/status']],
            ],
        ]);

        $html = (new FormTestContainer())->getFormRenderer()->render($form);

        // The 'active' option must be marked selected.
        $this->assertMatchesRegularExpression(
            '/value="active"[^>]*selected|selected[^>]*value="active"/',
            $html
        );
    }

    public function testCheckboxRendersUncheckedAfterUserUnchecksAndResubmits(): void
    {
        // Regression: checkbox with default=true must render unchecked after
        // user unchecks it and the form re-renders (e.g. with validation errors).
        $def = [
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'terms'   => ['type' => 'boolean', 'default' => true],
                    'name'    => ['type' => 'string'],
                ],
                'required' => ['name'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/terms'],
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                ],
            ],
        ];

        $form = $this->formFactory->create($def);

        // name is empty → validation fails → form re-renders.
        // terms is absent from POST (user unchecked it).
        $postData = ['name' => ''];
        $result = $this->processor()->process($form, $postData);

        $this->assertFalse($result->isValid(), 'Required name empty, must be invalid.');

        $html = (new FormTestContainer())->getFormRenderer()->render($result->getForm());

        // terms checkbox must NOT be checked in the re-rendered form.
        $this->assertStringNotContainsString('checked', $html);
    }

    private function field(FormInterface $form, string $name): FormFieldInterface
    {
        $field = $form->getField($name);
        $this->assertNotNull($field);

        return $field;
    }

    // =========================================================================
    // The first level of the definition
    // =========================================================================

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function wrongDefinitionsProvider(): array
    {
        $schema = ['type' => 'object', 'properties' => ['a' => ['type' => 'string']]];

        return [
            'schema' => [['schema' => 'x'], 'The key "schema" must be of type map, string given.'],
            'uischema' => [['schema' => $schema, 'uischema' => 'x'], 'The key "uischema" must be of type map, string given.'],
            'data' => [['schema' => $schema, 'data' => 'x'], 'The key "data" must be of type map, string given.'],
            'rules' => [['schema' => $schema, 'rules' => 'x'], 'The key "rules" must be of type map<array>, string given.'],
            'errors' => [['schema' => $schema, 'errors' => 'x'], 'The key "errors" must be of type map<list<string>>, string given.'],
            'an error that is not a list of strings' => [['schema' => $schema, 'errors' => ['a' => [5]]], 'The key "errors.a.0" must be of type string, int given.'],
        ];
    }

    /**
     * @param array<string, mixed> $definition
     */
    #[DataProvider('wrongDefinitionsProvider')]
    public function testADefinitionWithAKeyOfAnotherTypeIsRejected(array $definition, string $message): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        $this->formFactory->create($definition);
    }

    public function testASchemaWithoutPropertiesHasAnEmptyUiSchema(): void
    {
        $form = $this->formFactory->create(['schema' => ['type' => 'object']]);

        $this->assertSame([], $form->getFields());
        $this->assertSame([], $form->getUiSchema()->toArray()['elements'] ?? []);
    }

    public function testAFormWithNeitherSchemaNorDataIsRejected(): void
    {
        $this->expectException(TranslatableInvalidArgumentException::class);
        $this->expectExceptionMessage('The form schema definition must be assigned if form data is not provided.');

        $this->formFactory->create(['options' => ['csrf_protection' => false]]);
    }

    public function testNullDataOfAStringFieldIsAnEmptyString(): void
    {
        $form = $this->formFactory->create([
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'properties' => ['name' => ['type' => 'string'], 'age' => ['type' => 'integer']],
            ],
            'data' => ['name' => null, 'age' => null],
        ]);

        $data = $form->getData();
        $this->assertNotNull($data);
        $this->assertSame('', $data->get('name'));
        $this->assertNull($data->get('age'));
    }
}
