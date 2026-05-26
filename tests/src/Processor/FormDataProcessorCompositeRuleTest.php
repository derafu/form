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
use Derafu\Form\Rules\FormRules;
use Derafu\Form\Schema\ArraySchema;
use Derafu\Form\Schema\FormSchema;
use Derafu\Form\Schema\IntegerSchema;
use Derafu\Form\Schema\ObjectSchemaTrait;
use Derafu\Form\Schema\StringSchema;
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
 * Tests server-side evaluation of composite (AND / OR) UiSchema rule conditions.
 *
 * Composite conditions combine multiple simple conditions using a logical
 * operator. The dependent field is active only when the composite condition
 * evaluates to true under the chosen effect.
 *
 * All tests use the SHOW effect so that the polarity is straightforward:
 *   active  = composite condition met     → field is processed.
 *   inactive = composite condition not met → field is skipped.
 *
 * ## AND composite
 *
 *   Condition met   ↔ ALL children are met.
 *   Condition not met ↔ ANY child is not met.
 *
 * ## OR composite
 *
 *   Condition met   ↔ AT LEAST ONE child is met.
 *   Condition not met ↔ ALL children are not met.
 *
 * ## Nested composite (AND inside OR)
 *
 *   Demonstrates arbitrary nesting: the outer condition is OR, one of its
 *   children is itself a two-field AND condition.
 */
#[CoversClass(FormDataProcessor::class)]
#[CoversClass(FormRulesResolver::class)]
#[CoversClass(FormRules::class)]
#[CoversClass(ProcessResult::class)]
#[CoversClass(UiSchemaRule::class)]
#[CoversClass(UiSchemaRuleEffect::class)]
#[CoversClass(UiSchemaCondition::class)]
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
#[CoversClass(ArraySchema::class)]
#[CoversClass(IntegerSchema::class)]
#[CoversClass(Control::class)]
#[CoversClass(VerticalLayout::class)]
#[CoversClass(Widget::class)]
#[CoversClass(WidgetFactory::class)]
final class FormDataProcessorCompositeRuleTest extends TestCase
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
     * Builds a form with three string fields: "trigger_a", "trigger_b", and
     * "dependent". The dependent control carries the given composite rule.
     *
     * @param array $rule Raw rule definition array for the dependent control.
     * @return Form
     */
    private function formWithCompositeRule(array $rule): Form
    {
        return Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'trigger_a' => ['type' => 'string', 'title' => 'Trigger A'],
                    'trigger_b' => ['type' => 'string', 'title' => 'Trigger B'],
                    'dependent' => ['type' => 'string', 'title' => 'Dependent'],
                ],
                'required' => ['trigger_a', 'trigger_b'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/trigger_a'],
                    ['type' => 'Control', 'scope' => '#/properties/trigger_b'],
                    ['type' => 'Control', 'scope' => '#/properties/dependent', 'rule' => $rule],
                ],
            ],
        ]);
    }

    // =========================================================================
    // AND composite — condition met when ALL children are met
    // =========================================================================

    /**
     * AND — both triggers match: dependent must be processed.
     */
    public function testAndBothConditionsMetFieldIsProcessed(): void
    {
        $rule = [
            'effect' => 'SHOW',
            'condition' => [
                'type' => 'AND',
                'conditions' => [
                    ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'yes']],
                    ['scope' => '#/properties/trigger_b', 'schema' => ['const' => 'yes']],
                ],
            ],
        ];

        $result = $this->processor->process($this->formWithCompositeRule($rule), [
            'trigger_a' => 'yes',
            'trigger_b' => 'yes',
            'dependent' => 'value',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * AND — only the first trigger matches: dependent must be skipped.
     */
    public function testAndFirstConditionOnlyFieldIsSkipped(): void
    {
        $rule = [
            'effect' => 'SHOW',
            'condition' => [
                'type' => 'AND',
                'conditions' => [
                    ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'yes']],
                    ['scope' => '#/properties/trigger_b', 'schema' => ['const' => 'yes']],
                ],
            ],
        ];

        $result = $this->processor->process($this->formWithCompositeRule($rule), [
            'trigger_a' => 'yes',
            'trigger_b' => 'no',
            'dependent' => 'value',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * AND — only the second trigger matches: dependent must be skipped.
     */
    public function testAndSecondConditionOnlyFieldIsSkipped(): void
    {
        $rule = [
            'effect' => 'SHOW',
            'condition' => [
                'type' => 'AND',
                'conditions' => [
                    ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'yes']],
                    ['scope' => '#/properties/trigger_b', 'schema' => ['const' => 'yes']],
                ],
            ],
        ];

        $result = $this->processor->process($this->formWithCompositeRule($rule), [
            'trigger_a' => 'no',
            'trigger_b' => 'yes',
            'dependent' => 'value',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * AND — neither trigger matches: dependent must be skipped.
     */
    public function testAndNoConditionMetFieldIsSkipped(): void
    {
        $rule = [
            'effect' => 'SHOW',
            'condition' => [
                'type' => 'AND',
                'conditions' => [
                    ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'yes']],
                    ['scope' => '#/properties/trigger_b', 'schema' => ['const' => 'yes']],
                ],
            ],
        ];

        $result = $this->processor->process($this->formWithCompositeRule($rule), [
            'trigger_a' => 'no',
            'trigger_b' => 'no',
            'dependent' => 'value',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    // =========================================================================
    // OR composite — condition met when AT LEAST ONE child is met
    // =========================================================================

    /**
     * OR — only the first trigger matches: dependent must be processed.
     */
    public function testOrFirstConditionMetFieldIsProcessed(): void
    {
        $rule = [
            'effect' => 'SHOW',
            'condition' => [
                'type' => 'OR',
                'conditions' => [
                    ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'yes']],
                    ['scope' => '#/properties/trigger_b', 'schema' => ['const' => 'yes']],
                ],
            ],
        ];

        $result = $this->processor->process($this->formWithCompositeRule($rule), [
            'trigger_a' => 'yes',
            'trigger_b' => 'no',
            'dependent' => 'value',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * OR — only the second trigger matches: dependent must be processed.
     */
    public function testOrSecondConditionMetFieldIsProcessed(): void
    {
        $rule = [
            'effect' => 'SHOW',
            'condition' => [
                'type' => 'OR',
                'conditions' => [
                    ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'yes']],
                    ['scope' => '#/properties/trigger_b', 'schema' => ['const' => 'yes']],
                ],
            ],
        ];

        $result = $this->processor->process($this->formWithCompositeRule($rule), [
            'trigger_a' => 'no',
            'trigger_b' => 'yes',
            'dependent' => 'value',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * OR — neither trigger matches: dependent must be skipped.
     */
    public function testOrNoConditionMetFieldIsSkipped(): void
    {
        $rule = [
            'effect' => 'SHOW',
            'condition' => [
                'type' => 'OR',
                'conditions' => [
                    ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'yes']],
                    ['scope' => '#/properties/trigger_b', 'schema' => ['const' => 'yes']],
                ],
            ],
        ];

        $result = $this->processor->process($this->formWithCompositeRule($rule), [
            'trigger_a' => 'no',
            'trigger_b' => 'no',
            'dependent' => 'value',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    // =========================================================================
    // Nested composite — OR containing an AND child
    // =========================================================================

    /**
     * OR( simple_A, AND(simple_B, simple_C) )
     *
     * The outer OR fires when EITHER:
     *   - trigger_a = "x"                       (first OR branch), OR
     *   - trigger_a = "y" AND trigger_b = "y"   (second OR branch, itself AND)
     *
     * Case: outer simple branch matches → dependent is processed.
     */
    public function testNestedOrSimpleBranchMetFieldIsProcessed(): void
    {
        $rule = [
            'effect' => 'SHOW',
            'condition' => [
                'type' => 'OR',
                'conditions' => [
                    ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'x']],
                    [
                        'type' => 'AND',
                        'conditions' => [
                            ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'y']],
                            ['scope' => '#/properties/trigger_b', 'schema' => ['const' => 'y']],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->processor->process($this->formWithCompositeRule($rule), [
            'trigger_a' => 'x',
            'trigger_b' => 'anything',
            'dependent' => 'value',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * OR( simple_A, AND(simple_B, simple_C) )
     *
     * Case: inner AND branch both match → dependent is processed.
     */
    public function testNestedOrAndBranchBothMetFieldIsProcessed(): void
    {
        $rule = [
            'effect' => 'SHOW',
            'condition' => [
                'type' => 'OR',
                'conditions' => [
                    ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'x']],
                    [
                        'type' => 'AND',
                        'conditions' => [
                            ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'y']],
                            ['scope' => '#/properties/trigger_b', 'schema' => ['const' => 'y']],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->processor->process($this->formWithCompositeRule($rule), [
            'trigger_a' => 'y',
            'trigger_b' => 'y',
            'dependent' => 'value',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * OR( simple_A, AND(simple_B, simple_C) )
     *
     * Case: outer simple doesn't match AND inner AND is partially met → skipped.
     */
    public function testNestedOrNoBranchMetFieldIsSkipped(): void
    {
        $rule = [
            'effect' => 'SHOW',
            'condition' => [
                'type' => 'OR',
                'conditions' => [
                    ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'x']],
                    [
                        'type' => 'AND',
                        'conditions' => [
                            ['scope' => '#/properties/trigger_a', 'schema' => ['const' => 'y']],
                            ['scope' => '#/properties/trigger_b', 'schema' => ['const' => 'y']],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->processor->process($this->formWithCompositeRule($rule), [
            'trigger_a' => 'y',   // matches AND's first child but not outer simple
            'trigger_b' => 'no',  // breaks the AND
            'dependent' => 'value',
        ]);

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    // =========================================================================
    // contains — modelled after 010_config (enabledDocuments)
    //
    // A SHOW rule with {"contains": {"const": "invoice"}} mirrors the pattern
    // used in 010_config where document-type-specific fields are shown only
    // when the multiselect includes that document type.
    // =========================================================================

    /**
     * Builds a form with an array-type trigger field and a string dependent.
     * The dependent is shown when the trigger array contains $containsValue.
     *
     * Mirrors the 010_config pattern:
     *   enabledDocuments (array) → SHOW invoice-specific fields when array contains "invoice".
     */
    private function formWithArrayTrigger(string $containsValue): Form
    {
        return Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'trigger' => [
                        'type' => 'array',
                        'title' => 'Enabled Documents',
                        'items' => ['type' => 'string'],
                    ],
                    'dependent' => ['type' => 'string', 'title' => 'Dependent'],
                ],
                'required' => ['trigger'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/trigger'],
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/dependent',
                        'rule' => [
                            'effect' => 'SHOW',
                            'condition' => [
                                'scope' => '#/properties/trigger',
                                'schema' => ['contains' => ['const' => $containsValue]],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * contains — the array includes the expected value: dependent is processed.
     */
    public function testContainsConditionMetFieldIsProcessed(): void
    {
        $result = $this->processor->process(
            $this->formWithArrayTrigger('invoice'),
            ['trigger' => ['invoice', 'creditNote'], 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * contains — the array does not include the expected value: dependent is skipped.
     */
    public function testContainsConditionNotMetFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithArrayTrigger('invoice'),
            ['trigger' => ['creditNote', 'debitNote'], 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * contains — the array is empty: dependent is skipped.
     */
    public function testContainsEmptyArrayFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithArrayTrigger('invoice'),
            ['trigger' => [], 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * contains — the trigger value is a scalar, not an array: dependent is skipped.
     *
     * Defensive: a contains condition against a non-array value must always
     * evaluate to false, not throw.
     */
    public function testContainsNonArrayValueFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithArrayTrigger('invoice'),
            ['trigger' => 'invoice', 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    // =========================================================================
    // not — negation of a schema fragment
    //
    // {"not": {"const": "honorarios"}} → active when value IS NOT "honorarios".
    // Mirrors the 016_rules pattern where HIDE + const is semantically equivalent
    // to SHOW + not, but expressed differently.
    // =========================================================================

    /**
     * Builds a form with a string trigger and a string dependent.
     * The dependent is shown when the trigger does NOT match $notValue.
     */
    private function formWithNotRule(string $notValue): Form
    {
        return Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'trigger' => ['type' => 'string', 'title' => 'Contract Type'],
                    'dependent' => ['type' => 'string', 'title' => 'Dependent'],
                ],
                'required' => ['trigger'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/trigger'],
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/dependent',
                        'rule' => [
                            'effect' => 'SHOW',
                            'condition' => [
                                'scope' => '#/properties/trigger',
                                'schema' => ['not' => ['const' => $notValue]],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * not — trigger does NOT match the excluded value: dependent is processed.
     */
    public function testNotConditionMetFieldIsProcessed(): void
    {
        $result = $this->processor->process(
            $this->formWithNotRule('honorarios'),
            ['trigger' => 'indefinido', 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * not — trigger matches the excluded value: dependent is skipped.
     */
    public function testNotConditionNotMetFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithNotRule('honorarios'),
            ['trigger' => 'honorarios', 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    // =========================================================================
    // minimum / maximum / exclusiveMinimum / exclusiveMaximum
    //
    // Numeric range conditions. The trigger field carries an integer value;
    // the dependent field is shown based on the range check.
    // =========================================================================

    /**
     * Builds a form with an integer trigger and a string dependent.
     * The dependent is shown when the trigger satisfies the given schema.
     */
    private function formWithNumericRule(array $conditionSchema): Form
    {
        return Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'trigger' => ['type' => 'integer', 'title' => 'Age', 'minimum' => 0],
                    'dependent' => ['type' => 'string', 'title' => 'Dependent'],
                ],
                'required' => ['trigger'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/trigger'],
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/dependent',
                        'rule' => [
                            'effect' => 'SHOW',
                            'condition' => [
                                'scope' => '#/properties/trigger',
                                'schema' => $conditionSchema,
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * minimum — value equals the minimum (inclusive): dependent is processed.
     */
    public function testMinimumBoundaryMetFieldIsProcessed(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['minimum' => 18]),
            ['trigger' => 18, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * minimum — value is above the minimum: dependent is processed.
     */
    public function testMinimumAboveFieldIsProcessed(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['minimum' => 18]),
            ['trigger' => 25, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * minimum — value is below the minimum: dependent is skipped.
     */
    public function testMinimumBelowFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['minimum' => 18]),
            ['trigger' => 16, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * maximum — value equals the maximum (inclusive): dependent is processed.
     */
    public function testMaximumBoundaryMetFieldIsProcessed(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['maximum' => 65]),
            ['trigger' => 65, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * maximum — value exceeds the maximum: dependent is skipped.
     */
    public function testMaximumExceededFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['maximum' => 65]),
            ['trigger' => 70, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * minimum + maximum — value inside the range: dependent is processed.
     *
     * Verifies implicit AND: both keywords must be satisfied simultaneously.
     */
    public function testRangeInsideFieldIsProcessed(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['minimum' => 18, 'maximum' => 65]),
            ['trigger' => 30, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * minimum + maximum — value below the minimum: dependent is skipped.
     */
    public function testRangeBelowMinimumFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['minimum' => 18, 'maximum' => 65]),
            ['trigger' => 16, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * minimum + maximum — value above the maximum: dependent is skipped.
     */
    public function testRangeAboveMaximumFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['minimum' => 18, 'maximum' => 65]),
            ['trigger' => 70, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * exclusiveMinimum — value equals the boundary (not strictly greater): skipped.
     */
    public function testExclusiveMinimumBoundaryFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['exclusiveMinimum' => 18]),
            ['trigger' => 18, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * exclusiveMinimum — value is strictly above the boundary: dependent is processed.
     */
    public function testExclusiveMinimumAboveFieldIsProcessed(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['exclusiveMinimum' => 18]),
            ['trigger' => 19, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * exclusiveMaximum — value equals the boundary (not strictly less): skipped.
     */
    public function testExclusiveMaximumBoundaryFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['exclusiveMaximum' => 65]),
            ['trigger' => 65, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * exclusiveMaximum — value is strictly below the boundary: dependent is processed.
     */
    public function testExclusiveMaximumBelowFieldIsProcessed(): void
    {
        $result = $this->processor->process(
            $this->formWithNumericRule(['exclusiveMaximum' => 65]),
            ['trigger' => 64, 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    // =========================================================================
    // pattern — ECMA regex matching
    //
    // Useful for conditions that react to the format of a text field, e.g.
    // showing extra fields only when a RUT, email domain, or phone prefix
    // matches the expected format.
    // =========================================================================

    /**
     * Builds a form with a string trigger and a string dependent.
     * The dependent is shown when the trigger matches the given regex pattern.
     */
    private function formWithPatternRule(string $pattern): Form
    {
        return Form::fromArray([
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'trigger' => ['type' => 'string', 'title' => 'Trigger'],
                    'dependent' => ['type' => 'string', 'title' => 'Dependent'],
                ],
                'required' => ['trigger'],
            ],
            'uischema' => [
                'type' => 'VerticalLayout',
                'elements' => [
                    ['type' => 'Control', 'scope' => '#/properties/trigger'],
                    [
                        'type' => 'Control',
                        'scope' => '#/properties/dependent',
                        'rule' => [
                            'effect' => 'SHOW',
                            'condition' => [
                                'scope' => '#/properties/trigger',
                                'schema' => ['pattern' => $pattern],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * pattern — RUT chileno: trigger matches the format → dependent is processed.
     *
     * A RUT like "12345678-9" or "12345678-K" activates the dependent field
     * (e.g. additional tax fields shown only when a valid RUT is entered).
     */
    public function testPatternRutMatchFieldIsProcessed(): void
    {
        $result = $this->processor->process(
            $this->formWithPatternRule('^\d{1,8}-[\dkK]$'),
            ['trigger' => '12345678-9', 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * pattern — RUT format not met: dependent is skipped.
     */
    public function testPatternRutNoMatchFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithPatternRule('^\d{1,8}-[\dkK]$'),
            ['trigger' => '12345678', 'dependent' => 'value'] // missing dash and verifier
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * pattern — corporate email domain: internal fields shown only for
     * employees whose email ends in the company domain.
     */
    public function testPatternCorporateEmailMatchFieldIsProcessed(): void
    {
        $result = $this->processor->process(
            $this->formWithPatternRule('@derafu\\.dev$'),
            ['trigger' => 'esteban@derafu.dev', 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayHasKey('dependent', $result->getProcessedData());
    }

    /**
     * pattern — external email: internal fields must be skipped.
     */
    public function testPatternCorporateEmailNoMatchFieldIsSkipped(): void
    {
        $result = $this->processor->process(
            $this->formWithPatternRule('@derafu\\.dev$'),
            ['trigger' => 'esteban@gmail.com', 'dependent' => 'value']
        );

        $this->assertTrue($result->isValid());
        $this->assertArrayNotHasKey('dependent', $result->getProcessedData());
    }

    /**
     * pattern — invalid PCRE: must throw LogicException immediately.
     *
     * An invalid regex in a rule condition is a programming error (misconfigured
     * form definition). It must fail loudly so the developer catches it during
     * development rather than silently producing incorrect field activation.
     */
    public function testPatternInvalidRegexThrowsLogicException(): void
    {
        $this->expectException(\LogicException::class);

        $this->processor->process(
            $this->formWithPatternRule('[invalid'),
            ['trigger' => 'anything', 'dependent' => 'value']
        );
    }
}
