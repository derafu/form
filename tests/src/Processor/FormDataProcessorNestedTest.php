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

use Derafu\Array\ArrayPath;
use Derafu\DataProcessor\Processor;
use Derafu\DataProcessor\Registrar\DefaultRuleRegistrar;
use Derafu\DataProcessor\RuleParser;
use Derafu\DataProcessor\RuleRegistry;
use Derafu\DataProcessor\RuleResolver;
use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\PropertySchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormDefinition;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Processor\FormDataProcessor;
use Derafu\Form\Processor\FormRulesResolver;
use Derafu\Form\Processor\ProcessResult;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\PropertyDefinition;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\UiSchemaElementDefinition;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * The fields of an object of the schema: their names, their values in the data
 * and their `required`, which is the one of the list of the object that has them.
 */
#[CoversClass(FormDataProcessor::class)]
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(FormRulesResolver::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(ProcessResult::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(Form::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(FormSchema::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversTrait(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(Control::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Widget::class)]
#[CoversClass(WidgetFactory::class)]
#[UsesClass(PropertyDefinition::class)]
#[UsesClass(UiSchemaElementDefinition::class)]
#[UsesClass(FormDefinition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Schema\BooleanSchema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Options\FormAttributes::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Schema\Pattern::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Data\FieldPath::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Schema\ObjectSchema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Data\FormData::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\Processor\UiSchemaRuleEvaluator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\UiSchema\UiSchemaRule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\UiSchema\UiSchemaCompositeCondition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\UiSchema\UiSchemaCondition::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\UiSchema\ConditionSchema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\UiSchema\UiSchemaRuleEffect::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Derafu\Form\UiSchema\UiSchemaCompositeConditionType::class)]
final class FormDataProcessorNestedTest extends TestCase
{
    private FormDataProcessor $processor;

    protected function setUp(): void
    {
        $dataProcessorRegistry = new RuleRegistry();
        (new DefaultRuleRegistrar())->register($dataProcessorRegistry);
        $this->processor = new FormDataProcessor(new FormRulesResolver(), new Processor(new RuleResolver($dataProcessorRegistry), new RuleParser()));
    }

    /**
     * An object that is required, with only some of its fields required.
     *
     * @param array<int, array<string, mixed>> $rules The rule of the UI schema of
     * the controls that have one, by their position.
     */
    private function form(array $rules = []): Form
    {
        $elements = [
            ['type' => 'Control', 'scope' => '#/properties/nombre'],
            ['type' => 'Control', 'scope' => '#/properties/datos_de_contacto/properties/direccion'],
            ['type' => 'Control', 'scope' => '#/properties/datos_de_contacto/properties/comuna'],
            ['type' => 'Control', 'scope' => '#/properties/datos_de_contacto/properties/ciudad'],
        ];
        foreach ($rules as $position => $rule) {
            $elements[$position]['rule'] = $rule;
        }

        return Form::fromArray([
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'required' => ['datos_de_contacto'],
                'properties' => [
                    'nombre' => ['type' => 'string'],
                    'datos_de_contacto' => [
                        'type' => 'object',
                        'required' => ['direccion', 'comuna'],
                        'properties' => [
                            'direccion' => ['type' => 'string'],
                            'comuna' => ['type' => 'string'],
                            'ciudad' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'uischema' => ['type' => 'VerticalLayout', 'elements' => $elements],
        ]);
    }

    public function testTheFieldsOfAnObjectAreProcessedWithTheirValuesAndGivenBackInTheObject(): void
    {
        $result = $this->processor->process($this->form(), [
            'nombre' => ' Ana ',
            'datos_de_contacto' => ['direccion' => ' Calle 1 ', 'comuna' => 'Lima', 'ciudad' => null],
        ]);

        $this->assertTrue($result->isValid());
        $this->assertSame(
            [
                'nombre' => 'Ana',
                'datos_de_contacto' => ['direccion' => 'Calle 1', 'comuna' => 'Lima', 'ciudad' => null],
            ],
            $result->getProcessedData()
        );
    }

    public function testOnlyTheFieldsInTheListOfTheirObjectAreRequired(): void
    {
        $result = $this->processor->process($this->form(), [
            'nombre' => '',
            'datos_de_contacto' => ['direccion' => '', 'comuna' => '', 'ciudad' => null],
        ]);

        $this->assertFalse($result->isValid());
        $this->assertSame(
            ['datos_de_contacto.direccion', 'datos_de_contacto.comuna'],
            array_keys($result->getErrors())
        );
    }

    public function testTheRequiredOfTheObjectDoesNotRequireItsFields(): void
    {
        $form = $this->form();

        $this->assertTrue($this->processor->process($form, [
            'datos_de_contacto' => ['direccion' => 'Calle 1', 'comuna' => 'Lima'],
        ])->isValid());
    }

    public function testAFieldThatIsMissingInTheObjectIsRequired(): void
    {
        $result = $this->processor->process($this->form(), ['datos_de_contacto' => ['direccion' => 'Calle 1']]);

        $this->assertSame(['datos_de_contacto.comuna'], array_keys($result->getErrors()));
    }

    public function testAKeyWithTheWholeNameIsTheValueOfTheField(): void
    {
        $result = $this->processor->process($this->form(), [
            'datos_de_contacto.direccion' => 'Calle 1',
            'datos_de_contacto.comuna' => 'Lima',
        ]);

        $this->assertTrue($result->isValid());
        $processed = $result->getProcessedData();
        $this->assertIsArray($processed);
        $this->assertSame('Calle 1', ArrayPath::get($processed, 'datos_de_contacto.direccion'));
    }

    public function testTheFormThatIsGivenBackHasTheValuesOfTheObject(): void
    {
        $result = $this->processor->process($this->form(), [
            'datos_de_contacto' => ['direccion' => 'Calle 1', 'comuna' => ''],
        ]);

        $field = $result->getForm()->getField('datos_de_contacto.direccion');
        $this->assertNotNull($field);
        $this->assertSame('Calle 1', $field->getData());
        $this->assertSame(['Field is required.'], $result->getForm()->getField('datos_de_contacto.comuna')?->getErrors());
    }

    public function testTheValuesOfAnObjectWithAFieldThatIsNotActiveAreNotGivenBack(): void
    {
        $form = $this->form([3 => [
            'effect' => 'SHOW',
            'condition' => ['scope' => '#/properties/nombre', 'schema' => ['const' => 'yes']],
        ]]);

        $result = $this->processor->process($form, [
            'nombre' => 'no',
            'datos_de_contacto' => ['direccion' => 'Calle 1', 'comuna' => 'Lima', 'ciudad' => 'Secret'],
        ]);

        $this->assertTrue($result->isValid());
        $this->assertSame(
            ['nombre' => 'no', 'datos_de_contacto' => ['direccion' => 'Calle 1', 'comuna' => 'Lima']],
            $result->getProcessedData()
        );
    }

    public function testTheRuleOfAFieldCanDependOnAFieldOfAnObject(): void
    {
        $form = $this->form([0 => [
            'effect' => 'SHOW',
            'condition' => ['scope' => '#/properties/datos_de_contacto/properties/ciudad', 'schema' => ['const' => 'Lima']],
        ]]);
        $data = fn (string $city) => ['nombre' => 'x', 'datos_de_contacto' => ['direccion' => 'a', 'comuna' => 'b', 'ciudad' => $city]];

        $shown = $this->processor->process($form, $data('Lima'))->getProcessedData();
        $hidden = $this->processor->process($form, $data('Cusco'))->getProcessedData();
        $this->assertIsArray($shown);
        $this->assertIsArray($hidden);
        $this->assertArrayHasKey('nombre', $shown);
        $this->assertArrayNotHasKey('nombre', $hidden);
    }

    /**
     * @param bool|array<string, mixed>|null $root The `additionalProperties` of the root.
     * @param bool|array<string, mixed>|null $contact The `additionalProperties` of the object.
     * @param array<string, mixed> $rule The rule of the UI schema of the control `city`.
     */
    private function openForm(bool|array|null $root, bool|array|null $contact, array $rule = []): Form
    {
        $city = ['type' => 'Control', 'scope' => '#/properties/contact/properties/city'];
        if ($rule !== []) {
            $city['rule'] = $rule;
        }

        $schema = [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'contact' => [
                    'type' => 'object',
                    'properties' => [
                        'address' => ['type' => 'string'],
                        'city' => ['type' => 'string'],
                    ],
                ],
            ],
        ];
        if ($root !== null) {
            $schema['additionalProperties'] = $root;
        }
        if ($contact !== null) {
            $schema['properties']['contact']['additionalProperties'] = $contact;
        }

        return Form::fromArray([
            'options' => ['csrf_protection' => false],
            'schema' => $schema,
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                    ['type' => 'Control', 'scope' => '#/properties/contact/properties/address'],
                    $city,
                ],
            ],
        ]);
    }

    public function testTheFieldsThatAreNotInAnObjectAreDroppedUnlessItIsOpen(): void
    {
        $data = [
            'name' => 'Ana',
            'extra' => 'x',
            'contact' => ['address' => 'Calle 1', 'city' => '', 'zip' => '123'],
        ];

        $result = $this->processor->process($this->openForm(null, null), $data);
        $this->assertSame(
            ['name' => 'Ana', 'contact' => ['address' => 'Calle 1', 'city' => null]],
            $result->getProcessedData()
        );

        foreach ([true, ['type' => 'string']] as $open) {
            $result = $this->processor->process($this->openForm(null, $open), $data);
            $this->assertSame(
                ['name' => 'Ana', 'contact' => ['address' => 'Calle 1', 'city' => null, 'zip' => '123']],
                $result->getProcessedData()
            );
        }

        $result = $this->processor->process($this->openForm(true, true), $data);
        $this->assertSame(
            ['name' => 'Ana', 'contact' => ['address' => 'Calle 1', 'city' => null, 'zip' => '123'], 'extra' => 'x'],
            $result->getProcessedData()
        );
    }

    public function testAnOpenObjectDoesNotGiveBackTheFieldThatIsNotActive(): void
    {
        $form = $this->openForm(null, true, [
            'effect' => 'SHOW',
            'condition' => ['scope' => '#/properties/name', 'schema' => ['const' => 'yes']],
        ]);

        $result = $this->processor->process($form, [
            'name' => 'no',
            'contact' => ['address' => 'Calle 1', 'city' => 'Secret', 'zip' => '123'],
        ]);

        $this->assertSame(
            ['name' => 'no', 'contact' => ['address' => 'Calle 1', 'zip' => '123']],
            $result->getProcessedData()
        );
    }

    public function testAnOpenObjectThatDidNotComeIsNotCreated(): void
    {
        $result = $this->processor->process($this->openForm(null, true), ['name' => 'Ana']);

        $this->assertSame(['name' => 'Ana', 'contact' => ['address' => null, 'city' => null]], $result->getProcessedData());
    }

    public function testAnObjectWhoseFieldsAreNotActiveIsNotGivenBackAsAnExtra(): void
    {
        $form = Form::fromArray([
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'additionalProperties' => true,
                'properties' => [
                    'name' => ['type' => 'string'],
                    'contact' => ['type' => 'object', 'properties' => ['city' => ['type' => 'string']]],
                ],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/contact/properties/city',
                        'rule' => [
                            'effect' => 'SHOW',
                            'condition' => ['scope' => '#/properties/name', 'schema' => ['const' => 'yes']],
                        ],
                    ],
                ],
            ],
        ]);

        $result = $this->processor->process($form, ['name' => 'no', 'contact' => ['city' => 'Secret'], 'extra' => 'x']);

        $this->assertSame(['name' => 'no', 'extra' => 'x'], $result->getProcessedData());
    }
}
