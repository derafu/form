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

use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractType;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Contract\Schema\ArraySchemaInterface;
use Derafu\Form\Factory\FormFactory;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\ArraySchema;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
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
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\HorizontalLayout;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests the control.options.choices shorthand.
 *
 * The shorthand lets form authors write a compact `key => label` dict in the
 * UI schema control options instead of the verbose oneOf/const/title structure.
 * It is resolved inside FormFactory::normalizeDefinition() — before any object
 * is created — so it never reaches any Control or PropertySchema object.
 *
 * The test goes through FormFactory::create() to validate the full
 * normalization pipeline; Form::fromArray() is a pure hydrator and is not
 * the right entry point for normalization tests.
 */
#[CoversClass(FormFactory::class)]
#[CoversClass(Form::class)]
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(FormSchema::class)]
#[CoversTrait(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(ArraySchema::class)]
#[CoversClass(Control::class)]
#[CoversClass(HorizontalLayout::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Widget::class)]
#[CoversClass(WidgetFactory::class)]
#[CoversClass(FormField::class)]
#[CoversClass(AbstractType::class)]
#[CoversClass(TypeProvider::class)]
#[CoversClass(TypeRegistry::class)]
#[CoversClass(TypeResolver::class)]
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
final class FormChoicesShorthandTest extends TestCase
{
    private FormFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new FormFactory(
            new TypeResolver(new TypeRegistry(new TypeProvider()))
        );
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Builds a minimal single-field form definition.
     *
     * @param array $propertyDef  The JSON Schema property definition.
     * @param array $controlOpts  The UI schema control options.
     * @return array
     */
    private function def(array $propertyDef, array $controlOpts = []): array
    {
        $control = [
            'type'  => 'Control',
            'scope' => '#/properties/field',
        ];

        if ($controlOpts !== []) {
            $control['options'] = $controlOpts;
        }

        return [
            'schema' => [
                'type'       => 'object',
                'properties' => [
                    'field' => $propertyDef,
                ],
            ],
            'uischema' => [
                'type'     => 'VerticalLayout',
                'elements' => [$control],
            ],
        ];
    }

    // =========================================================================
    // String field
    // =========================================================================

    /**
     * choices on a string field injects oneOf into the schema property.
     */
    public function testStringFieldChoicesInjectsOneOf(): void
    {
        $form = $this->factory->create($this->def(
            ['type' => 'string', 'title' => 'Status'],
            ['type' => 'choice', 'choices' => ['active' => 'Active', 'inactive' => 'Inactive']]
        ));

        $choices = $form->getField('field')->getProperty()->getChoices();

        $this->assertSame(['active' => 'Active', 'inactive' => 'Inactive'], $choices);
    }

    /**
     * choices is removed from the control options — it must not reach the
     * Control object.
     */
    public function testStringFieldChoicesRemovedFromControlOptions(): void
    {
        $form = $this->factory->create($this->def(
            ['type' => 'string'],
            ['type' => 'choice', 'choices' => ['a' => 'A', 'b' => 'B']]
        ));

        $options = $form->getField('field')->getControl()->getOptions();

        $this->assertArrayNotHasKey('choices', $options);
        // Other options are preserved.
        $this->assertSame('choice', $options['type']);
    }

    /**
     * When choices is the only option key, options is dropped entirely from
     * the element (not left as an empty array).
     */
    public function testStringFieldChoicesOnlyOptionDropsOptionsKey(): void
    {
        $form = $this->factory->create($this->def(
            ['type' => 'string'],
            ['choices' => ['x' => 'X']]
        ));

        $options = $form->getField('field')->getControl()->getOptions();

        $this->assertSame([], $options);
    }

    /**
     * getChoices() returns the correct value → label mapping derived from
     * the promoted oneOf.
     */
    public function testStringFieldChoicesPreservesOrder(): void
    {
        $form = $this->factory->create($this->def(
            ['type' => 'string'],
            ['choices' => ['c' => 'C', 'a' => 'A', 'b' => 'B']]
        ));

        $this->assertSame(
            ['c' => 'C', 'a' => 'A', 'b' => 'B'],
            $form->getField('field')->getProperty()->getChoices()
        );
    }

    // =========================================================================
    // Array field (checkboxes / multi-select)
    // =========================================================================

    /**
     * choices on an array field injects oneOf into items, not into the array
     * property itself.
     */
    public function testArrayFieldChoicesInjectsOneOfIntoItems(): void
    {
        $form = $this->factory->create($this->def(
            ['type' => 'array'],
            ['choices' => ['php' => 'PHP', 'js' => 'JavaScript']]
        ));

        $property = $form->getField('field')->getProperty();

        // The array property itself has no choices.
        $this->assertNull($property->getChoices());

        // The items sub-schema carries the choices.
        $this->assertInstanceOf(ArraySchemaInterface::class, $property);
        $this->assertNotNull($property->getItems());
        $this->assertSame(
            ['php' => 'PHP', 'js' => 'JavaScript'],
            $property->getItems()->getChoices()
        );
    }

    /**
     * choices on an array field creates items if not already defined.
     */
    public function testArrayFieldChoicesCreatesItemsWhenAbsent(): void
    {
        $form = $this->factory->create($this->def(
            ['type' => 'array'],
            ['choices' => ['x' => 'X', 'y' => 'Y']]
        ));

        $property = $form->getField('field')->getProperty();

        $this->assertInstanceOf(ArraySchemaInterface::class, $property);
        $this->assertNotNull($property->getItems());
    }

    /**
     * choices on an array field that already has an items definition merges
     * the oneOf into it without removing other items keywords.
     */
    public function testArrayFieldChoicesMergesIntoExistingItems(): void
    {
        $form = $this->factory->create($this->def(
            ['type' => 'array', 'items' => ['type' => 'string']],
            ['choices' => ['a' => 'A', 'b' => 'B']]
        ));

        $property = $form->getField('field')->getProperty();

        $this->assertInstanceOf(ArraySchemaInterface::class, $property);
        $this->assertSame('string', $property->getItems()->getType());
        $this->assertSame(['a' => 'A', 'b' => 'B'], $property->getItems()->getChoices());
    }

    /**
     * choices is removed from the control options for array fields too.
     */
    public function testArrayFieldChoicesRemovedFromControlOptions(): void
    {
        $form = $this->factory->create($this->def(
            ['type' => 'array'],
            ['choices' => ['m' => 'M']]
        ));

        $this->assertArrayNotHasKey(
            'choices',
            $form->getField('field')->getControl()->getOptions()
        );
    }

    // =========================================================================
    // Nested uischema (recursion)
    // =========================================================================

    /**
     * choices inside a nested layout element (VerticalLayout > HorizontalLayout
     * > Control) is still resolved correctly.
     */
    public function testChoicesResolvedInNestedLayout(): void
    {
        $form = $this->factory->create([
            'schema' => [
                'type'       => 'object',
                'properties' => [
                    'color' => ['type' => 'string'],
                    'size'  => ['type' => 'string'],
                ],
            ],
            'uischema' => [
                'type'     => 'VerticalLayout',
                'elements' => [
                    [
                        'type'     => 'HorizontalLayout',
                        'elements' => [
                            [
                                'type'    => 'Control',
                                'scope'   => '#/properties/color',
                                'options' => ['choices' => ['red' => 'Red', 'blue' => 'Blue']],
                            ],
                            [
                                'type'    => 'Control',
                                'scope'   => '#/properties/size',
                                'options' => ['choices' => ['s' => 'Small', 'l' => 'Large']],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            ['red' => 'Red', 'blue' => 'Blue'],
            $form->getField('color')->getProperty()->getChoices()
        );
        $this->assertSame(
            ['s' => 'Small', 'l' => 'Large'],
            $form->getField('size')->getProperty()->getChoices()
        );
    }

    /**
     * Multiple controls in the same layout each get their choices promoted
     * independently.
     */
    public function testMultipleFieldsWithChoicesInSameLayout(): void
    {
        $form = $this->factory->create([
            'schema' => [
                'type'       => 'object',
                'properties' => [
                    'status'   => ['type' => 'string'],
                    'category' => ['type' => 'string'],
                ],
            ],
            'uischema' => [
                'type'     => 'VerticalLayout',
                'elements' => [
                    [
                        'type'    => 'Control',
                        'scope'   => '#/properties/status',
                        'options' => [
                            'choices' => ['draft' => 'Draft', 'active' => 'Active'],
                        ],
                    ],
                    [
                        'type'    => 'Control',
                        'scope'   => '#/properties/category',
                        'options' => [
                            'choices' => ['books' => 'Books', 'music' => 'Music'],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            ['draft' => 'Draft', 'active' => 'Active'],
            $form->getField('status')->getProperty()->getChoices()
        );
        $this->assertSame(
            ['books' => 'Books', 'music' => 'Music'],
            $form->getField('category')->getProperty()->getChoices()
        );
    }

    // =========================================================================
    // No-op cases
    // =========================================================================

    /**
     * A control without choices is untouched.
     */
    public function testControlWithoutChoicesIsUntouched(): void
    {
        $form = $this->factory->create($this->def(
            ['type' => 'string'],
            ['type' => 'choice', 'placeholder' => 'Select…']
        ));

        $this->assertNull($form->getField('field')->getProperty()->getChoices());
        $this->assertSame('choice', $form->getField('field')->getControl()->getOptions()['type']);
    }

    /**
     * When uischema.elements is absent, the method returns silently.
     */
    public function testEmptyDefinitionDoesNotThrow(): void
    {
        $form = $this->factory->create([
            'schema'   => ['type' => 'object'],
            'uischema' => ['type' => 'VerticalLayout', 'elements' => []],
        ]);

        $this->assertSame([], $form->getFields());
    }

    // =========================================================================
    // LogicException cases
    // =========================================================================

    /**
     * Nested scope throws LogicException.
     */
    public function testNestedScopeThrows(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/nested scopes/');

        $this->factory->create([
            'schema' => [
                'type'       => 'object',
                'properties' => ['address' => ['type' => 'object']],
            ],
            'uischema' => [
                'type'     => 'VerticalLayout',
                'elements' => [
                    [
                        'type'    => 'Control',
                        'scope'   => '#/properties/address/properties/city',
                        'options' => ['choices' => ['a' => 'A']],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Scope referencing a property that does not exist in the schema throws.
     */
    public function testMissingPropertyThrows(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/not found in schema/');

        $this->factory->create([
            'schema' => [
                'type'       => 'object',
                'properties' => ['other' => ['type' => 'string']],
            ],
            'uischema' => [
                'type'     => 'VerticalLayout',
                'elements' => [
                    [
                        'type'    => 'Control',
                        'scope'   => '#/properties/ghost',
                        'options' => ['choices' => ['a' => 'A']],
                    ],
                ],
            ],
        ]);
    }

    /**
     * choices on an integer field throws LogicException.
     */
    public function testIntegerFieldThrows(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/not supported for type "integer"/');

        $this->factory->create($this->def(
            ['type' => 'integer'],
            ['choices' => ['1' => 'One', '2' => 'Two']]
        ));
    }

    /**
     * choices on a number field throws LogicException.
     */
    public function testNumberFieldThrows(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/not supported for type "number"/');

        $this->factory->create($this->def(
            ['type' => 'number'],
            ['choices' => ['1.5' => 'One point five']]
        ));
    }

    /**
     * choices when the property already has oneOf throws LogicException.
     */
    public function testExistingOneOfOnStringThrows(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/already has oneOf defined/');

        $this->factory->create($this->def(
            [
                'type'  => 'string',
                'oneOf' => [['const' => 'a', 'title' => 'A']],
            ],
            ['choices' => ['b' => 'B']]
        ));
    }

    /**
     * choices when the array items already has oneOf throws LogicException.
     */
    public function testExistingOneOfOnArrayItemsThrows(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/items already has oneOf defined/');

        $this->factory->create($this->def(
            [
                'type'  => 'array',
                'items' => ['oneOf' => [['const' => 'x', 'title' => 'X']]],
            ],
            ['choices' => ['y' => 'Y']]
        ));
    }
}
