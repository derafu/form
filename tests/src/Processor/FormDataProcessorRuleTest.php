<?php

declare(strict_types=1);

/**
 * Derafu: Form - Declarative Forms, Seamless Rendering.
 *
 * Copyright (c) 2026 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Form\Tests\Processor;

use Derafu\DataProcessor\ProcessorFactory;
use Derafu\Form\Abstract\AbstractPropertySchema;
use Derafu\Form\Abstract\AbstractUiSchemaElement;
use Derafu\Form\Factory\FormUiSchemaFactory;
use Derafu\Form\Factory\UiSchemaElementFactory;
use Derafu\Form\Form;
use Derafu\Form\FormField;
use Derafu\Form\Options\FormOptions;
use Derafu\Form\Processor\FormDataProcessor;
use Derafu\Form\Processor\FormRulesResolver;
use Derafu\Form\Processor\ProcessResult;
use Derafu\Form\Processor\UiSchemaRuleEvaluator;
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\PropertySchemaFactory;
use Derafu\Form\Schema\StringSchema;
use Derafu\Form\UiSchema\ConditionSchema;
use Derafu\Form\UiSchema\Control;
use Derafu\Form\UiSchema\UiSchemaCompositeCondition;
use Derafu\Form\UiSchema\UiSchemaCompositeConditionType;
use Derafu\Form\UiSchema\UiSchemaCondition;
use Derafu\Form\UiSchema\UiSchemaRule;
use Derafu\Form\UiSchema\UiSchemaRuleEffect;
use Derafu\Form\UiSchema\VerticalLayout;
use Derafu\Form\Widget\Widget;
use Derafu\Form\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Tests the server-side evaluation of UiSchema rules in FormDataProcessor.
 *
 * A rule on a Control defines a conditional behavior: depending on the value
 * of another field (the condition), the control is considered active or
 * inactive. Inactive controls must be skipped entirely during processing:
 * their submitted values are not validated and not included in the result.
 *
 * Effects and their server-side semantics:
 *
 *   SHOW    → active when condition IS met, inactive when condition is NOT met.
 *   HIDE    → active when condition is NOT met, inactive when condition IS met.
 *   ENABLE  → active when condition IS met, inactive when condition is NOT met.
 *   DISABLE → active when condition is NOT met, inactive when condition IS met.
 *
 * SHOW and ENABLE share the same server-side polarity (active = condition met).
 * HIDE and DISABLE share the same server-side polarity (active = condition NOT met).
 * The distinction between them is purely a UI concern (visibility vs. editability).
 *
 * Note: DISABLE (condition met = inactive) differs from ENABLE. A disabled
 * field is visible but not editable; server-side it should be ignored just
 * as a hidden field is.
 */
#[CoversClass(FormDataProcessor::class)]
#[CoversClass(PropertySchemaFactory::class)]
#[CoversClass(UiSchemaRuleEvaluator::class)]
#[CoversClass(FormRulesResolver::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(ProcessResult::class)]
#[CoversClass(UiSchemaRule::class)]
#[CoversClass(UiSchemaRuleEffect::class)]
#[CoversClass(UiSchemaCondition::class)]
#[CoversClass(ConditionSchema::class)]
#[CoversClass(UiSchemaCompositeCondition::class)]
#[CoversClass(UiSchemaCompositeConditionType::class)]
#[CoversClass(AbstractPropertySchema::class)]
#[CoversClass(AbstractUiSchemaElement::class)]
#[CoversClass(Form::class)]
#[CoversClass(FormField::class)]
#[CoversClass(FormOptions::class)]
#[CoversClass(FormSchema::class)]
#[CoversClass(FormUiSchemaFactory::class)]
#[CoversClass(UiSchemaElementFactory::class)]
#[CoversClass(ObjectSchemaTrait::class)]
#[CoversClass(StringSchema::class)]
#[CoversClass(Control::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Widget::class)]
#[CoversClass(WidgetFactory::class)]
final class FormDataProcessorRuleTest extends TestCase
{
    private FormDataProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new FormDataProcessor(
            new FormRulesResolver(),
            ProcessorFactory::create()
        );
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Builds a form with two string fields: a "trigger" select and a
     * "dependent" input. The dependent control carries the given rule.
     *
     * The rule condition always targets the trigger field and checks
     * whether its value equals "active".
     *
     * @param array $rule Raw rule definition array for the dependent control.
     * @param bool $dependentRequired Whether the dependent field is required.
     * @return Form
     */
    private function formWithRule(array $rule, bool $dependentRequired = false): Form
    {
        $required = ['trigger'];
        if ($dependentRequired) {
            $required[] = 'dependent';
        }

        return Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'trigger' => [
                        'type' => 'string',
                        'title' => 'Trigger',
                        'enum' => ['active' => 'Active', 'inactive' => 'Inactive'],
                    ],
                    'dependent' => [
                        'type' => 'string',
                        'title' => 'Dependent field',
                    ],
                ],
                'required' => $required,
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/trigger',
                        'options' => ['type' => 'choice'],
                    ],
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/dependent',
                        'rule' => $rule,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Returns the raw rule definition for the given effect, targeting the
     * "trigger" field and matching the value "active".
     *
     * @param UiSchemaRuleEffect $effect
     * @return array
     */
    private function ruleFor(UiSchemaRuleEffect $effect): array
    {
        return [
            'effect' => $effect->value,
            'condition' => [
                'scope' => '#/properties/trigger',
                'schema' => ['const' => 'active'],
            ],
        ];
    }

    // =========================================================================
    // SHOW effect
    // =========================================================================

    /**
     * SHOW — condition met: trigger = "active".
     * The dependent field is visible → it must be processed normally.
     */
    public function testShowActiveFieldIsProcessed(): void
    {
        $form = $this->formWithRule($this->ruleFor(UiSchemaRuleEffect::SHOW));

        $result = $this->processor->process($form, [
            'trigger' => 'active',
            'dependent' => '  hello  ',
        ]);

        $this->assertTrue($result->isValid());
        $data = $result->getProcessedData();
        $this->assertArrayHasKey('dependent', $data);
        $this->assertSame('hello', $data['dependent']); // trim applied
    }

    /**
     * SHOW — condition not met: trigger ≠ "active".
     * The dependent field is hidden → it must be skipped entirely.
     * Its value must not appear in the processed result.
     */
    public function testShowInactiveFieldIsSkipped(): void
    {
        $form = $this->formWithRule($this->ruleFor(UiSchemaRuleEffect::SHOW));

        $result = $this->processor->process($form, [
            'trigger' => 'inactive',
            'dependent' => 'some value',
        ]);

        $this->assertTrue($result->isValid());
        $data = $result->getProcessedData();
        $this->assertArrayNotHasKey('dependent', $data);
    }

    /**
     * SHOW — condition not met, field is required.
     * Even if the field is declared required in the schema, the rule makes it
     * inactive (hidden), so validation must be skipped and no error produced.
     */
    public function testShowInactiveRequiredFieldProducesNoValidationError(): void
    {
        $form = $this->formWithRule(
            $this->ruleFor(UiSchemaRuleEffect::SHOW),
            dependentRequired: true
        );

        $result = $this->processor->process($form, [
            'trigger' => 'inactive',
            'dependent' => '',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getErrors());
    }

    /**
     * SHOW — condition met, field is required, value is empty.
     * When the field is active (visible), the required rule must run and
     * produce a validation error for the empty value.
     */
    public function testShowActiveRequiredFieldWithEmptyValueFailsValidation(): void
    {
        $form = $this->formWithRule(
            $this->ruleFor(UiSchemaRuleEffect::SHOW),
            dependentRequired: true
        );

        $result = $this->processor->process($form, [
            'trigger' => 'active',
            'dependent' => '',
        ]);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getErrors());
    }

    // =========================================================================
    // HIDE effect
    // =========================================================================

    /**
     * HIDE — condition met: trigger = "active".
     * The dependent field is hidden → it must be skipped entirely.
     */
    public function testHideActiveConditionFieldIsSkipped(): void
    {
        $form = $this->formWithRule($this->ruleFor(UiSchemaRuleEffect::HIDE));

        $result = $this->processor->process($form, [
            'trigger' => 'active',
            'dependent' => 'some value',
        ]);

        $this->assertTrue($result->isValid());
        $data = $result->getProcessedData();
        $this->assertArrayNotHasKey('dependent', $data);
    }

    /**
     * HIDE — condition not met: trigger ≠ "active".
     * The dependent field is visible → it must be processed normally.
     */
    public function testHideInactiveConditionFieldIsProcessed(): void
    {
        $form = $this->formWithRule($this->ruleFor(UiSchemaRuleEffect::HIDE));

        $result = $this->processor->process($form, [
            'trigger' => 'inactive',
            'dependent' => '  world  ',
        ]);

        $this->assertTrue($result->isValid());
        $data = $result->getProcessedData();
        $this->assertArrayHasKey('dependent', $data);
        $this->assertSame('world', $data['dependent']); // trim applied
    }

    // =========================================================================
    // ENABLE effect
    // =========================================================================

    /**
     * ENABLE — condition met: trigger = "active".
     * The dependent field is enabled → it must be processed normally.
     */
    public function testEnableActiveFieldIsProcessed(): void
    {
        $form = $this->formWithRule($this->ruleFor(UiSchemaRuleEffect::ENABLE));

        $result = $this->processor->process($form, [
            'trigger' => 'active',
            'dependent' => 'Consalud',
        ]);

        $this->assertTrue($result->isValid());
        $data = $result->getProcessedData();
        $this->assertArrayHasKey('dependent', $data);
        $this->assertSame('Consalud', $data['dependent']);
    }

    /**
     * ENABLE — condition not met: trigger ≠ "active".
     * The dependent field is disabled → it must be skipped entirely.
     */
    public function testEnableInactiveFieldIsSkipped(): void
    {
        $form = $this->formWithRule($this->ruleFor(UiSchemaRuleEffect::ENABLE));

        $result = $this->processor->process($form, [
            'trigger' => 'inactive',
            'dependent' => 'Consalud',
        ]);

        $this->assertTrue($result->isValid());
        $data = $result->getProcessedData();
        $this->assertArrayNotHasKey('dependent', $data);
    }

    // =========================================================================
    // DISABLE effect
    // =========================================================================

    /**
     * DISABLE — condition met: trigger = "active".
     * The dependent field is disabled → it must be skipped entirely.
     * Disabled fields are visible for reference but must not be processed.
     */
    public function testDisableActiveConditionFieldIsSkipped(): void
    {
        $form = $this->formWithRule($this->ruleFor(UiSchemaRuleEffect::DISABLE));

        $result = $this->processor->process($form, [
            'trigger' => 'active',
            'dependent' => 'some value',
        ]);

        $this->assertTrue($result->isValid());
        $data = $result->getProcessedData();
        $this->assertArrayNotHasKey('dependent', $data);
    }

    /**
     * DISABLE — condition not met: trigger ≠ "active".
     * The dependent field is enabled → it must be processed normally.
     */
    public function testDisableInactiveConditionFieldIsProcessed(): void
    {
        $form = $this->formWithRule($this->ruleFor(UiSchemaRuleEffect::DISABLE));

        $result = $this->processor->process($form, [
            'trigger' => 'inactive',
            'dependent' => 'Banmédica',
        ]);

        $this->assertTrue($result->isValid());
        $data = $result->getProcessedData();
        $this->assertArrayHasKey('dependent', $data);
        $this->assertSame('Banmédica', $data['dependent']);
    }

    // =========================================================================
    // Field without rule is unaffected
    // =========================================================================

    /**
     * A field with no rule is always processed, regardless of other fields.
     */
    public function testFieldWithoutRuleIsAlwaysProcessed(): void
    {
        $form = Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string', 'title' => 'Name'],
                ],
                'required' => ['name'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/name'],
                ],
            ],
        ]);

        $result = $this->processor->process($form, ['name' => 'Esteban']);

        $this->assertTrue($result->isValid());
        $this->assertSame('Esteban', $result->getProcessedData()['name']);
    }
}
