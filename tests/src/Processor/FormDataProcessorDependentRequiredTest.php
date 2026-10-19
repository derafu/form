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

use Derafu\DataProcessor\ProcessorFactory;
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
 * The fields that an object requires when another one has a value
 * (`dependentRequired`).
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
final class FormDataProcessorDependentRequiredTest extends TestCase
{
    private FormDataProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new FormDataProcessor(new FormRulesResolver(), ProcessorFactory::create());
    }

    /**
     * @param array<string, list<string>> $dependentRequired
     * @param array<string, mixed> $rule The rule of the UI schema of the control `address`.
     */
    private function form(array $dependentRequired, array $rule = []): Form
    {
        $address = ['type' => 'Control', 'scope' => '#/properties/contact/properties/address'];
        if ($rule !== []) {
            $address['rule'] = $rule;
        }

        return Form::fromArray([
            'options' => ['csrf_protection' => false],
            'schema' => [
                'type' => 'object',
                'dependentRequired' => ['name' => ['phone']],
                'properties' => [
                    'name' => ['type' => 'string'],
                    'phone' => ['type' => 'string'],
                    'contact' => [
                        'type' => 'object',
                        'dependentRequired' => $dependentRequired,
                        'properties' => [
                            'card' => ['type' => 'string'],
                            'invoice' => ['type' => 'boolean'],
                            'address' => ['type' => 'string'],
                            'zip' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                    ['type' => 'Control', 'scope' => '#/properties/phone'],
                    ['type' => 'Control', 'scope' => '#/properties/contact/properties/card'],
                    ['type' => 'Control', 'scope' => '#/properties/contact/properties/invoice'],
                    $address,
                    ['type' => 'Control', 'scope' => '#/properties/contact/properties/zip'],
                ],
            ],
        ]);
    }

    public function testTheFieldsOfAnObjectAreRequiredWhenTheTriggerHasAValue(): void
    {
        $form = $this->form(['card' => ['address', 'zip']]);

        $result = $this->processor->process($form, [
            'contact' => ['card' => '4111', 'address' => '', 'zip' => ''],
        ]);

        $this->assertFalse($result->isValid());
        $this->assertSame([
            'contact.address' => ['Field is required.'],
            'contact.zip' => ['Field is required.'],
        ], $result->getErrors());

        $result = $this->processor->process($form, [
            'contact' => ['card' => '4111', 'address' => 'Calle 1', 'zip' => '123'],
        ]);

        $this->assertTrue($result->isValid());
    }

    public function testNothingIsRequiredWhenTheTriggerHasNoValue(): void
    {
        $form = $this->form(['card' => ['address', 'zip']]);

        $result = $this->processor->process($form, [
            'contact' => ['card' => '', 'address' => '', 'zip' => ''],
        ]);

        $this->assertTrue($result->isValid());
    }

    public function testTheRootRequiresItsFields(): void
    {
        $form = $this->form([]);

        $result = $this->processor->process($form, ['name' => 'Ana', 'phone' => '']);

        $this->assertSame(['phone' => ['Field is required.']], $result->getErrors());
    }

    public function testACheckboxThatIsNotCheckedIsNotAValue(): void
    {
        $form = $this->form(['invoice' => ['address']]);

        $result = $this->processor->process($form, ['contact' => ['address' => '']]);

        $this->assertTrue($result->isValid());

        $result = $this->processor->process($form, ['contact' => ['invoice' => '1', 'address' => '']]);

        $this->assertSame(['contact.address' => ['Field is required.']], $result->getErrors());
    }

    public function testNamesThatAreNotFieldsOfTheFormAreIgnored(): void
    {
        $form = $this->form(['card' => ['ghost'], 'ghost' => ['address']]);

        $result = $this->processor->process($form, ['contact' => ['card' => '4111', 'address' => '']]);

        $this->assertTrue($result->isValid());
    }

    public function testAFieldThatIsNotActiveIsNotRequired(): void
    {
        $form = $this->form(['card' => ['address']], [
            'effect' => 'SHOW',
            'condition' => ['scope' => '#/properties/name', 'schema' => ['const' => 'yes']],
        ]);

        $result = $this->processor->process($form, ['name' => 'no', 'phone' => 'x', 'contact' => ['card' => '4111']]);

        $this->assertTrue($result->isValid());
    }
}
