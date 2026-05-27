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

use Derafu\Form\Processor\UiSchemaRuleEvaluator;
use Derafu\Form\UiSchema\ConditionSchema;
use Derafu\Form\UiSchema\UiSchemaCompositeCondition;
use Derafu\Form\UiSchema\UiSchemaCompositeConditionType;
use Derafu\Form\UiSchema\UiSchemaCondition;
use Derafu\Form\UiSchema\UiSchemaRule;
use Derafu\Form\UiSchema\UiSchemaRuleEffect;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UiSchemaRuleEvaluator.
 *
 * The evaluator is the single source of truth for rule evaluation, used by
 * both FormDataProcessor (server-side processing) and ControlRenderer (initial
 * render state).
 */
#[CoversClass(UiSchemaRuleEvaluator::class)]
#[CoversClass(UiSchemaRule::class)]
#[CoversClass(UiSchemaRuleEffect::class)]
#[CoversClass(UiSchemaCondition::class)]
#[CoversClass(UiSchemaCompositeCondition::class)]
#[CoversClass(UiSchemaCompositeConditionType::class)]
#[CoversClass(ConditionSchema::class)]
final class UiSchemaRuleEvaluatorTest extends TestCase
{
    private UiSchemaRuleEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->evaluator = new UiSchemaRuleEvaluator();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Builds a rule with a simple condition:
     *   scope = "#/properties/trigger", schema = $schema
     */
    private function simpleRule(UiSchemaRuleEffect $effect, array $schema): UiSchemaRule
    {
        return new UiSchemaRule(
            $effect,
            new UiSchemaCompositeCondition(
                UiSchemaCompositeConditionType::AND,
                [new UiSchemaCondition('#/properties/trigger', ConditionSchema::fromArray($schema))]
            )
        );
    }

    // =========================================================================
    // Effect polarity
    // =========================================================================

    public function testShowActiveWhenConditionMet(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['const' => 'yes']);
        $this->assertTrue($this->evaluator->isActive($rule, ['trigger' => 'yes']));
    }

    public function testShowInactiveWhenConditionNotMet(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['const' => 'yes']);
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 'no']));
    }

    public function testHideInactiveWhenConditionMet(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::HIDE, ['const' => 'yes']);
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 'yes']));
    }

    public function testHideActiveWhenConditionNotMet(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::HIDE, ['const' => 'yes']);
        $this->assertTrue($this->evaluator->isActive($rule, ['trigger' => 'no']));
    }

    public function testEnableActiveWhenConditionMet(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::ENABLE, ['const' => 'yes']);
        $this->assertTrue($this->evaluator->isActive($rule, ['trigger' => 'yes']));
    }

    public function testEnableInactiveWhenConditionNotMet(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::ENABLE, ['const' => 'yes']);
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 'no']));
    }

    public function testDisableInactiveWhenConditionMet(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::DISABLE, ['const' => 'yes']);
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 'yes']));
    }

    public function testDisableActiveWhenConditionNotMet(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::DISABLE, ['const' => 'yes']);
        $this->assertTrue($this->evaluator->isActive($rule, ['trigger' => 'no']));
    }

    // =========================================================================
    // Missing trigger field
    // =========================================================================

    public function testMissingTriggerFieldTreatedAsNull(): void
    {
        // const 'yes' against null → false → SHOW inactive.
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['const' => 'yes']);
        $this->assertFalse($this->evaluator->isActive($rule, []));
    }

    // =========================================================================
    // Schema keyword: const
    // =========================================================================

    public function testConstMatchExact(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['const' => 'plazo_fijo']);
        $this->assertTrue($this->evaluator->isActive($rule, ['trigger' => 'plazo_fijo']));
    }

    public function testConstNoMatchDifferentCase(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['const' => 'Plazo_Fijo']);
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 'plazo_fijo']));
    }

    // =========================================================================
    // Schema keyword: enum
    // =========================================================================

    public function testEnumMatchesWhenValueInList(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['enum' => ['a', 'b', 'c']]);
        $this->assertTrue($this->evaluator->isActive($rule, ['trigger' => 'b']));
    }

    public function testEnumNoMatchWhenValueNotInList(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['enum' => ['a', 'b', 'c']]);
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 'd']));
    }



    // =========================================================================
    // Schema keyword: contains
    // =========================================================================

    public function testContainsMatchesWhenArrayHasMatchingItem(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['contains' => ['const' => 'x']]);
        $this->assertTrue($this->evaluator->isActive($rule, ['trigger' => ['a', 'x', 'b']]));
    }

    public function testContainsNoMatchWhenArrayLacksItem(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['contains' => ['const' => 'x']]);
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => ['a', 'b']]));
    }

    public function testContainsNoMatchWhenNotAnArray(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['contains' => ['const' => 'x']]);
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 'x']));
    }

    // =========================================================================
    // Schema keyword: not
    // =========================================================================

    public function testNotNegatesInnerSchema(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['not' => ['const' => 'forbidden']]);
        $this->assertTrue($this->evaluator->isActive($rule, ['trigger' => 'allowed']));
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 'forbidden']));
    }

    // =========================================================================
    // Schema keywords: numeric comparisons
    // =========================================================================

    #[DataProvider('numericKeywordProvider')]
    public function testNumericKeywords(
        string $keyword,
        int|float $limit,
        int|float $passing,
        int|float $failing
    ): void {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, [$keyword => $limit]);
        $this->assertTrue($this->evaluator->isActive($rule, ['trigger' => $passing]));
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => $failing]));
    }

    public static function numericKeywordProvider(): array
    {
        return [
            'minimum'          => ['minimum',          10,   10,    9],
            'maximum'          => ['maximum',          10,   10,   11],
            'exclusiveMinimum' => ['exclusiveMinimum', 10,   11,   10],
            'exclusiveMaximum' => ['exclusiveMaximum', 10,    9,   10],
        ];
    }

    public function testNumericKeywordFailsOnNonNumericValue(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['minimum' => 5]);
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 'text']));
    }

    // =========================================================================
    // Schema keyword: pattern
    // =========================================================================

    public function testPatternMatchesValidString(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['pattern' => '^[0-9]{4}$']);
        $this->assertTrue($this->evaluator->isActive($rule, ['trigger' => '1234']));
    }

    public function testPatternNoMatchInvalidString(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['pattern' => '^[0-9]{4}$']);
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 'abcd']));
    }

    public function testInvalidPatternThrowsLogicException(): void
    {
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['pattern' => '[invalid']);
        $this->expectException(\LogicException::class);
        $this->evaluator->isActive($rule, ['trigger' => 'anything']);
    }

    // =========================================================================
    // Composite conditions: AND / OR
    // =========================================================================

    public function testAndCompositeRequiresAllConditionsMet(): void
    {
        $rule = new UiSchemaRule(
            UiSchemaRuleEffect::SHOW,
            new UiSchemaCompositeCondition(
                UiSchemaCompositeConditionType::AND,
                [
                    new UiSchemaCondition('#/properties/a', ConditionSchema::fromArray(['const' => '1'])),
                    new UiSchemaCondition('#/properties/b', ConditionSchema::fromArray(['const' => '2'])),
                ]
            )
        );

        $this->assertTrue($this->evaluator->isActive($rule, ['a' => '1', 'b' => '2']));
        $this->assertFalse($this->evaluator->isActive($rule, ['a' => '1', 'b' => 'x']));
        $this->assertFalse($this->evaluator->isActive($rule, ['a' => 'x', 'b' => '2']));
        $this->assertFalse($this->evaluator->isActive($rule, ['a' => 'x', 'b' => 'x']));
    }

    public function testOrCompositeRequiresAtLeastOneConditionMet(): void
    {
        $rule = new UiSchemaRule(
            UiSchemaRuleEffect::SHOW,
            new UiSchemaCompositeCondition(
                UiSchemaCompositeConditionType::OR,
                [
                    new UiSchemaCondition('#/properties/a', ConditionSchema::fromArray(['const' => '1'])),
                    new UiSchemaCondition('#/properties/b', ConditionSchema::fromArray(['const' => '2'])),
                ]
            )
        );

        $this->assertTrue($this->evaluator->isActive($rule, ['a' => '1', 'b' => 'x']));
        $this->assertTrue($this->evaluator->isActive($rule, ['a' => 'x', 'b' => '2']));
        $this->assertTrue($this->evaluator->isActive($rule, ['a' => '1', 'b' => '2']));
        $this->assertFalse($this->evaluator->isActive($rule, ['a' => 'x', 'b' => 'x']));
    }

    public function testNestedCompositeConditions(): void
    {
        // (a == '1' AND b == '2') OR c == '3'
        $rule = new UiSchemaRule(
            UiSchemaRuleEffect::SHOW,
            new UiSchemaCompositeCondition(
                UiSchemaCompositeConditionType::OR,
                [
                    new UiSchemaCompositeCondition(
                        UiSchemaCompositeConditionType::AND,
                        [
                            new UiSchemaCondition('#/properties/a', ConditionSchema::fromArray(['const' => '1'])),
                            new UiSchemaCondition('#/properties/b', ConditionSchema::fromArray(['const' => '2'])),
                        ]
                    ),
                    new UiSchemaCondition('#/properties/c', ConditionSchema::fromArray(['const' => '3'])),
                ]
            )
        );

        $this->assertTrue($this->evaluator->isActive($rule, ['a' => '1', 'b' => '2', 'c' => 'x']));
        $this->assertTrue($this->evaluator->isActive($rule, ['a' => 'x', 'b' => 'x', 'c' => '3']));
        $this->assertFalse($this->evaluator->isActive($rule, ['a' => '1', 'b' => 'x', 'c' => 'x']));
    }

    // =========================================================================
    // Nested scope (dot notation)
    // =========================================================================

    public function testNestedScopeExtractedAsDoNotation(): void
    {
        // "#/properties/address/properties/city" should resolve to data["address.city"]
        $rule = new UiSchemaRule(
            UiSchemaRuleEffect::SHOW,
            new UiSchemaCompositeCondition(
                UiSchemaCompositeConditionType::AND,
                [new UiSchemaCondition('#/properties/address/properties/city', ConditionSchema::fromArray(['const' => 'Santiago']))]
            )
        );

        $this->assertTrue($this->evaluator->isActive($rule, ['address.city' => 'Santiago']));
        $this->assertFalse($this->evaluator->isActive($rule, ['address.city' => 'Valparaíso']));
    }

    // =========================================================================
    // Multiple keywords in same schema (implicit AND)
    // =========================================================================

    public function testMultipleKeywordsInSameSchemaAllMustPass(): void
    {
        // minimum: 5, maximum: 10 → value 7 passes, value 3 fails, value 12 fails.
        $rule = $this->simpleRule(UiSchemaRuleEffect::SHOW, ['minimum' => 5, 'maximum' => 10]);
        $this->assertTrue($this->evaluator->isActive($rule, ['trigger' => 7]));
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 3]));
        $this->assertFalse($this->evaluator->isActive($rule, ['trigger' => 12]));
    }
}
